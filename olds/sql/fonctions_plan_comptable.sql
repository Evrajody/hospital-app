-- ==========================================
-- FONCTIONS UTILES POUR LE PLAN COMPTABLE OHADA
-- ==========================================

SET client_encoding = 'UTF-8';

-- ==========================================
-- 1. FONCTION : Obtenir le parent d'un compte
-- ==========================================

CREATE OR REPLACE FUNCTION get_compte_parent(p_numero_compte VARCHAR)
RETURNS TABLE (
    id INTEGER,
    numero_compte VARCHAR,
    libelle VARCHAR,
    classe INTEGER,
    niveau INTEGER,
    type_compte VARCHAR
) AS $$
BEGIN
    -- Si le compte a 1 caractère ou n'existe pas, pas de parent
    IF LENGTH(p_numero_compte) <= 1 THEN
        RETURN;
    END IF;

    RETURN QUERY
    SELECT
        pco.id,
        pco.numero_compte,
        pco.libelle,
        pco.classe,
        pco.niveau,
        pco.type_compte
    FROM plan_comptable_ohada pco
    WHERE pco.numero_compte = LEFT(p_numero_compte, LENGTH(p_numero_compte) - 1);
END;
$$ LANGUAGE plpgsql;

-- Exemple d'utilisation :
-- SELECT * FROM get_compte_parent('6011');


-- ==========================================
-- 2. FONCTION : Obtenir tous les parents (hiérarchie complète)
-- ==========================================

CREATE OR REPLACE FUNCTION get_hierarchie_complete(p_numero_compte VARCHAR)
RETURNS TABLE (
    id INTEGER,
    numero_compte VARCHAR,
    libelle VARCHAR,
    classe INTEGER,
    niveau INTEGER,
    type_compte VARCHAR,
    profondeur INTEGER
) AS $$
BEGIN
    RETURN QUERY
    WITH RECURSIVE hierarchie AS (
        -- Compte de départ
        SELECT
            pco.id,
            pco.numero_compte,
            pco.libelle,
            pco.classe,
            pco.niveau,
            pco.type_compte,
            0 as profondeur
        FROM plan_comptable_ohada pco
        WHERE pco.numero_compte = p_numero_compte

        UNION ALL

        -- Parents récursifs
        SELECT
            p.id,
            p.numero_compte,
            p.libelle,
            p.classe,
            p.niveau,
            p.type_compte,
            h.profondeur + 1 as profondeur
        FROM plan_comptable_ohada p
        INNER JOIN hierarchie h ON p.numero_compte = LEFT(h.numero_compte, LENGTH(h.numero_compte) - 1)
        WHERE LENGTH(p.numero_compte) < LENGTH(h.numero_compte)
    )
    SELECT * FROM hierarchie
    ORDER BY profondeur DESC, numero_compte;
END;
$$ LANGUAGE plpgsql;

-- Exemple d'utilisation :
-- SELECT * FROM get_hierarchie_complete('6011');


-- ==========================================
-- 3. FONCTION : Obtenir tous les enfants d'un compte
-- ==========================================

CREATE OR REPLACE FUNCTION get_comptes_enfants(p_numero_compte VARCHAR, p_niveaux INTEGER DEFAULT 999)
RETURNS TABLE (
    id INTEGER,
    numero_compte VARCHAR,
    libelle VARCHAR,
    classe INTEGER,
    niveau INTEGER,
    type_compte VARCHAR,
    profondeur INTEGER
) AS $$
BEGIN
    RETURN QUERY
    WITH RECURSIVE enfants AS (
        -- Compte de départ
        SELECT
            pco.id,
            pco.numero_compte,
            pco.libelle,
            pco.classe,
            pco.niveau,
            pco.type_compte,
            0 as profondeur
        FROM plan_comptable_ohada pco
        WHERE pco.numero_compte = p_numero_compte

        UNION ALL

        -- Enfants directs
        SELECT
            e.id,
            e.numero_compte,
            e.libelle,
            e.classe,
            e.niveau,
            e.type_compte,
            enf.profondeur + 1 as profondeur
        FROM plan_comptable_ohada e
        INNER JOIN enfants enf ON e.numero_compte LIKE enf.numero_compte || '%'
            AND LENGTH(e.numero_compte) = LENGTH(enf.numero_compte) + 1
        WHERE enf.profondeur < p_niveaux
    )
    SELECT * FROM enfants
    ORDER BY numero_compte;
END;
$$ LANGUAGE plpgsql;

-- Exemple d'utilisation :
-- SELECT * FROM get_comptes_enfants('60');      -- Tous les enfants
-- SELECT * FROM get_comptes_enfants('60', 1);   -- Enfants directs seulement


-- ==========================================
-- 4. FONCTION : Vérifier si un compte est un parent d'un autre
-- ==========================================

CREATE OR REPLACE FUNCTION est_parent_de(p_compte_parent VARCHAR, p_compte_enfant VARCHAR)
RETURNS BOOLEAN AS $$
BEGIN
    -- Un compte est parent si l'enfant commence par le numéro du parent
    -- et que l'enfant est plus long que le parent
    RETURN p_compte_enfant LIKE p_compte_parent || '%'
        AND LENGTH(p_compte_enfant) > LENGTH(p_compte_parent);
END;
$$ LANGUAGE plpgsql;

-- Exemple d'utilisation :
-- SELECT est_parent_de('60', '6011');    -- Retourne TRUE
-- SELECT est_parent_de('60', '701');     -- Retourne FALSE


-- ==========================================
-- 5. FONCTION : Obtenir le chemin complet d'un compte (breadcrumb)
-- ==========================================

CREATE OR REPLACE FUNCTION get_chemin_compte(p_numero_compte VARCHAR)
RETURNS TEXT AS $$
DECLARE
    chemin TEXT := '';
    compte_courant VARCHAR := p_numero_compte;
    libelle_courant VARCHAR;
BEGIN
    -- Construire le chemin de bas en haut
    WHILE LENGTH(compte_courant) > 0 LOOP
        SELECT libelle INTO libelle_courant
        FROM plan_comptable_ohada
        WHERE numero_compte = compte_courant;

        IF FOUND THEN
            IF chemin = '' THEN
                chemin := compte_courant || ' - ' || libelle_courant;
            ELSE
                chemin := compte_courant || ' - ' || libelle_courant || ' > ' || chemin;
            END IF;
        END IF;

        -- Remonter au parent
        EXIT WHEN LENGTH(compte_courant) <= 1;
        compte_courant := LEFT(compte_courant, LENGTH(compte_courant) - 1);
    END LOOP;

    RETURN chemin;
END;
$$ LANGUAGE plpgsql;

-- Exemple d'utilisation :
-- SELECT get_chemin_compte('6011');


-- ==========================================
-- 6. FONCTION : Obtenir la profondeur d'un compte
-- ==========================================

CREATE OR REPLACE FUNCTION get_profondeur_compte(p_numero_compte VARCHAR)
RETURNS INTEGER AS $$
BEGIN
    -- La profondeur = la longueur du numéro de compte
    RETURN LENGTH(p_numero_compte);
END;
$$ LANGUAGE plpgsql;


-- ==========================================
-- 7. VUE : Comptes avec leurs parents directs
-- ==========================================

CREATE OR REPLACE VIEW v_comptes_avec_parents AS
SELECT
    c.id,
    c.numero_compte,
    c.libelle,
    c.classe,
    c.niveau,
    c.type_compte,
    CASE
        WHEN LENGTH(c.numero_compte) > 1 THEN LEFT(c.numero_compte, LENGTH(c.numero_compte) - 1)
        ELSE NULL
    END as numero_compte_parent,
    p.libelle as libelle_parent,
    p.niveau as niveau_parent
FROM plan_comptable_ohada c
LEFT JOIN plan_comptable_ohada p ON p.numero_compte = LEFT(c.numero_compte, LENGTH(c.numero_compte) - 1);

-- Exemple d'utilisation :
-- SELECT * FROM v_comptes_avec_parents WHERE numero_compte = '6011';


-- ==========================================
-- 8. FONCTION : Rechercher des comptes par libellé
-- ==========================================

CREATE OR REPLACE FUNCTION rechercher_comptes(p_recherche VARCHAR)
RETURNS TABLE (
    numero_compte VARCHAR,
    libelle VARCHAR,
    classe INTEGER,
    niveau INTEGER,
    type_compte VARCHAR,
    chemin TEXT
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        c.numero_compte,
        c.libelle,
        c.classe,
        c.niveau,
        c.type_compte,
        get_chemin_compte(c.numero_compte) as chemin
    FROM plan_comptable_ohada c
    WHERE c.libelle ILIKE '%' || p_recherche || '%'
       OR c.numero_compte ILIKE '%' || p_recherche || '%'
    ORDER BY c.numero_compte;
END;
$$ LANGUAGE plpgsql;

-- Exemple d'utilisation :
-- SELECT * FROM rechercher_comptes('client');


-- ==========================================
-- 9. FONCTION : Valider un numéro de compte
-- ==========================================

CREATE OR REPLACE FUNCTION valider_numero_compte(p_numero_compte VARCHAR)
RETURNS TABLE (
    est_valide BOOLEAN,
    message TEXT,
    compte_suggere VARCHAR
) AS $$
DECLARE
    v_existe BOOLEAN;
    v_parent_existe BOOLEAN;
    v_numero_parent VARCHAR;
BEGIN
    -- Vérifier si le compte existe
    SELECT EXISTS(SELECT 1 FROM plan_comptable_ohada WHERE numero_compte = p_numero_compte) INTO v_existe;

    IF v_existe THEN
        RETURN QUERY SELECT TRUE, 'Compte valide'::TEXT, p_numero_compte;
        RETURN;
    END IF;

    -- Si le compte n'existe pas, vérifier le parent
    IF LENGTH(p_numero_compte) > 1 THEN
        v_numero_parent := LEFT(p_numero_compte, LENGTH(p_numero_compte) - 1);
        SELECT EXISTS(SELECT 1 FROM plan_comptable_ohada WHERE numero_compte = v_numero_parent) INTO v_parent_existe;

        IF v_parent_existe THEN
            RETURN QUERY SELECT
                FALSE,
                'Compte inexistant, mais le parent existe'::TEXT,
                v_numero_parent;
            RETURN;
        END IF;
    END IF;

    -- Aucun parent trouvé
    RETURN QUERY SELECT FALSE, 'Compte inexistant'::TEXT, NULL::VARCHAR;
END;
$$ LANGUAGE plpgsql;

-- Exemple d'utilisation :
-- SELECT * FROM valider_numero_compte('6011');
-- SELECT * FROM valider_numero_compte('9999');


-- ==========================================
-- COMMENTAIRES
-- ==========================================

COMMENT ON FUNCTION get_compte_parent(VARCHAR) IS 'Retourne le compte parent direct d''un compte donné';
COMMENT ON FUNCTION get_hierarchie_complete(VARCHAR) IS 'Retourne toute la hiérarchie (tous les parents) d''un compte';
COMMENT ON FUNCTION get_comptes_enfants(VARCHAR, INTEGER) IS 'Retourne tous les comptes enfants d''un compte';
COMMENT ON FUNCTION est_parent_de(VARCHAR, VARCHAR) IS 'Vérifie si un compte est parent d''un autre';
COMMENT ON FUNCTION get_chemin_compte(VARCHAR) IS 'Retourne le chemin complet d''un compte (breadcrumb)';
COMMENT ON FUNCTION rechercher_comptes(VARCHAR) IS 'Recherche des comptes par libellé ou numéro';
COMMENT ON FUNCTION valider_numero_compte(VARCHAR) IS 'Valide un numéro de compte et suggère des alternatives';

-- ==========================================
-- FIN DES FONCTIONS
-- ==========================================
