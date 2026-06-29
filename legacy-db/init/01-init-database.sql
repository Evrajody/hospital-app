-- ==========================================
-- INITIALIZATION SCRIPT FOR LEGACY DATABASE
-- ==========================================

\set ON_ERROR_STOP off

SET client_encoding = 'UTF-8';
SET standard_conforming_strings = off;

DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'legacy_anonymous') THEN
        CREATE ROLE legacy_anonymous NOLOGIN;
    END IF;
END
$$;

GRANT USAGE ON SCHEMA public TO legacy_anonymous;

\echo 'Loading plan_comptable_ohada.sql...'
\i /docker-entrypoint-initdb.d/sql_processed/plan_comptable_ohada.sql

\echo 'Loading fonctions_plan_comptable.sql...'
\i /docker-entrypoint-initdb.d/sql/fonctions_plan_comptable.sql

\echo 'Loading database_export.sql...'
\i /docker-entrypoint-initdb.d/sql_processed/database_export.sql

\echo 'Loading legacy_clients.sql...'
\i /docker-entrypoint-initdb.d/sql_processed/legacy_clients.sql

\echo 'Loading legacy_fournisseurs.sql...'
\i /docker-entrypoint-initdb.d/sql_processed/legacy_fournisseurs.sql

\echo 'Creating API views...'

-- View: Clients with invoice stats
DO $$
BEGIN
    DROP VIEW IF EXISTS api_clients;
    CREATE VIEW api_clients AS
    SELECT 
        c.numcli,
        c.rscli,
        c.adrcli,
        c."user",
        c.cpt,
        COUNT(f.reffac) as nombre_factures,
        COALESCE(SUM(f.mtfac), 0) as total_factures
    FROM client c
    LEFT JOIN facture f ON c.numcli = f.numcli
    GROUP BY c.numcli, c.rscli, c.adrcli, c."user", c.cpt;
EXCEPTION WHEN undefined_table THEN
    RAISE NOTICE 'Skipping api_clients - dependency missing';
END
$$;

-- View: Factures with client info
DO $$
BEGIN
    DROP VIEW IF EXISTS api_factures;
    CREATE VIEW api_factures AS
    SELECT 
        f.reffac,
        f.datfac,
        f.mtfac,
        f.numcli,
        f.etatfac,
        f."user",
        f.ann
    FROM facture f;
EXCEPTION WHEN undefined_table THEN
    RAISE NOTICE 'Skipping api_factures - dependency missing';
END
$$;

-- View: Fournisseurs
DO $$
BEGIN
    DROP VIEW IF EXISTS api_fournisseurs;
    CREATE VIEW api_fournisseurs AS
    SELECT 
        f.rsfsr,
        f.comptfsr,
        f.numifu,
        f.numtel,
        f.pays,
        f.ville,
        f.contact,
        f.info,
        f."user"
    FROM fournisseur f;
EXCEPTION WHEN undefined_table THEN
    RAISE NOTICE 'Skipping api_fournisseurs - dependency missing';
END
$$;

-- View: Plan comptable with hierarchy
DO $$
BEGIN
    DROP VIEW IF EXISTS api_plan_comptable;
    CREATE VIEW api_plan_comptable AS
    SELECT 
        pco.id,
        pco.numero_compte,
        pco.libelle,
        pco.classe,
        pco.niveau,
        pco.type_compte,
        pco.utilisable
    FROM plan_comptable_ohada pco
    ORDER BY pco.numero_compte;
EXCEPTION WHEN undefined_table THEN
    RAISE NOTICE 'Skipping api_plan_comptable - dependency missing';
END
$$;

-- View: Reglements
DO $$
BEGIN
    DROP VIEW IF EXISTS api_reglements;
    CREATE VIEW api_reglements AS
    SELECT 
        r.lreg,
        r.reffac,
        r.datreg,
        r.refch,
        r.mtch,
        r.mtchl,
        r.insreg,
        r."user"
    FROM reglement r;
EXCEPTION WHEN undefined_table THEN
    RAISE NOTICE 'Skipping api_reglements - dependency missing';
END
$$;

-- Grant permissions
DO $$
BEGIN
    GRANT SELECT ON api_clients TO legacy_anonymous;
    GRANT SELECT ON api_factures TO legacy_anonymous;
    GRANT SELECT ON api_fournisseurs TO legacy_anonymous;
    GRANT SELECT ON api_plan_comptable TO legacy_anonymous;
    GRANT SELECT ON api_reglements TO legacy_anonymous;
EXCEPTION WHEN undefined_table THEN
    RAISE NOTICE 'Some views not created, skipping grants';
END
$$;

GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO legacy_anonymous;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO legacy_anonymous;

\echo 'Legacy database initialization complete!'
