-- ============================================================================
-- Comparaison volumétrie STAGING vs APP (à lancer APRÈS `make migrate-legacy-load`
-- puis `make migrate-legacy`).
-- Usage : docker exec -i hospital-db psql -U hospital_user -d hospital_db -f - < ce_fichier.sql
--   (ou copier/coller les requêtes dans `make legacy-shell` côté hospital-db)
-- ============================================================================

\echo '======== STAGING BRUT (ce que la migration voit) ========'
SELECT 'legacy_clients.facture'              AS source, count(*) FROM legacy_clients.facture
UNION ALL SELECT 'legacy_clients.reglement',        count(*) FROM legacy_clients.reglement
UNION ALL SELECT 'legacy_clients.client',           count(*) FROM legacy_clients.client
UNION ALL SELECT 'legacy_clients.bordereau',        count(*) FROM legacy_clients.bordereau
UNION ALL SELECT 'legacy_clients.imputation (NON importé)', count(*) FROM legacy_clients.imputation
UNION ALL SELECT 'legacy_clients.mouvement (NON importé)',  count(*) FROM legacy_clients.mouvement
UNION ALL SELECT 'legacy_fsr.facture_fournisseur',  count(*) FROM legacy_fsr.facture_fournisseur
UNION ALL SELECT 'legacy_fsr.reglement_fournisseur',count(*) FROM legacy_fsr.reglement_fournisseur
UNION ALL SELECT 'legacy_fsr.fournisseur',          count(*) FROM legacy_fsr.fournisseur
UNION ALL SELECT 'legacy_fsr.approvisionnement',    count(*) FROM legacy_fsr.approvisionnement
UNION ALL SELECT 'legacy_fsr.imputation_fournisseur',count(*) FROM legacy_fsr.imputation_fournisseur
UNION ALL SELECT 'legacy_fsr.mouvement_fournisseur (NON importé)', count(*) FROM legacy_fsr.mouvement_fournisseur;

\echo '======== STAGING DÉDOUBLONNÉ par clé naturelle (attendu ~= app) ========'
SELECT 'factures_clients (reffac distinct)'   AS attendu, count(DISTINCT lower(trim(reffac))) FROM legacy_clients.facture WHERE reffac IS NOT NULL
UNION ALL SELECT 'factures_fsr (nump distinct)',           count(DISTINCT lower(trim(nump)))  FROM legacy_fsr.facture_fournisseur WHERE nump IS NOT NULL
UNION ALL SELECT 'fournisseurs (rsfsr distinct)',          count(DISTINCT lower(trim(rsfsr))) FROM legacy_fsr.fournisseur WHERE rsfsr IS NOT NULL;

\echo '======== TABLES APP (résultat migration) ========'
SELECT 'factures_clients'         AS app, count(*) FROM factures_clients
UNION ALL SELECT 'reglements_clients',     count(*) FROM reglements_clients
UNION ALL SELECT 'clients',                count(*) FROM clients
UNION ALL SELECT 'approvisionnements_banques', count(*) FROM approvisionnements_banques
UNION ALL SELECT 'factures_fournisseurs',  count(*) FROM factures_fournisseurs
UNION ALL SELECT 'reglements_fournisseurs', count(*) FROM reglements_fournisseurs
UNION ALL SELECT 'fournisseurs',           count(*) FROM fournisseurs
UNION ALL SELECT 'imputations_facture_fournisseur', count(*) FROM imputations_facture_fournisseur
UNION ALL SELECT 'ecritures_comptables',   count(*) FROM ecritures_comptables
UNION ALL SELECT 'comptes_comptables',     count(*) FROM comptes_comptables;

\echo '======== CONTROLE ORPHELINS (factures clients sans client mappable) ========'
SELECT count(*) AS factures_clients_orphelines
FROM legacy_clients.facture f
WHERE f.reffac IS NOT NULL
  AND lower(trim(f.numcli)) NOT IN (SELECT lower(trim(numcli)) FROM legacy_clients.client WHERE numcli IS NOT NULL);
