#!/usr/bin/env python3
"""
Préprocesseur SQL pour la base legacy.
- Convert BOOLEAN → INTEGER
- Sépare les INSERTs multi-lignes en INSERT individuels
- Ajoute ON CONFLICT DO NOTHING
- Corrige les dates invalides et Unicode escapes
- Renomme les tables en conflit dans legacy_fournisseurs.sql
"""
import re
import os
import sys

# Tables dont legacy_fournisseurs.sql a un schéma DIFFÉRENT de database_export.sql
# On les renomme en suffixant _fournisseur pour garder les deux versions
FSLR_CONFLICT_TABLES = {
    'facture': 'facture_fournisseur',
    'imputation': 'imputation_fournisseur',
    'mouvement': 'mouvement_fournisseur',
    'reglement': 'reglement_fournisseur',
}


def convert_boolean_to_integer(sql):
    sql = re.sub(r'\bBOOLEAN\s+NOT\s+NULL\b', 'INTEGER NOT NULL', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\bBOOLEAN\b(?!\s+NOT)', 'INTEGER', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\bDEFAULT\s+TRUE\b', 'DEFAULT 1', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\bDEFAULT\s+FALSE\b', 'DEFAULT 0', sql, flags=re.IGNORECASE)
    return sql


def expand_multivalue_inserts(sql):
    """
    INSERT INTO t (cols) VALUES (v1), (v2), (v3);
    → INSERT INTO t (cols) VALUES (v1); INSERT INTO t (cols) VALUES (v2); ...
    """
    result = []
    buffer = ""

    for line in sql.split('\n'):
        stripped = line.strip()
        if not stripped or stripped.startswith('--'):
            buffer += "\n" + line if buffer else ""
            if not stripped:
                if buffer:
                    result.append(buffer)
                    buffer = ""
                result.append(line)
            continue

        buffer += "\n" + line if buffer else line

        if buffer.rstrip().endswith(';'):
            result.append(buffer.strip())
            buffer = ""

    if buffer.strip():
        result.append(buffer.strip())

    joined = '\n'.join(result)

    # Expand multi-value INSERTs
    pattern = re.compile(
        r'(INSERT\s+INTO\s+"[^"]+"\s*\([^)]+\)\s*VALUES\s+)(.+?)(\s*;)',
        re.IGNORECASE | re.DOTALL
    )

    def expand(m):
        prefix = m.group(1)
        values_part = m.group(2)
        suffix = m.group(3)

        tuples = []
        depth = 0
        in_string = False
        current = ""
        i = 0
        while i < len(values_part):
            ch = values_part[i]
            if ch == "'" and not in_string:
                in_string = True
                current += ch
            elif ch == "'" and in_string:
                # Check for escaped quote ''
                if i + 1 < len(values_part) and values_part[i + 1] == "'":
                    current += "''"
                    i += 1
                else:
                    in_string = False
                    current += ch
            elif not in_string and ch == '(':
                depth += 1
                current += ch
            elif not in_string and ch == ')':
                depth -= 1
                current += ch
            elif not in_string and ch == ',' and depth == 0:
                tuples.append(current.strip())
                current = ""
            else:
                current += ch
            i += 1
        if current.strip():
            tuples.append(current.strip())

        if len(tuples) <= 1:
            return prefix + values_part + suffix

        inserts = []
        for t in tuples:
            inserts.append(f"{prefix}{t}{suffix}")
        return '\n'.join(inserts)

    return pattern.sub(expand, joined)


def add_on_conflict(sql):
    pattern = re.compile(
        r'(INSERT\s+INTO\s+"[^"]+"\s*\([^)]+\)\s*VALUES\s*\([^)]+\))(\s*;)',
        re.IGNORECASE
    )
    def add(m):
        insert = m.group(1)
        semi = m.group(2)
        if 'ON CONFLICT' not in insert.upper():
            return f"{insert} ON CONFLICT DO NOTHING{semi}"
        return f"{insert}{semi}"
    return pattern.sub(add, sql)


def fix_dates(sql):
    sql = re.sub(r"'0000-00-00\s*00:00:00'", "NULL", sql, flags=re.IGNORECASE)
    sql = re.sub(r"'0000-00-00'", "NULL", sql, flags=re.IGNORECASE)
    sql = re.sub(r"'(\d{4})-00-00\s*00:00:00'", r"'\1-01-01 00:00:00'", sql)
    sql = re.sub(r"'(\d{4})-(\d{2})-00\s*00:00:00'", r"'\1-\2-01 00:00:00'", sql)
    return sql


def fix_unicode_escapes(sql):
    # Fix Windows paths: C:\Users\... → C:\\Users\\...
    # PostgreSQL interprets \U, \A etc as Unicode escapes
    def fix_backslashes_in_string(m):
        prefix = m.group(1)
        content = m.group(2)
        suffix = m.group(3)
        # Only fix backslashes that look like escape sequences (\U, \b, \H etc)
        # but are not valid PG escapes
        # Double backslashes that aren't valid PG escape sequences
        # Valid: \\ \' \" \0 \n \r \t
        content = re.sub(r"\\(?!['\"\\0nrt])", r"\\\\", content)
        return prefix + content + suffix
    
    sql = re.sub(r"(')((?:[^'\\]|\\.)*)(')", fix_backslashes_in_string, sql)
    
    # Fix "No MSysRelationships" line that's not valid SQL
    sql = re.sub(r'^No MSysRelationships\s*$', '-- No MSysRelationships', sql, flags=re.MULTILINE)
    
    # Fix uppercase table references in ALTER TABLE FOREIGN KEY
    sql = re.sub(r'ALTER\s+TABLE\s+"FACTURE"', 'ALTER TABLE "facture"', sql, flags=re.IGNORECASE)
    sql = re.sub(r'ALTER\s+TABLE\s+"REGLEMENT"', 'ALTER TABLE "reglement"', sql, flags=re.IGNORECASE)
    sql = re.sub(r'ALTER\s+TABLE\s+"CLIENT"', 'ALTER TABLE "client"', sql, flags=re.IGNORECASE)
    sql = re.sub(r'REFERENCES\s+"FACTURE"', 'REFERENCES "facture"', sql, flags=re.IGNORECASE)
    sql = re.sub(r'REFERENCES\s+"CLIENT"', 'REFERENCES "client"', sql, flags=re.IGNORECASE)
    sql = re.sub(r'REFERENCES\s+"REGLEMENT"', 'REFERENCES "reglement"', sql, flags=re.IGNORECASE)
    
    # Fix index names with special characters (n° → num)
    sql = re.sub(r'"user_n°_idx"', '"user_num_idx"', sql)
    
    return sql


def rename_conflicting_tables(sql, mapping):
    """
    Renomme les tables et leurs références dans un fichier SQL.
    CREATE TABLE "facture" → CREATE TABLE "facture_fournisseur"
    INSERT INTO "facture" → INSERT INTO "facture_fournisseur"
    CREATE INDEX ... ON "facture" → CREATE INDEX ... ON "facture_fournisseur"
    """
    for old, new in mapping.items():
        escaped = re.escape(old)
        # CREATE TABLE "old"
        sql = re.sub(
            rf'(CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+"){escaped}(")',
            rf'\g<1>{new}\2',
            sql, flags=re.IGNORECASE
        )
        # INSERT INTO "old"
        sql = re.sub(
            rf'(INSERT\s+INTO\s+"){escaped}(")',
            rf'\g<1>{new}\2',
            sql, flags=re.IGNORECASE
        )
        # CREATE INDEX ... ON "old"
        sql = re.sub(
            rf'(CREATE\s+INDEX\s+["\w]+\s+ON\s+"){escaped}(")',
            rf'\g<1>{new}\2',
            sql, flags=re.IGNORECASE
        )
        # ALTER TABLE "old"
        sql = re.sub(
            rf'(ALTER\s+TABLE\s+"){escaped}(")',
            rf'\g<1>{new}\2',
            sql, flags=re.IGNORECASE
        )
        # REFERENCES "old"
        sql = re.sub(
            rf'(REFERENCES\s+"){escaped}(")',
            rf'\g<1>{new}\2',
            sql, flags=re.IGNORECASE
        )
    return sql


def fix_uppercase_table_refs(sql):
    """Convert all UPPERCASE quoted identifiers to lowercase."""
    sql = re.sub(r'"COMPTENIV1"', '"compteniv1"', sql)
    sql = re.sub(r'"COMPTENIV2"', '"compteniv2"', sql)
    sql = re.sub(r'"COMPTENIV3"', '"compteniv3"', sql)
    sql = re.sub(r'"COMPTENIV4"', '"compteniv4"', sql)
    sql = re.sub(r'"COMPTENIV5"', '"compteniv5"', sql)
    sql = re.sub(r'"COMPTENIV6"', '"compteniv6"', sql)
    sql = re.sub(r'"COMPTENIV7"', '"compteniv7"', sql)
    sql = re.sub(r'"COMPTENIV8"', '"compteniv8"', sql)
    sql = re.sub(r'"COMPTENIV9"', '"compteniv9"', sql)
    sql = re.sub(r'"CLASSECOMPTE"', '"classecompte"', sql)
    sql = re.sub(r'"COMPTENIV0"', '"compteniv0"', sql)
    sql = re.sub(r'"RESPO"', '"respo"', sql)
    sql = re.sub(r'"APPROVISIONNEMENT"', '"approvisionnement"', sql)
    sql = re.sub(r'"USER"', '"user"', sql)
    sql = re.sub(r'"FOURNISSEUR"', '"fournisseur"', sql)
    sql = re.sub(r'"FACTURE"', '"facture"', sql)
    sql = re.sub(r'"REGLEMENT"', '"reglement"', sql)
    sql = re.sub(r'"CLIENT"', '"client"', sql)
    sql = re.sub(r'"IMPUTATION"', '"imputation"', sql)
    sql = re.sub(r'"MOUVEMENT"', '"mouvement"', sql)
    sql = re.sub(r'"BANQUE"', '"banque"', sql)
    sql = re.sub(r'"BORDEREAU"', '"bordereau"', sql)
    sql = re.sub(r'"BASE"', '"base"', sql)
    sql = re.sub(r'"PLAN_COMPTABLE_OHADA"', '"plan_comptable_ohada"', sql)
    return sql


def fix_constraint_names(sql, mapping):
    """Rename constraint names to avoid collisions with tables from other files.
    e.g. facture_pkey → facture_fournisseur_pkey (when table was renamed)
    Also comment out FK constraints that reference renamed tables.
    """
    for old, new in mapping.items():
        escaped = re.escape(old)
        # Rename PK constraints: "facture_pkey" → "facture_fournisseur_pkey"
        sql = re.sub(
            rf'(ADD\s+CONSTRAINT\s+"){escaped}(_pkey)(")',
            rf'\g<1>{new}_pkey\3',
            sql, flags=re.IGNORECASE
        )
        # Rename FK constraints referencing the old table
        sql = re.sub(
            rf'(ADD\s+CONSTRAINT\s+"){escaped}(_\w+_fk)(")',
            rf'\g<1>{new}\3',
            sql, flags=re.IGNORECASE
        )
        # Also fix index names: facture_numcptacpt_idx → facture_fournisseur_numcptacpt_idx
        sql = re.sub(
            rf'(CREATE\s+INDEX\s+"){escaped}(_)',
            rf'\g<1>{new}_',
            sql, flags=re.IGNORECASE
        )
    
    # Comment out all FK constraints - they block INSERTs due to load order issues
    # and aren't needed for our read-only API
    sql = re.sub(
        r'(ALTER\s+TABLE\s+"[^"]+"\s+ADD\s+CONSTRAINT\s+"[^"]+"\s+FOREIGN\s+KEY[^;]+;)',
        r'-- \1',
        sql, flags=re.IGNORECASE
    )
    return sql


def process_file(input_path, output_path, rename_map=None):
    with open(input_path, 'r', encoding='utf-8') as f:
        sql = f.read()

    sql = convert_boolean_to_integer(sql)

    if rename_map:
        sql = rename_conflicting_tables(sql, rename_map)
        sql = fix_constraint_names(sql, rename_map)

    sql = expand_multivalue_inserts(sql)
    sql = add_on_conflict(sql)
    sql = fix_dates(sql)
    sql = fix_unicode_escapes(sql)
    sql = fix_uppercase_table_refs(sql)

    with open(output_path, 'w', encoding='utf-8') as f:
        f.write(sql)

    insert_count = len(re.findall(r'INSERT\s+INTO', sql, re.IGNORECASE))
    table_count = len(re.findall(r'CREATE\s+TABLE', sql, re.IGNORECASE))
    print(f"  {os.path.basename(input_path):40s} → {insert_count:6d} INSERTs, {table_count} tables")


def main():
    script_dir = os.path.dirname(os.path.abspath(__file__))
    input_dir = os.path.join(script_dir, 'init', 'sql')
    output_dir = os.path.join(script_dir, 'init', 'sql_processed')
    os.makedirs(output_dir, exist_ok=True)

    files = [
        ('plan_comptable_ohada.sql', None),
        ('database_export.sql', None),
        ('legacy_clients.sql', None),
        ('legacy_fournisseurs.sql', FSLR_CONFLICT_TABLES),
    ]

    print("Préprocessing des fichiers SQL:")
    for fname, rename_map in files:
        input_path = os.path.join(input_dir, fname)
        output_path = os.path.join(output_dir, fname)
        if os.path.exists(input_path):
            process_file(input_path, output_path, rename_map)
        else:
            print(f"  {fname} — INTRUVABLE")

    print(f"\nRésultat dans: {output_dir}")


if __name__ == '__main__':
    main()
