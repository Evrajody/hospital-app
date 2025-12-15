#!/bin/bash

OUTPUT_FILE="../database_export.sql"

# Fonction pour exporter les données d'une table
export_table_data() {
    local db_file="$1"
    local table_name="$2"

    echo ""
    echo "-- Données de la table: $table_name"
    echo ""

    # Exporter les données en format SQL INSERT
    mdb-export -I postgres "$db_file" "$table_name" 2>/dev/null || echo "-- Erreur lors de l'export de $table_name"
}

echo "" >> "$OUTPUT_FILE"
echo "-- ==========================================" >> "$OUTPUT_FILE"
echo "-- INSERTION DES DONNÉES" >> "$OUTPUT_FILE"
echo "-- ==========================================" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# Export des données de "Base Factures Clients.accdb"
echo "" >> "$OUTPUT_FILE"
echo "-- ===========================================" >> "$OUTPUT_FILE"
echo "-- Données de: Base Factures Clients.accdb" >> "$OUTPUT_FILE"
echo "-- ===========================================" >> "$OUTPUT_FILE"

DB1="Base Factures Clients.accdb"
for table in BANQUE BORDEREAU CLIENT FACTURE IMPUTATION REGLEMENT "USER 1" MOUVEMENT USER; do
    export_table_data "$DB1" "$table" >> "$OUTPUT_FILE"
done

# Export des données de "Gestion des Factures des Fournisseurs.accdb"
echo "" >> "$OUTPUT_FILE"
echo "-- ===========================================" >> "$OUTPUT_FILE"
echo "-- Données de: Gestion des Factures des Fournisseurs.accdb" >> "$OUTPUT_FILE"
echo "-- ===========================================" >> "$OUTPUT_FILE"

DB2="Gestion des Factures des Fournisseurs.accdb"
for table in BASE LISTACPT LISTCOMPT MOIS NIVEAU SITUATIONBANQ; do
    export_table_data "$DB2" "$table" >> "$OUTPUT_FILE"
done

echo "" >> "$OUTPUT_FILE"
echo "-- ===========================================" >> "$OUTPUT_FILE"
echo "-- FIN DE L'EXPORT" >> "$OUTPUT_FILE"
echo "-- ===========================================" >> "$OUTPUT_FILE"

echo "Export terminé: $OUTPUT_FILE"
