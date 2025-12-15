-- ==========================================
-- PLAN COMPTABLE OHADA COMPLET
-- Organisation pour l'Harmonisation en Afrique du Droit des Affaires
-- ==========================================
-- Encodage: UTF-8
-- Format: PostgreSQL
-- Date de création: 2025-12-14
-- ==========================================

SET client_encoding = 'UTF-8';

-- Création de la table du plan comptable
DROP TABLE IF EXISTS plan_comptable_ohada CASCADE;

CREATE TABLE plan_comptable_ohada (
    id SERIAL PRIMARY KEY,
    numero_compte VARCHAR(10) NOT NULL UNIQUE,
    libelle VARCHAR(500) NOT NULL,
    classe INTEGER NOT NULL CHECK (classe BETWEEN 1 AND 9),
    niveau INTEGER NOT NULL CHECK (niveau BETWEEN 1 AND 5),
    type_compte VARCHAR(20) NOT NULL CHECK (type_compte IN ('ACTIF', 'PASSIF', 'CHARGE', 'PRODUIT', 'SPECIAL')),
    utilisable BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Index pour optimiser les recherches
CREATE INDEX idx_numero_compte ON plan_comptable_ohada(numero_compte);
CREATE INDEX idx_classe ON plan_comptable_ohada(classe);
CREATE INDEX idx_type_compte ON plan_comptable_ohada(type_compte);

-- ==========================================
-- CLASSE 1 : COMPTES DE RESSOURCES DURABLES
-- ==========================================

INSERT INTO plan_comptable_ohada (numero_compte, libelle, classe, niveau, type_compte) VALUES
-- Niveau 1
('10', 'CAPITAL', 1, 1, 'PASSIF'),
('11', 'RESERVES', 1, 1, 'PASSIF'),
('12', 'REPORT A NOUVEAU', 1, 1, 'PASSIF'),
('13', 'RESULTAT NET DE L''EXERCICE', 1, 1, 'PASSIF'),
('14', 'SUBVENTIONS D''INVESTISSEMENT', 1, 1, 'PASSIF'),
('15', 'PROVISIONS REGLEMENTEES ET FONDS ASSIMILES', 1, 1, 'PASSIF'),
('16', 'EMPRUNTS ET DETTES ASSIMILEES', 1, 1, 'PASSIF'),
('17', 'DETTES DE CREDIT-BAIL ET CONTRATS ASSIMILES', 1, 1, 'PASSIF'),
('18', 'DETTES LIEES A DES PARTICIPATIONS ET COMPTES DE LIAISON DES ETABLISSEMENTS', 1, 1, 'PASSIF'),
('19', 'PROVISIONS FINANCIERES POUR RISQUES ET CHARGES', 1, 1, 'PASSIF'),

-- Niveau 2 - Détail CAPITAL
('101', 'Capital social', 1, 2, 'PASSIF'),
('102', 'Capital par dotation', 1, 2, 'PASSIF'),
('103', 'Capital personnel', 1, 2, 'PASSIF'),
('104', 'Compte de l''exploitant', 1, 2, 'PASSIF'),
('105', 'Primes liées au capital social', 1, 2, 'PASSIF'),
('106', 'Ecarts de réévaluation', 1, 2, 'PASSIF'),
('109', 'Actionnaires, Capital souscrit non appelé', 1, 2, 'PASSIF'),

-- Niveau 3 - Détail Capital social
('1011', 'Capital souscrit, non appelé', 1, 3, 'PASSIF'),
('1012', 'Capital souscrit, appelé, non versé', 1, 3, 'PASSIF'),
('1013', 'Capital souscrit, appelé, versé, non amorti', 1, 3, 'PASSIF'),
('1014', 'Capital souscrit, appelé, versé, amorti', 1, 3, 'PASSIF'),
('1018', 'Capital souscrit soumis à des réglementations particulières', 1, 3, 'PASSIF'),

-- Niveau 2 - Détail RESERVES
('111', 'Réserve légale', 1, 2, 'PASSIF'),
('112', 'Réserves statutaires ou contractuelles', 1, 2, 'PASSIF'),
('113', 'Réserves réglementées', 1, 2, 'PASSIF'),
('118', 'Autres réserves', 1, 2, 'PASSIF'),

-- Niveau 3 - Détail Réserves
('1181', 'Réserves facultatives', 1, 3, 'PASSIF'),
('1182', 'Réserves de plus-values nettes à long terme', 1, 3, 'PASSIF'),
('1183', 'Réserves consécutives à l''octroi de subventions d''investissement', 1, 3, 'PASSIF'),
('1188', 'Réserves diverses', 1, 3, 'PASSIF'),

-- Niveau 2 - Détail REPORT A NOUVEAU
('121', 'Report à nouveau créditeur', 1, 2, 'PASSIF'),
('129', 'Report à nouveau débiteur', 1, 2, 'PASSIF'),

-- Niveau 2 - Détail RESULTAT NET
('130', 'Résultat en instance d''affectation', 1, 2, 'PASSIF'),
('131', 'Résultat net : Bénéfice', 1, 2, 'PASSIF'),
('132', 'Mises en paiement', 1, 2, 'PASSIF'),
('139', 'Résultat net : Perte', 1, 2, 'PASSIF'),

-- Niveau 3 - Détail Résultat
('1301', 'Résultat en instance d''affectation : Bénéfice', 1, 3, 'PASSIF'),
('1309', 'Résultat en instance d''affectation : Perte', 1, 3, 'PASSIF'),

-- Niveau 2 - Détail SUBVENTIONS D'INVESTISSEMENT
('141', 'Subventions d''équipement', 1, 2, 'PASSIF'),
('148', 'Autres subventions d''investissement', 1, 2, 'PASSIF'),

-- Niveau 3 - Détail Subventions
('1411', 'Etat', 1, 3, 'PASSIF'),
('1412', 'Régions', 1, 3, 'PASSIF'),
('1413', 'Départements', 1, 3, 'PASSIF'),
('1414', 'Communes et collectivités publiques décentralisées', 1, 3, 'PASSIF'),
('1415', 'Entreprises publiques ou mixtes', 1, 3, 'PASSIF'),
('1416', 'Entreprises et organismes privés', 1, 3, 'PASSIF'),
('1417', 'Organismes internationaux', 1, 3, 'PASSIF'),
('1418', 'Autres', 1, 3, 'PASSIF'),

-- Niveau 2 - Détail PROVISIONS REGLEMENTEES
('151', 'Amortissements dérogatoires', 1, 2, 'PASSIF'),
('152', 'Plus-values de cession à réinvestir', 1, 2, 'PASSIF'),
('153', 'Fonds réglementés', 1, 2, 'PASSIF'),
('154', 'Provision spéciale de réévaluation', 1, 2, 'PASSIF'),
('155', 'Provisions réglementées relatives aux immobilisations', 1, 2, 'PASSIF'),
('156', 'Provisions réglementées relatives aux stocks', 1, 2, 'PASSIF'),
('157', 'Provisions pour investissement', 1, 2, 'PASSIF'),
('158', 'Autres provisions et fonds réglementés', 1, 2, 'PASSIF'),

-- Niveau 2 - Détail EMPRUNTS ET DETTES
('161', 'Emprunts obligataires', 1, 2, 'PASSIF'),
('162', 'Emprunts et dettes auprès des établissements de crédit', 1, 2, 'PASSIF'),
('163', 'Avances reçues de l''Etat', 1, 2, 'PASSIF'),
('164', 'Avances reçues et comptes courants bloqués', 1, 2, 'PASSIF'),
('165', 'Dépôts et cautionnements reçus', 1, 2, 'PASSIF'),
('166', 'Intérêts courus', 1, 2, 'PASSIF'),
('167', 'Emprunts et dettes assorties de conditions particulières', 1, 2, 'PASSIF'),
('168', 'Autres emprunts et dettes', 1, 2, 'PASSIF'),
('169', 'Primes de remboursement des obligations', 1, 2, 'PASSIF'),

-- Niveau 3 - Détail Emprunts obligataires
('1611', 'Emprunts obligataires ordinaires', 1, 3, 'PASSIF'),
('1612', 'Emprunts obligataires convertibles', 1, 3, 'PASSIF'),
('1618', 'Autres emprunts obligataires', 1, 3, 'PASSIF'),

-- Niveau 3 - Détail Emprunts établissements de crédit
('1621', 'Crédits de mobilisation de créances commerciales', 1, 3, 'PASSIF'),
('1622', 'Crédits de mobilisation de créances nées à l''étranger', 1, 3, 'PASSIF'),
('1623', 'Crédits de mobilisation de créances nées sur l''Etat', 1, 3, 'PASSIF'),
('1624', 'Crédits de financement d''actifs mobilisés', 1, 3, 'PASSIF'),
('1625', 'Billets de fonds', 1, 3, 'PASSIF'),
('1626', 'Emprunts et dettes contractés auprès des établissements de crédit', 1, 3, 'PASSIF'),
('1627', 'Crédit-bail mobilier', 1, 3, 'PASSIF'),
('1628', 'Crédit-bail immobilier', 1, 3, 'PASSIF'),

-- Niveau 3 - Détail Intérêts courus
('1661', 'Intérêts courus sur emprunts obligataires', 1, 3, 'PASSIF'),
('1662', 'Intérêts courus sur emprunts auprès des établissements de crédit', 1, 3, 'PASSIF'),
('1663', 'Intérêts courus sur avances reçues de l''Etat', 1, 3, 'PASSIF'),
('1664', 'Intérêts courus sur avances reçues et comptes courants bloqués', 1, 3, 'PASSIF'),
('1665', 'Intérêts courus sur dépôts et cautionnements reçus', 1, 3, 'PASSIF'),
('1667', 'Intérêts courus sur emprunts et dettes assorties de conditions particulières', 1, 3, 'PASSIF'),
('1668', 'Intérêts courus sur autres emprunts et dettes', 1, 3, 'PASSIF'),

-- Niveau 2 - Détail DETTES DE CREDIT-BAIL
('172', 'Dettes de crédit-bail immobilier', 1, 2, 'PASSIF'),
('173', 'Dettes de crédit-bail mobilier', 1, 2, 'PASSIF'),
('176', 'Intérêts courus', 1, 2, 'PASSIF'),
('178', 'Dettes sur autres contrats', 1, 2, 'PASSIF'),

-- Niveau 2 - Détail DETTES LIEES A PARTICIPATIONS
('181', 'Dettes liées à des participations', 1, 2, 'PASSIF'),
('182', 'Dettes liées à des sociétés en participation', 1, 2, 'PASSIF'),
('183', 'Intérêts courus', 1, 2, 'PASSIF'),
('184', 'Comptes permanents bloqués', 1, 2, 'PASSIF'),
('185', 'Comptes permanents non bloqués', 1, 2, 'PASSIF'),
('186', 'Comptes de liaison des établissements et succursales', 1, 2, 'PASSIF'),
('187', 'Comptes de liaison des sociétés en participation', 1, 2, 'PASSIF'),
('188', 'Comptes de liaison charges', 1, 2, 'PASSIF'),

-- Niveau 3 - Détail Dettes liées à participations
('1811', 'Dettes liées à des participations (groupe)', 1, 3, 'PASSIF'),
('1812', 'Dettes liées à des participations (hors groupe)', 1, 3, 'PASSIF'),

-- Niveau 2 - Détail PROVISIONS FINANCIERES
('191', 'Provisions pour litiges', 1, 2, 'PASSIF'),
('192', 'Provisions pour garanties données aux clients', 1, 2, 'PASSIF'),
('193', 'Provisions pour pertes sur marchés à achèvement futur', 1, 2, 'PASSIF'),
('194', 'Provisions pour pertes de change', 1, 2, 'PASSIF'),
('195', 'Provisions pour impôts', 1, 2, 'PASSIF'),
('196', 'Provisions pour pensions et obligations assimilées', 1, 2, 'PASSIF'),
('197', 'Provisions pour charges à répartir sur plusieurs exercices', 1, 2, 'PASSIF'),
('198', 'Autres provisions financières pour risques et charges', 1, 2, 'PASSIF');


-- ==========================================
-- CLASSE 2 : COMPTES D'ACTIF IMMOBILISE
-- ==========================================

INSERT INTO plan_comptable_ohada (numero_compte, libelle, classe, niveau, type_compte) VALUES
-- Niveau 1
('20', 'CHARGES IMMOBILISEES', 2, 1, 'ACTIF'),
('21', 'IMMOBILISATIONS INCORPORELLES', 2, 1, 'ACTIF'),
('22', 'TERRAINS', 2, 1, 'ACTIF'),
('23', 'BATIMENTS, INSTALLATIONS TECHNIQUES ET AGENCEMENTS', 2, 1, 'ACTIF'),
('24', 'MATERIEL', 2, 1, 'ACTIF'),
('25', 'AVANCES ET ACOMPTES VERSES SUR IMMOBILISATIONS', 2, 1, 'ACTIF'),
('26', 'TITRES DE PARTICIPATION', 2, 1, 'ACTIF'),
('27', 'AUTRES IMMOBILISATIONS FINANCIERES', 2, 1, 'ACTIF'),
('28', 'AMORTISSEMENTS', 2, 1, 'ACTIF'),
('29', 'PROVISIONS POUR DEPRECIATION DES IMMOBILISATIONS', 2, 1, 'ACTIF'),

-- Niveau 2 - Détail CHARGES IMMOBILISEES
('201', 'Frais d''établissement', 2, 2, 'ACTIF'),
('202', 'Charges à répartir sur plusieurs exercices', 2, 2, 'ACTIF'),
('206', 'Primes de remboursement des obligations', 2, 2, 'ACTIF'),
('208', 'Autres charges immobilisées', 2, 2, 'ACTIF'),

-- Niveau 3 - Détail Frais d'établissement
('2011', 'Frais de constitution', 2, 3, 'ACTIF'),
('2012', 'Frais de prospection', 2, 3, 'ACTIF'),
('2013', 'Frais de publicité', 2, 3, 'ACTIF'),
('2014', 'Frais de restructuration', 2, 3, 'ACTIF'),
('2015', 'Frais de fonctionnement antérieurs au démarrage', 2, 3, 'ACTIF'),
('2016', 'Frais de modification du capital', 2, 3, 'ACTIF'),
('2018', 'Autres frais d''établissement', 2, 3, 'ACTIF'),

-- Niveau 2 - Détail IMMOBILISATIONS INCORPORELLES
('211', 'Frais de recherche et de développement', 2, 2, 'ACTIF'),
('212', 'Brevets, licences, logiciels et droits similaires', 2, 2, 'ACTIF'),
('213', 'Concessions et droits similaires, brevets, licences, marques', 2, 2, 'ACTIF'),
('214', 'Fonds commercial et droit au bail', 2, 2, 'ACTIF'),
('218', 'Autres droits et valeurs incorporels', 2, 2, 'ACTIF'),

-- Niveau 3 - Détail Brevets, licences
('2121', 'Brevets', 2, 3, 'ACTIF'),
('2122', 'Licences', 2, 3, 'ACTIF'),
('2123', 'Logiciels', 2, 3, 'ACTIF'),
('2128', 'Autres droits', 2, 3, 'ACTIF'),

-- Niveau 2 - Détail TERRAINS
('221', 'Terrains agricoles et forestiers', 2, 2, 'ACTIF'),
('222', 'Terrains nus', 2, 2, 'ACTIF'),
('223', 'Terrains bâtis', 2, 2, 'ACTIF'),
('224', 'Travaux de mise en valeur des terrains', 2, 2, 'ACTIF'),
('228', 'Autres terrains', 2, 2, 'ACTIF'),
('229', 'Aménagements de terrains', 2, 2, 'ACTIF'),

-- Niveau 2 - Détail BATIMENTS
('231', 'Bâtiments industriels, agricoles, administratifs et commerciaux sur sol propre', 2, 2, 'ACTIF'),
('232', 'Bâtiments industriels, agricoles, administratifs et commerciaux sur sol d''autrui', 2, 2, 'ACTIF'),
('233', 'Ouvrages d''infrastructure', 2, 2, 'ACTIF'),
('234', 'Installations techniques', 2, 2, 'ACTIF'),
('235', 'Aménagements de bureaux et locaux', 2, 2, 'ACTIF'),
('237', 'Bâtiments industriels, agricoles et commerciaux mis en concession', 2, 2, 'ACTIF'),
('238', 'Autres installations et agencements', 2, 2, 'ACTIF'),
('239', 'Bâtiments en cours', 2, 2, 'ACTIF'),

-- Niveau 2 - Détail MATERIEL
('241', 'Matériel et outillage industriel et commercial', 2, 2, 'ACTIF'),
('242', 'Matériel et outillage agricole', 2, 2, 'ACTIF'),
('243', 'Matériel d''emballage récupérable et identifiable', 2, 2, 'ACTIF'),
('244', 'Matériel et mobilier', 2, 2, 'ACTIF'),
('245', 'Matériel de transport', 2, 2, 'ACTIF'),
('246', 'Immobilisations animales et agricoles', 2, 2, 'ACTIF'),
('248', 'Autres matériels', 2, 2, 'ACTIF'),

-- Niveau 3 - Détail Matériel et mobilier
('2441', 'Mobilier de bureau', 2, 3, 'ACTIF'),
('2442', 'Matériel de bureau', 2, 3, 'ACTIF'),
('2443', 'Matériel informatique', 2, 3, 'ACTIF'),
('2444', 'Matériel et mobilier hôtelier', 2, 3, 'ACTIF'),
('2445', 'Matériel et mobilier de logement', 2, 3, 'ACTIF'),
('2446', 'Équipements sociaux', 2, 3, 'ACTIF'),
('2448', 'Autres matériels et mobiliers', 2, 3, 'ACTIF'),

-- Niveau 2 - Détail AVANCES ET ACOMPTES
('251', 'Avances et acomptes versés sur immobilisations incorporelles', 2, 2, 'ACTIF'),
('252', 'Avances et acomptes versés sur immobilisations corporelles', 2, 2, 'ACTIF'),
('257', 'Avances et acomptes versés sur immobilisations financières', 2, 2, 'ACTIF'),

-- Niveau 2 - Détail TITRES DE PARTICIPATION
('261', 'Titres de participation dans des sociétés sous contrôle exclusif', 2, 2, 'ACTIF'),
('262', 'Titres de participation dans des sociétés sous contrôle conjoint', 2, 2, 'ACTIF'),
('263', 'Titres de participation dans des sociétés conférant une influence notable', 2, 2, 'ACTIF'),
('264', 'Autres titres de participation', 2, 2, 'ACTIF'),
('265', 'Parts dans des G.I.E.', 2, 2, 'ACTIF'),
('266', 'Titres de participation évalués par équivalence', 2, 2, 'ACTIF'),
('268', 'Créances rattachées à des participations', 2, 2, 'ACTIF'),
('269', 'Versements restant à effectuer sur titres de participation non libérés', 2, 2, 'ACTIF'),

-- Niveau 2 - Détail AUTRES IMMOBILISATIONS FINANCIERES
('271', 'Prêts et créances non commerciales', 2, 2, 'ACTIF'),
('272', 'Prêts au personnel', 2, 2, 'ACTIF'),
('273', 'Créances sur l''Etat', 2, 2, 'ACTIF'),
('274', 'Titres immobilisés', 2, 2, 'ACTIF'),
('275', 'Dépôts et cautionnements versés', 2, 2, 'ACTIF'),
('276', 'Intérêts courus', 2, 2, 'ACTIF'),
('277', 'Créances immobilisées', 2, 2, 'ACTIF'),
('278', 'Créances financières diverses', 2, 2, 'ACTIF'),

-- Niveau 3 - Détail Titres immobilisés
('2741', 'Titres immobilisés de l''activité de portefeuille (T.I.A.P.)', 2, 3, 'ACTIF'),
('2742', 'Titres immobilisés représentatifs de droits de créance', 2, 3, 'ACTIF'),
('2743', 'Titres immobilisés de l''activité de portefeuille dans des sociétés sous contrôle exclusif', 2, 3, 'ACTIF'),
('2748', 'Autres titres immobilisés', 2, 3, 'ACTIF'),

-- Niveau 2 - Détail AMORTISSEMENTS
('281', 'Amortissements des immobilisations incorporelles', 2, 2, 'ACTIF'),
('282', 'Amortissements des terrains', 2, 2, 'ACTIF'),
('283', 'Amortissements des bâtiments, installations techniques et agencements', 2, 2, 'ACTIF'),
('284', 'Amortissements du matériel', 2, 2, 'ACTIF'),

-- Niveau 3 - Détail Amortissements immobilisations incorporelles
('2811', 'Amortissements des frais de recherche et de développement', 2, 3, 'ACTIF'),
('2812', 'Amortissements des brevets, licences, logiciels', 2, 3, 'ACTIF'),
('2813', 'Amortissements des concessions et droits similaires', 2, 3, 'ACTIF'),
('2814', 'Amortissements du fonds commercial', 2, 3, 'ACTIF'),
('2818', 'Amortissements des autres droits et valeurs incorporels', 2, 3, 'ACTIF'),

-- Niveau 2 - Détail PROVISIONS POUR DEPRECIATION
('291', 'Provisions pour dépréciation des immobilisations incorporelles', 2, 2, 'ACTIF'),
('292', 'Provisions pour dépréciation des terrains', 2, 2, 'ACTIF'),
('293', 'Provisions pour dépréciation des bâtiments', 2, 2, 'ACTIF'),
('294', 'Provisions pour dépréciation du matériel', 2, 2, 'ACTIF'),
('295', 'Provisions pour dépréciation des avances et acomptes versés sur immobilisations', 2, 2, 'ACTIF'),
('296', 'Provisions pour dépréciation des titres de participation', 2, 2, 'ACTIF'),
('297', 'Provisions pour dépréciation des autres immobilisations financières', 2, 2, 'ACTIF');


-- ==========================================
-- CLASSE 3 : COMPTES DE STOCKS
-- ==========================================

INSERT INTO plan_comptable_ohada (numero_compte, libelle, classe, niveau, type_compte) VALUES
-- Niveau 1
('31', 'MARCHANDISES', 3, 1, 'ACTIF'),
('32', 'MATIERES PREMIERES ET FOURNITURES LIEES', 3, 1, 'ACTIF'),
('33', 'AUTRES APPROVISIONNEMENTS', 3, 1, 'ACTIF'),
('34', 'PRODUITS EN COURS', 3, 1, 'ACTIF'),
('35', 'SERVICES EN COURS', 3, 1, 'ACTIF'),
('36', 'PRODUITS FINIS', 3, 1, 'ACTIF'),
('37', 'PRODUITS INTERMEDIAIRES ET RESIDUELS', 3, 1, 'ACTIF'),
('38', 'STOCKS EN COURS DE ROUTE, EN CONSIGNATION OU EN DEPOT', 3, 1, 'ACTIF'),
('39', 'DEPRECIATION DES STOCKS', 3, 1, 'ACTIF'),

-- Niveau 2 - Détail MARCHANDISES
('311', 'Marchandises A', 3, 2, 'ACTIF'),
('312', 'Marchandises B', 3, 2, 'ACTIF'),
('318', 'Autres marchandises', 3, 2, 'ACTIF'),

-- Niveau 2 - Détail MATIERES PREMIERES
('321', 'Matières A', 3, 2, 'ACTIF'),
('322', 'Matières B', 3, 2, 'ACTIF'),
('323', 'Fournitures', 3, 2, 'ACTIF'),
('328', 'Autres matières et fournitures', 3, 2, 'ACTIF'),

-- Niveau 2 - Détail AUTRES APPROVISIONNEMENTS
('331', 'Matières consommables', 3, 2, 'ACTIF'),
('332', 'Fournitures d''atelier et d''usine', 3, 2, 'ACTIF'),
('333', 'Fournitures de magasin', 3, 2, 'ACTIF'),
('334', 'Fournitures de bureau', 3, 2, 'ACTIF'),
('335', 'Emballages', 3, 2, 'ACTIF'),
('338', 'Autres matières consommables', 3, 2, 'ACTIF'),

-- Niveau 3 - Détail Emballages
('3351', 'Emballages perdus', 3, 3, 'ACTIF'),
('3352', 'Emballages récupérables non identifiables', 3, 3, 'ACTIF'),
('3353', 'Emballages à usage mixte', 3, 3, 'ACTIF'),
('3358', 'Autres emballages', 3, 3, 'ACTIF'),

-- Niveau 2 - Détail PRODUITS EN COURS
('341', 'Produits en cours P 1', 3, 2, 'ACTIF'),
('342', 'Produits en cours P 2', 3, 2, 'ACTIF'),
('348', 'Autres produits en cours', 3, 2, 'ACTIF'),

-- Niveau 2 - Détail SERVICES EN COURS
('351', 'Etudes en cours', 3, 2, 'ACTIF'),
('352', 'Prestations de services en cours', 3, 2, 'ACTIF'),
('358', 'Autres services en cours', 3, 2, 'ACTIF'),

-- Niveau 2 - Détail PRODUITS FINIS
('361', 'Produits finis A', 3, 2, 'ACTIF'),
('362', 'Produits finis B', 3, 2, 'ACTIF'),
('368', 'Autres produits finis', 3, 2, 'ACTIF'),

-- Niveau 2 - Détail PRODUITS INTERMEDIAIRES
('371', 'Produits intermédiaires A', 3, 2, 'ACTIF'),
('372', 'Produits intermédiaires B', 3, 2, 'ACTIF'),
('373', 'Produits résiduels', 3, 2, 'ACTIF'),
('378', 'Autres produits intermédiaires', 3, 2, 'ACTIF'),

-- Niveau 3 - Détail Produits résiduels
('3731', 'Déchets', 3, 3, 'ACTIF'),
('3732', 'Rebuts', 3, 3, 'ACTIF'),
('3733', 'Matières de récupération', 3, 3, 'ACTIF'),

-- Niveau 2 - Détail STOCKS EN TRANSIT
('381', 'Marchandises en cours de route', 3, 2, 'ACTIF'),
('382', 'Matières premières et fournitures en cours de route', 3, 2, 'ACTIF'),
('383', 'Autres approvisionnements en cours de route', 3, 2, 'ACTIF'),
('386', 'Produits finis en cours de route', 3, 2, 'ACTIF'),
('387', 'Stock en consignation ou en dépôt', 3, 2, 'ACTIF'),
('388', 'Stock en cours de route ou en dépôt', 3, 2, 'ACTIF'),

-- Niveau 2 - Détail DEPRECIATION DES STOCKS
('391', 'Dépréciation des stocks de marchandises', 3, 2, 'ACTIF'),
('392', 'Dépréciation des stocks de matières premières et fournitures liées', 3, 2, 'ACTIF'),
('393', 'Dépréciation des stocks d''autres approvisionnements', 3, 2, 'ACTIF'),
('394', 'Dépréciation des stocks de produits en cours', 3, 2, 'ACTIF'),
('395', 'Dépréciation des stocks de services en cours', 3, 2, 'ACTIF'),
('396', 'Dépréciation des stocks de produits finis', 3, 2, 'ACTIF'),
('397', 'Dépréciation des stocks de produits intermédiaires et résiduels', 3, 2, 'ACTIF'),
('398', 'Dépréciation des stocks en cours de route, en consignation ou en dépôt', 3, 2, 'ACTIF');


-- ==========================================
-- CLASSE 4 : COMPTES DE TIERS
-- ==========================================

INSERT INTO plan_comptable_ohada (numero_compte, libelle, classe, niveau, type_compte) VALUES
-- Niveau 1
('40', 'FOURNISSEURS ET COMPTES RATTACHES', 4, 1, 'PASSIF'),
('41', 'CLIENTS ET COMPTES RATTACHES', 4, 1, 'ACTIF'),
('42', 'PERSONNEL', 4, 1, 'PASSIF'),
('43', 'ORGANISMES SOCIAUX', 4, 1, 'PASSIF'),
('44', 'ETAT ET COLLECTIVITES PUBLIQUES', 4, 1, 'SPECIAL'),
('45', 'ORGANISMES INTERNATIONAUX', 4, 1, 'SPECIAL'),
('46', 'ASSOCIES ET GROUPE', 4, 1, 'SPECIAL'),
('47', 'DEBITEURS ET CREDITEURS DIVERS', 4, 1, 'SPECIAL'),
('48', 'CREANCES ET DETTES HORS ACTIVITES ORDINAIRES (H.A.O.)', 4, 1, 'SPECIAL'),
('49', 'DEPRECIATION ET RISQUES PROVISIONNES', 4, 1, 'SPECIAL'),

-- Niveau 2 - Détail FOURNISSEURS
('401', 'Fournisseurs d''exploitation', 4, 2, 'PASSIF'),
('402', 'Fournisseurs d''exploitation - Effets à payer', 4, 2, 'PASSIF'),
('403', 'Fournisseurs d''exploitation - Retenues de garantie', 4, 2, 'PASSIF'),
('404', 'Fournisseurs d''immobilisations', 4, 2, 'PASSIF'),
('405', 'Fournisseurs d''immobilisations - Effets à payer', 4, 2, 'PASSIF'),
('408', 'Fournisseurs - Factures non parvenues', 4, 2, 'PASSIF'),
('409', 'Fournisseurs débiteurs', 4, 2, 'ACTIF'),

-- Niveau 3 - Détail Fournisseurs débiteurs
('4091', 'Fournisseurs - Avances et acomptes versés', 4, 3, 'ACTIF'),
('4092', 'Fournisseurs - Créances pour emballages et matériel consignés', 4, 3, 'ACTIF'),
('4098', 'Autres fournisseurs débiteurs', 4, 3, 'ACTIF'),

-- Niveau 2 - Détail CLIENTS
('411', 'Clients', 4, 2, 'ACTIF'),
('412', 'Clients - Effets à recevoir', 4, 2, 'ACTIF'),
('413', 'Clients - Retenues de garantie', 4, 2, 'ACTIF'),
('414', 'Clients douteux', 4, 2, 'ACTIF'),
('415', 'Clients - Effets escomptés non échus', 4, 2, 'ACTIF'),
('416', 'Créances clients litigieuses', 4, 2, 'ACTIF'),
('417', 'Créances sur travaux non encore facturables', 4, 2, 'ACTIF'),
('418', 'Clients - Produits à recevoir', 4, 2, 'ACTIF'),
('419', 'Clients créditeurs', 4, 2, 'PASSIF'),

-- Niveau 3 - Détail Clients créditeurs
('4191', 'Clients - Avances et acomptes reçus', 4, 3, 'PASSIF'),
('4192', 'Clients - Dettes sur emballages et matériel consignés', 4, 3, 'PASSIF'),
('4198', 'Autres clients créditeurs', 4, 3, 'PASSIF'),

-- Niveau 2 - Détail PERSONNEL
('421', 'Personnel - Avances et acomptes', 4, 2, 'ACTIF'),
('422', 'Personnel - Rémunérations dues', 4, 2, 'PASSIF'),
('423', 'Personnel - Oppositions', 4, 2, 'PASSIF'),
('424', 'Personnel - Œuvres sociales', 4, 2, 'PASSIF'),
('425', 'Personnel - Dépôts', 4, 2, 'PASSIF'),
('426', 'Personnel - Participation aux résultats', 4, 2, 'PASSIF'),
('427', 'Personnel - Charges à payer et produits à recevoir', 4, 2, 'SPECIAL'),
('428', 'Personnel - Charges à payer et produits à recevoir', 4, 2, 'SPECIAL'),

-- Niveau 2 - Détail ORGANISMES SOCIAUX
('431', 'Sécurité sociale', 4, 2, 'PASSIF'),
('432', 'Caisse de retraite obligatoire', 4, 2, 'PASSIF'),
('433', 'Caisse de retraite facultative', 4, 2, 'PASSIF'),
('434', 'Autres organismes sociaux', 4, 2, 'PASSIF'),
('438', 'Organismes sociaux - Charges à payer et produits à recevoir', 4, 2, 'SPECIAL'),

-- Niveau 2 - Détail ETAT
('441', 'Etat - Subventions à recevoir', 4, 2, 'ACTIF'),
('442', 'Etat - Impôts et taxes recouvrables sur des tiers', 4, 2, 'ACTIF'),
('443', 'Opérations particulières avec l''Etat', 4, 2, 'SPECIAL'),
('444', 'Etat - Impôts sur les résultats', 4, 2, 'PASSIF'),
('445', 'Etat - Taxes sur le chiffre d''affaires', 4, 2, 'SPECIAL'),
('446', 'Etat - Autres impôts et taxes', 4, 2, 'PASSIF'),
('447', 'Etat - Charges à payer et produits à recevoir', 4, 2, 'SPECIAL'),
('448', 'Etat - Charges à payer et produits à recevoir', 4, 2, 'SPECIAL'),
('449', 'Etat - Créances et dettes diverses', 4, 2, 'SPECIAL'),

-- Niveau 3 - Détail TVA
('4451', 'Etat - TVA récupérable sur immobilisations', 4, 3, 'ACTIF'),
('4452', 'Etat - TVA récupérable sur achats', 4, 3, 'ACTIF'),
('4455', 'Etat - TVA à décaisser', 4, 3, 'PASSIF'),
('4456', 'Etat - TVA sur factures non parvenues', 4, 3, 'ACTIF'),
('4457', 'Etat - Crédit de TVA à reporter', 4, 3, 'ACTIF'),
('4458', 'Etat - Taxes sur le chiffre d''affaires à régulariser', 4, 3, 'SPECIAL'),

-- Niveau 2 - Détail ORGANISMES INTERNATIONAUX
('451', 'Organismes internationaux - Subventions à recevoir', 4, 2, 'ACTIF'),
('452', 'Organismes internationaux - Autres comptes débiteurs', 4, 2, 'ACTIF'),
('456', 'Organismes internationaux - Autres comptes créditeurs', 4, 2, 'PASSIF'),
('458', 'Organismes internationaux - Charges à payer et produits à recevoir', 4, 2, 'SPECIAL'),

-- Niveau 2 - Détail ASSOCIES ET GROUPE
('461', 'Associés - Capital à rembourser', 4, 2, 'PASSIF'),
('462', 'Associés - Comptes courants', 4, 2, 'SPECIAL'),
('463', 'Associés - Versements reçus sur augmentation de capital', 4, 2, 'PASSIF'),
('464', 'Associés - Capital appelé, non versé', 4, 2, 'ACTIF'),
('465', 'Associés - Versements anticipés', 4, 2, 'PASSIF'),
('466', 'Groupe', 4, 2, 'SPECIAL'),
('467', 'Créances et dettes sur cessions d''immobilisations', 4, 2, 'SPECIAL'),
('468', 'Autres comptes d''associés ou groupe', 4, 2, 'SPECIAL'),

-- Niveau 3 - Détail Associés
('4641', 'Actionnaires - Capital souscrit et appelé, non versé', 4, 3, 'ACTIF'),
('4642', 'Commanditaires - Capital appelé, non versé', 4, 3, 'ACTIF'),
('4643', 'Associés personnes physiques - Capital appelé, non versé', 4, 3, 'ACTIF'),

-- Niveau 2 - Détail DEBITEURS ET CREDITEURS DIVERS
('471', 'Compte d''attente', 4, 2, 'SPECIAL'),
('472', 'Versements restant à effectuer sur titres non libérés', 4, 2, 'PASSIF'),
('473', 'Versements restant à effectuer sur titres de participation non libérés', 4, 2, 'PASSIF'),
('474', 'Débiteurs et créditeurs divers', 4, 2, 'SPECIAL'),
('475', 'Créances sur cessions d''immobilisations', 4, 2, 'ACTIF'),
('476', 'Dettes sur acquisitions de titres', 4, 2, 'PASSIF'),
('477', 'Différence de conversion - Actif', 4, 2, 'ACTIF'),
('478', 'Différence de conversion - Passif', 4, 2, 'PASSIF'),

-- Niveau 2 - Détail H.A.O.
('481', 'Charges à payer H.A.O.', 4, 2, 'PASSIF'),
('482', 'Charges à payer H.A.O. sur créances', 4, 2, 'PASSIF'),
('483', 'Charges à payer H.A.O. sur dettes', 4, 2, 'PASSIF'),
('484', 'Produits à recevoir H.A.O.', 4, 2, 'ACTIF'),
('485', 'Créances sur cessions d''immobilisations H.A.O.', 4, 2, 'ACTIF'),
('486', 'Dettes sur acquisitions d''immobilisations H.A.O.', 4, 2, 'PASSIF'),

-- Niveau 2 - Détail DEPRECIATIONS
('491', 'Dépréciation des comptes de fournisseurs', 4, 2, 'SPECIAL'),
('492', 'Dépréciation des comptes clients', 4, 2, 'SPECIAL'),
('493', 'Dépréciation des comptes de personnel', 4, 2, 'SPECIAL'),
('494', 'Dépréciation des comptes d''organismes sociaux', 4, 2, 'SPECIAL'),
('495', 'Dépréciation des comptes d''Etat', 4, 2, 'SPECIAL'),
('496', 'Dépréciation des comptes d''associés et groupe', 4, 2, 'SPECIAL'),
('497', 'Dépréciation des comptes de débiteurs et créditeurs divers', 4, 2, 'SPECIAL'),
('498', 'Dépréciation des comptes de créances H.A.O.', 4, 2, 'SPECIAL');


-- ==========================================
-- CLASSE 5 : COMPTES DE TRESORERIE
-- ==========================================

INSERT INTO plan_comptable_ohada (numero_compte, libelle, classe, niveau, type_compte) VALUES
-- Niveau 1
('50', 'TITRES DE PLACEMENT', 5, 1, 'ACTIF'),
('51', 'VALEURS A ENCAISSER', 5, 1, 'ACTIF'),
('52', 'BANQUES', 5, 1, 'ACTIF'),
('53', 'ETABLISSEMENTS FINANCIERS ET ASSIMILES', 5, 1, 'ACTIF'),
('54', 'INSTRUMENTS DE TRESORERIE', 5, 1, 'ACTIF'),
('56', 'BANQUES, CREDITS DE TRESORERIE ET D''ESCOMPTE', 5, 1, 'PASSIF'),
('57', 'CAISSE', 5, 1, 'ACTIF'),
('58', 'REGIES D''AVANCES, ACCREDITIFS ET VIREMENTS INTERNES', 5, 1, 'SPECIAL'),
('59', 'DEPRECIATION ET RISQUES PROVISIONNES', 5, 1, 'SPECIAL'),

-- Niveau 2 - Détail TITRES DE PLACEMENT
('501', 'Actions, partie libérée', 5, 2, 'ACTIF'),
('502', 'Actions, partie non libérée', 5, 2, 'ACTIF'),
('503', 'Obligations', 5, 2, 'ACTIF'),
('504', 'Bons du Trésor et bons de caisse à court terme', 5, 2, 'ACTIF'),
('505', 'Bons de caisse à moyen et long terme', 5, 2, 'ACTIF'),
('506', 'Obligations cautionnées', 5, 2, 'ACTIF'),
('507', 'Bons d''épargne', 5, 2, 'ACTIF'),
('508', 'Autres titres de placement', 5, 2, 'ACTIF'),

-- Niveau 2 - Détail VALEURS A ENCAISSER
('511', 'Coupons échus à l''encaissement', 5, 2, 'ACTIF'),
('512', 'Chèques à encaisser', 5, 2, 'ACTIF'),
('513', 'Effets à l''encaissement', 5, 2, 'ACTIF'),
('514', 'Effets à l''escompte', 5, 2, 'ACTIF'),
('515', 'Cartes bancaires à l''encaissement', 5, 2, 'ACTIF'),

-- Niveau 2 - Détail BANQUES
('521', 'Banques locales', 5, 2, 'ACTIF'),
('522', 'Banques autres Etats de la région', 5, 2, 'ACTIF'),
('523', 'Banques autres Etats de la zone monétaire', 5, 2, 'ACTIF'),
('524', 'Banques hors zone monétaire', 5, 2, 'ACTIF'),

-- Niveau 2 - Détail ETABLISSEMENTS FINANCIERS
('531', 'Trésor', 5, 2, 'ACTIF'),
('532', 'Chèques postaux', 5, 2, 'ACTIF'),
('533', 'Caisse d''épargne', 5, 2, 'ACTIF'),
('536', 'Etablissements financiers', 5, 2, 'ACTIF'),

-- Niveau 2 - Détail INSTRUMENTS DE TRESORERIE
('541', 'Opérations d''escompte de crédits de trésorerie', 5, 2, 'ACTIF'),

-- Niveau 2 - Détail BANQUES CREDIT
('561', 'Crédit de trésorerie', 5, 2, 'PASSIF'),
('562', 'Crédit d''escompte', 5, 2, 'PASSIF'),
('563', 'Intérêts courus sur crédits de trésorerie et d''escompte', 5, 2, 'PASSIF'),
('564', 'Banques - Intérêts courus', 5, 2, 'PASSIF'),
('565', 'Escompte de crédits de trésorerie', 5, 2, 'PASSIF'),
('566', 'Intérêts courus sur escomptes de crédits de trésorerie', 5, 2, 'PASSIF'),

-- Niveau 2 - Détail CAISSE
('571', 'Caisse siège social', 5, 2, 'ACTIF'),
('572', 'Caisse succursale (ou agence) A', 5, 2, 'ACTIF'),
('573', 'Caisse succursale (ou agence) B', 5, 2, 'ACTIF'),
('578', 'Autres caisses', 5, 2, 'ACTIF'),

-- Niveau 2 - Détail REGIES
('581', 'Régies d''avances et accréditifs', 5, 2, 'SPECIAL'),
('582', 'Virements de fonds', 5, 2, 'SPECIAL'),
('588', 'Virements internes', 5, 2, 'SPECIAL'),

-- Niveau 2 - Détail DEPRECIATIONS
('590', 'Provisions pour dépréciation des titres de placement', 5, 2, 'SPECIAL'),
('599', 'Risques provisionnés sur opérations de trésorerie', 5, 2, 'SPECIAL');


-- ==========================================
-- CLASSE 6 : COMPTES DE CHARGES DES ACTIVITES ORDINAIRES
-- ==========================================

INSERT INTO plan_comptable_ohada (numero_compte, libelle, classe, niveau, type_compte) VALUES
-- Niveau 1
('60', 'ACHATS ET VARIATIONS DE STOCKS', 6, 1, 'CHARGE'),
('61', 'TRANSPORTS', 6, 1, 'CHARGE'),
('62', 'SERVICES EXTERIEURS A', 6, 1, 'CHARGE'),
('63', 'SERVICES EXTERIEURS B', 6, 1, 'CHARGE'),
('64', 'IMPOTS ET TAXES', 6, 1, 'CHARGE'),
('65', 'AUTRES CHARGES', 6, 1, 'CHARGE'),
('66', 'CHARGES DE PERSONNEL', 6, 1, 'CHARGE'),
('67', 'FRAIS FINANCIERS ET CHARGES ASSIMILEES', 6, 1, 'CHARGE'),
('68', 'DOTATIONS AUX AMORTISSEMENTS', 6, 1, 'CHARGE'),
('69', 'DOTATIONS AUX PROVISIONS', 6, 1, 'CHARGE'),

-- Niveau 2 - Détail ACHATS
('601', 'Achats de marchandises', 6, 2, 'CHARGE'),
('602', 'Achats de matières premières et fournitures liées', 6, 2, 'CHARGE'),
('603', 'Variations des stocks de biens achetés', 6, 2, 'CHARGE'),
('604', 'Achats stockés de matières et fournitures consommables', 6, 2, 'CHARGE'),
('605', 'Autres achats', 6, 2, 'CHARGE'),
('608', 'Achats d''emballages', 6, 2, 'CHARGE'),

-- Niveau 3 - Détail Achats de marchandises
('6011', 'Achats de marchandises groupe A', 6, 3, 'CHARGE'),
('6012', 'Achats de marchandises groupe B', 6, 3, 'CHARGE'),
('6018', 'Achats de marchandises autres', 6, 3, 'CHARGE'),
('6019', 'Rabais, remises et ristournes obtenus sur achats de marchandises', 6, 3, 'CHARGE'),

-- Niveau 3 - Détail Matières premières
('6021', 'Matières A', 6, 3, 'CHARGE'),
('6022', 'Matières B', 6, 3, 'CHARGE'),
('6023', 'Fournitures', 6, 3, 'CHARGE'),
('6028', 'Autres matières et fournitures', 6, 3, 'CHARGE'),
('6029', 'Rabais, remises et ristournes obtenus sur achats de matières premières', 6, 3, 'CHARGE'),

-- Niveau 3 - Détail Variations de stocks
('6031', 'Variation des stocks de marchandises', 6, 3, 'CHARGE'),
('6032', 'Variation des stocks de matières premières et fournitures liées', 6, 3, 'CHARGE'),
('6033', 'Variation des stocks d''autres approvisionnements', 6, 3, 'CHARGE'),

-- Niveau 3 - Détail Achats stockés
('6041', 'Matières consommables', 6, 3, 'CHARGE'),
('6042', 'Fournitures d''atelier et d''usine', 6, 3, 'CHARGE'),
('6043', 'Fournitures de magasin', 6, 3, 'CHARGE'),
('6044', 'Fournitures de bureau', 6, 3, 'CHARGE'),
('6048', 'Autres matières et fournitures consommables', 6, 3, 'CHARGE'),
('6049', 'Rabais, remises et ristournes obtenus sur achats stockés', 6, 3, 'CHARGE'),

-- Niveau 3 - Détail Autres achats
('6051', 'Fournitures non stockées', 6, 3, 'CHARGE'),
('6052', 'Fournitures d''entretien', 6, 3, 'CHARGE'),
('6053', 'Fournitures de bureau', 6, 3, 'CHARGE'),
('6054', 'Achats de petit matériel et outillage', 6, 3, 'CHARGE'),
('6055', 'Achats de travaux, études et prestations de services', 6, 3, 'CHARGE'),
('6056', 'Achats non stockés de matières et fournitures', 6, 3, 'CHARGE'),
('6057', 'Achats de fournitures faites à l''entreprise', 6, 3, 'CHARGE'),
('6058', 'Autres achats', 6, 3, 'CHARGE'),
('6059', 'Rabais, remises et ristournes obtenus sur autres achats', 6, 3, 'CHARGE'),

-- Niveau 3 - Détail Emballages
('6081', 'Emballages perdus', 6, 3, 'CHARGE'),
('6082', 'Emballages récupérables non identifiables', 6, 3, 'CHARGE'),
('6083', 'Emballages à usage mixte', 6, 3, 'CHARGE'),
('6089', 'Rabais, remises et ristournes obtenus sur achats d''emballages', 6, 3, 'CHARGE'),

-- Niveau 2 - Détail TRANSPORTS
('611', 'Transports sur achats', 6, 2, 'CHARGE'),
('612', 'Transports sur ventes', 6, 2, 'CHARGE'),
('613', 'Transports pour le compte de tiers', 6, 2, 'CHARGE'),
('614', 'Transports du personnel', 6, 2, 'CHARGE'),
('616', 'Transports de plis', 6, 2, 'CHARGE'),
('618', 'Autres frais de transport et déplacements', 6, 2, 'CHARGE'),
('619', 'Rabais, remises et ristournes obtenus sur transports', 6, 2, 'CHARGE'),

-- Niveau 2 - Détail SERVICES EXTERIEURS A
('621', 'Sous-traitance générale', 6, 2, 'CHARGE'),
('622', 'Locations et charges locatives', 6, 2, 'CHARGE'),
('623', 'Redevances de crédit-bail et contrats assimilés', 6, 2, 'CHARGE'),
('624', 'Entretien, réparations et maintenances', 6, 2, 'CHARGE'),
('625', 'Primes d''assurances', 6, 2, 'CHARGE'),
('626', 'Etudes, recherches et documentation', 6, 2, 'CHARGE'),
('627', 'Publicité, publications, relations publiques', 6, 2, 'CHARGE'),
('628', 'Frais de télécommunications', 6, 2, 'CHARGE'),
('629', 'Rabais, remises et ristournes obtenus sur services extérieurs A', 6, 2, 'CHARGE'),

-- Niveau 3 - Détail Locations
('6221', 'Locations de terrains', 6, 3, 'CHARGE'),
('6222', 'Locations de bâtiments', 6, 3, 'CHARGE'),
('6223', 'Locations de matériel et outillage', 6, 3, 'CHARGE'),
('6224', 'Malis sur emballages rendus', 6, 3, 'CHARGE'),
('6225', 'Locations de mobilier et matériel de bureau', 6, 3, 'CHARGE'),
('6226', 'Locations de matériel informatique', 6, 3, 'CHARGE'),
('6227', 'Locations de matériel de transport', 6, 3, 'CHARGE'),
('6228', 'Autres locations', 6, 3, 'CHARGE'),

-- Niveau 3 - Détail Crédit-bail
('6231', 'Crédit-bail immobilier', 6, 3, 'CHARGE'),
('6232', 'Crédit-bail mobilier', 6, 3, 'CHARGE'),
('6233', 'Contrats assimilés', 6, 3, 'CHARGE'),

-- Niveau 3 - Détail Entretien et réparations
('6241', 'Entretien et réparations des biens immobiliers', 6, 3, 'CHARGE'),
('6242', 'Entretien et réparations des biens mobiliers', 6, 3, 'CHARGE'),
('6243', 'Maintenance', 6, 3, 'CHARGE'),
('6248', 'Autres entretiens et réparations', 6, 3, 'CHARGE'),

-- Niveau 3 - Détail Primes d'assurances
('6251', 'Assurances multirisques', 6, 3, 'CHARGE'),
('6252', 'Assurances matériel de transport', 6, 3, 'CHARGE'),
('6253', 'Assurances risques d''exploitation', 6, 3, 'CHARGE'),
('6254', 'Assurances responsabilité civile', 6, 3, 'CHARGE'),
('6255', 'Assurances insolvabilité clients', 6, 3, 'CHARGE'),
('6258', 'Autres assurances', 6, 3, 'CHARGE'),

-- Niveau 3 - Détail Etudes et recherches
('6261', 'Etudes générales', 6, 3, 'CHARGE'),
('6262', 'Recherches', 6, 3, 'CHARGE'),
('6263', 'Documentation générale', 6, 3, 'CHARGE'),
('6264', 'Documentation technique', 6, 3, 'CHARGE'),
('6265', 'Frais de colloques, séminaires, conférences', 6, 3, 'CHARGE'),

-- Niveau 3 - Détail Publicité
('6271', 'Annonces et insertions', 6, 3, 'CHARGE'),
('6272', 'Echantillons, catalogues et imprimés', 6, 3, 'CHARGE'),
('6273', 'Foires et expositions', 6, 3, 'CHARGE'),
('6274', 'Primes de publicité', 6, 3, 'CHARGE'),
('6275', 'Publications', 6, 3, 'CHARGE'),
('6276', 'Cadeaux à la clientèle', 6, 3, 'CHARGE'),
('6278', 'Autres charges de publicité et relations publiques', 6, 3, 'CHARGE'),

-- Niveau 3 - Détail Télécommunications
('6281', 'Frais de téléphone', 6, 3, 'CHARGE'),
('6282', 'Frais de télex et de télégrammes', 6, 3, 'CHARGE'),
('6283', 'Frais de télécopie, transmission de données', 6, 3, 'CHARGE'),
('6284', 'Frais d''affranchissement', 6, 3, 'CHARGE'),

-- Niveau 2 - Détail SERVICES EXTERIEURS B
('631', 'Frais bancaires', 6, 2, 'CHARGE'),
('632', 'Rémunérations d''intermédiaires et de conseils', 6, 2, 'CHARGE'),
('633', 'Frais de formation du personnel', 6, 2, 'CHARGE'),
('634', 'Redevances pour brevets, licences, logiciels et droits similaires', 6, 2, 'CHARGE'),
('635', 'Cotisations', 6, 2, 'CHARGE'),
('637', 'Rémunérations de personnel extérieur à l''entreprise', 6, 2, 'CHARGE'),
('638', 'Autres charges externes', 6, 2, 'CHARGE'),
('639', 'Rabais, remises et ristournes obtenus sur services extérieurs B', 6, 2, 'CHARGE'),

-- Niveau 3 - Détail Frais bancaires
('6311', 'Frais sur titres (achats, ventes, garde)', 6, 3, 'CHARGE'),
('6312', 'Frais sur effets de commerce', 6, 3, 'CHARGE'),
('6313', 'Location de coffres', 6, 3, 'CHARGE'),
('6315', 'Commissions sur cartes de crédit', 6, 3, 'CHARGE'),
('6316', 'Frais d''émission d''emprunts', 6, 3, 'CHARGE'),
('6318', 'Autres frais bancaires', 6, 3, 'CHARGE'),

-- Niveau 3 - Détail Rémunérations intermédiaires
('6321', 'Commissions et courtages sur ventes', 6, 3, 'CHARGE'),
('6322', 'Commissions et courtages sur achats', 6, 3, 'CHARGE'),
('6323', 'Rémunérations des transitaires', 6, 3, 'CHARGE'),
('6324', 'Honoraires', 6, 3, 'CHARGE'),
('6325', 'Frais d''actes et de contentieux', 6, 3, 'CHARGE'),
('6328', 'Divers', 6, 3, 'CHARGE'),

-- Niveau 3 - Détail Cotisations
('6351', 'Cotisations', 6, 3, 'CHARGE'),
('6352', 'Dons', 6, 3, 'CHARGE'),
('6353', 'Pourboires et dons courants', 6, 3, 'CHARGE'),

-- Niveau 3 - Détail Personnel extérieur
('6371', 'Personnel intérimaire', 6, 3, 'CHARGE'),
('6372', 'Personnel détaché ou prêté à l''entreprise', 6, 3, 'CHARGE'),

-- Niveau 3 - Détail Autres charges externes
('6381', 'Frais de recrutement de personnel', 6, 3, 'CHARGE'),
('6382', 'Frais de déménagement', 6, 3, 'CHARGE'),
('6383', 'Réceptions', 6, 3, 'CHARGE'),
('6384', 'Missions', 6, 3, 'CHARGE'),

-- Niveau 2 - Détail IMPOTS ET TAXES
('641', 'Impôts et taxes directs', 6, 2, 'CHARGE'),
('645', 'Impôts et taxes indirects', 6, 2, 'CHARGE'),
('646', 'Droits d''enregistrement', 6, 2, 'CHARGE'),
('647', 'Pénalités et amendes fiscales', 6, 2, 'CHARGE'),
('648', 'Autres impôts et taxes', 6, 2, 'CHARGE'),
('649', 'Charges provisionnées sur impôts et taxes', 6, 2, 'CHARGE'),

-- Niveau 3 - Détail Impôts directs
('6411', 'Impôts fonciers et taxes annexes', 6, 3, 'CHARGE'),
('6412', 'Patentes, licences et taxes annexes', 6, 3, 'CHARGE'),
('6413', 'Taxes sur appointements et salaires', 6, 3, 'CHARGE'),
('6414', 'Taxes d''apprentissage', 6, 3, 'CHARGE'),
('6415', 'Formation professionnelle continue', 6, 3, 'CHARGE'),
('6418', 'Autres impôts et taxes directs', 6, 3, 'CHARGE'),

-- Niveau 3 - Détail Impôts indirects
('6451', 'Taxes sur le chiffre d''affaires non récupérables', 6, 3, 'CHARGE'),
('6452', 'Droits de douane', 6, 3, 'CHARGE'),
('6453', 'Taxes spécifiques sur les produits', 6, 3, 'CHARGE'),
('6458', 'Autres impôts et taxes indirects', 6, 3, 'CHARGE'),

-- Niveau 2 - Détail AUTRES CHARGES
('651', 'Pertes sur créances clients et autres débiteurs', 6, 2, 'CHARGE'),
('652', 'Quote-part de résultat sur opérations faites en commun', 6, 2, 'CHARGE'),
('653', 'Quote-part de résultat annulée sur exécution partielle de contrats pluri-exercices', 6, 2, 'CHARGE'),
('654', 'Valeur comptable des cessions courantes d''immobilisations', 6, 2, 'CHARGE'),
('658', 'Charges diverses', 6, 2, 'CHARGE'),
('659', 'Charges provisionnées d''exploitation', 6, 2, 'CHARGE'),

-- Niveau 3 - Détail Pertes sur créances
('6511', 'Créances clients', 6, 3, 'CHARGE'),
('6515', 'Créances sur personnel', 6, 3, 'CHARGE'),
('6516', 'Créances sur l''Etat', 6, 3, 'CHARGE'),
('6518', 'Créances sur autres débiteurs', 6, 3, 'CHARGE'),

-- Niveau 3 - Détail Charges diverses
('6581', 'Jetons de présence et autres rémunérations d''administrateurs', 6, 3, 'CHARGE'),
('6582', 'Dons', 6, 3, 'CHARGE'),
('6583', 'Mécénat', 6, 3, 'CHARGE'),

-- Niveau 2 - Détail CHARGES DE PERSONNEL
('661', 'Appointements, salaires et commissions', 6, 2, 'CHARGE'),
('662', 'Primes et gratifications', 6, 2, 'CHARGE'),
('663', 'Congés payés', 6, 2, 'CHARGE'),
('664', 'Charges sociales', 6, 2, 'CHARGE'),
('665', 'Charges sociales facultatives', 6, 2, 'CHARGE'),
('666', 'Charges sociales du personnel extérieur', 6, 2, 'CHARGE'),
('667', 'Rémunérations transférées de charges', 6, 2, 'CHARGE'),
('668', 'Autres charges sociales', 6, 2, 'CHARGE'),

-- Niveau 3 - Détail Charges sociales
('6641', 'Charges sociales sur appointements et salaires', 6, 3, 'CHARGE'),
('6642', 'Charges sociales sur primes et gratifications', 6, 3, 'CHARGE'),
('6643', 'Charges sociales sur congés payés', 6, 3, 'CHARGE'),
('6644', 'Charges sociales sur indemnités de préavis, de licenciement et de recherche d''embauchage', 6, 3, 'CHARGE'),

-- Niveau 2 - Détail FRAIS FINANCIERS
('671', 'Intérêts des emprunts', 6, 2, 'CHARGE'),
('672', 'Intérêts dans loyers de crédit-bail et contrats assimilés', 6, 2, 'CHARGE'),
('673', 'Escomptes accordés', 6, 2, 'CHARGE'),
('674', 'Autres intérêts', 6, 2, 'CHARGE'),
('675', 'Escomptes des effets de commerce', 6, 2, 'CHARGE'),
('676', 'Pertes de change', 6, 2, 'CHARGE'),
('677', 'Pertes sur cessions de titres de placement', 6, 2, 'CHARGE'),
('678', 'Pertes sur risques financiers', 6, 2, 'CHARGE'),
('679', 'Charges provisionnées financières', 6, 2, 'CHARGE'),

-- Niveau 3 - Détail Intérêts des emprunts
('6711', 'Intérêts des emprunts obligataires', 6, 3, 'CHARGE'),
('6712', 'Intérêts des emprunts auprès des établissements de crédit', 6, 3, 'CHARGE'),
('6713', 'Intérêts des dettes rattachées à des participations', 6, 3, 'CHARGE'),
('6714', 'Intérêts des comptes courants et dépôts créditeurs', 6, 3, 'CHARGE'),
('6715', 'Intérêts bancaires et sur opérations de financement', 6, 3, 'CHARGE'),
('6716', 'Intérêts des obligations cautionnées', 6, 3, 'CHARGE'),
('6717', 'Agios et autres frais sur effets escomptés non échus', 6, 3, 'CHARGE'),
('6718', 'Intérêts des autres emprunts', 6, 3, 'CHARGE'),

-- Niveau 2 - Détail DOTATIONS AUX AMORTISSEMENTS
('681', 'Dotations aux amortissements d''exploitation', 6, 2, 'CHARGE'),
('687', 'Dotations aux amortissements à caractère financier', 6, 2, 'CHARGE'),

-- Niveau 3 - Détail Dotations exploitation
('6811', 'Dotations aux amortissements des charges immobilisées', 6, 3, 'CHARGE'),
('6812', 'Dotations aux amortissements des immobilisations incorporelles', 6, 3, 'CHARGE'),
('6813', 'Dotations aux amortissements des immobilisations corporelles', 6, 3, 'CHARGE'),

-- Niveau 2 - Détail DOTATIONS AUX PROVISIONS
('691', 'Dotations aux provisions d''exploitation', 6, 2, 'CHARGE'),
('697', 'Dotations aux provisions financières', 6, 2, 'CHARGE'),
('698', 'Dotations aux provisions H.A.O.', 6, 2, 'CHARGE'),

-- Niveau 3 - Détail Provisions exploitation
('6911', 'Pour risques et charges d''exploitation', 6, 3, 'CHARGE'),
('6912', 'Pour grosses réparations', 6, 3, 'CHARGE'),
('6913', 'Pour dépréciation des immobilisations incorporelles', 6, 3, 'CHARGE'),
('6914', 'Pour dépréciation des immobilisations corporelles', 6, 3, 'CHARGE'),
('6915', 'Pour dépréciation des stocks', 6, 3, 'CHARGE'),
('6916', 'Pour dépréciation des créances', 6, 3, 'CHARGE'),
('6917', 'Pour dépréciation de comptes de tiers', 6, 3, 'CHARGE'),
('6918', 'Pour dépréciation d''autres comptes', 6, 3, 'CHARGE');


-- ==========================================
-- CLASSE 7 : COMPTES DE PRODUITS DES ACTIVITES ORDINAIRES
-- ==========================================

INSERT INTO plan_comptable_ohada (numero_compte, libelle, classe, niveau, type_compte) VALUES
-- Niveau 1
('70', 'VENTES', 7, 1, 'PRODUIT'),
('71', 'SUBVENTIONS D''EXPLOITATION', 7, 1, 'PRODUIT'),
('72', 'PRODUCTION IMMOBILISEE', 7, 1, 'PRODUIT'),
('73', 'VARIATIONS DES STOCKS DE BIENS ET DE SERVICES PRODUITS', 7, 1, 'PRODUIT'),
('74', 'TRAVAUX, SERVICES VENDUS, PRODUITS ACCESSOIRES', 7, 1, 'PRODUIT'),
('75', 'AUTRES PRODUITS', 7, 1, 'PRODUIT'),
('76', 'REPRISES DE CHARGES ET TRANSFERTS DE CHARGES', 7, 1, 'PRODUIT'),
('77', 'REVENUS FINANCIERS ET PRODUITS ASSIMILES', 7, 1, 'PRODUIT'),
('78', 'TRANSFERTS DE CHARGES', 7, 1, 'PRODUIT'),
('79', 'REPRISES DE PROVISIONS', 7, 1, 'PRODUIT'),

-- Niveau 2 - Détail VENTES
('701', 'Ventes de marchandises', 7, 2, 'PRODUIT'),
('702', 'Ventes de produits finis', 7, 2, 'PRODUIT'),
('703', 'Ventes de produits intermédiaires', 7, 2, 'PRODUIT'),
('704', 'Ventes de produits résiduels', 7, 2, 'PRODUIT'),
('705', 'Travaux facturés', 7, 2, 'PRODUIT'),
('706', 'Services vendus', 7, 2, 'PRODUIT'),
('707', 'Produits accessoires', 7, 2, 'PRODUIT'),
('708', 'Produits des activités annexes', 7, 2, 'PRODUIT'),
('709', 'Rabais, remises et ristournes accordés', 7, 2, 'PRODUIT'),

-- Niveau 3 - Détail Ventes de marchandises
('7011', 'Ventes de marchandises dans la région', 7, 3, 'PRODUIT'),
('7012', 'Ventes de marchandises hors région', 7, 3, 'PRODUIT'),
('7013', 'Ventes de marchandises hors zone monétaire', 7, 3, 'PRODUIT'),

-- Niveau 3 - Détail Ventes de produits finis
('7021', 'Ventes de produits finis dans la région', 7, 3, 'PRODUIT'),
('7022', 'Ventes de produits finis hors région', 7, 3, 'PRODUIT'),
('7023', 'Ventes de produits finis hors zone monétaire', 7, 3, 'PRODUIT'),

-- Niveau 3 - Détail Produits accessoires
('7071', 'Ports et frais accessoires facturés', 7, 3, 'PRODUIT'),
('7072', 'Commissions et courtages', 7, 3, 'PRODUIT'),
('7073', 'Locations diverses', 7, 3, 'PRODUIT'),
('7074', 'Bonis sur reprises d''emballages consignés', 7, 3, 'PRODUIT'),
('7075', 'Ventes d''approvisionnements', 7, 3, 'PRODUIT'),
('7076', 'Ventes de produits divers', 7, 3, 'PRODUIT'),
('7078', 'Autres produits accessoires', 7, 3, 'PRODUIT'),

-- Niveau 3 - Détail Produits des activités annexes
('7081', 'Produits des services exploités dans l''intérêt du personnel', 7, 3, 'PRODUIT'),
('7082', 'Produits des cessions courantes d''immobilisations', 7, 3, 'PRODUIT'),
('7083', 'Locations et redevances pour sous-concessions', 7, 3, 'PRODUIT'),
('7084', 'Mise à disposition de personnel facturée', 7, 3, 'PRODUIT'),
('7085', 'Indemnités d''assurances reçues', 7, 3, 'PRODUIT'),
('7088', 'Autres produits des activités annexes', 7, 3, 'PRODUIT'),

-- Niveau 3 - Détail RRR accordés
('7091', 'RRR accordés sur ventes de marchandises', 7, 3, 'PRODUIT'),
('7092', 'RRR accordés sur ventes de produits finis', 7, 3, 'PRODUIT'),
('7093', 'RRR accordés sur ventes de produits intermédiaires', 7, 3, 'PRODUIT'),
('7094', 'RRR accordés sur ventes de produits résiduels', 7, 3, 'PRODUIT'),
('7095', 'RRR accordés sur travaux facturés', 7, 3, 'PRODUIT'),
('7096', 'RRR accordés sur services vendus', 7, 3, 'PRODUIT'),
('7097', 'RRR accordés sur produits accessoires', 7, 3, 'PRODUIT'),
('7098', 'RRR accordés sur produits des activités annexes', 7, 3, 'PRODUIT'),

-- Niveau 2 - Détail SUBVENTIONS D'EXPLOITATION
('711', 'Subventions d''équilibre', 7, 2, 'PRODUIT'),
('712', 'Subventions spécifiques', 7, 2, 'PRODUIT'),
('713', 'Subventions d''équipement en nature', 7, 2, 'PRODUIT'),
('718', 'Autres subventions d''exploitation', 7, 2, 'PRODUIT'),

-- Niveau 2 - Détail PRODUCTION IMMOBILISEE
('721', 'Immobilisations incorporelles', 7, 2, 'PRODUIT'),
('722', 'Immobilisations corporelles', 7, 2, 'PRODUIT'),
('726', 'Immobilisations financières', 7, 2, 'PRODUIT'),

-- Niveau 3 - Détail Production immobilisée corporelles
('7221', 'Terrains', 7, 3, 'PRODUIT'),
('7222', 'Bâtiments', 7, 3, 'PRODUIT'),
('7223', 'Installations et agencements', 7, 3, 'PRODUIT'),
('7224', 'Matériel', 7, 3, 'PRODUIT'),

-- Niveau 2 - Détail VARIATIONS DE STOCKS
('731', 'Variation des stocks de produits en cours', 7, 2, 'PRODUIT'),
('735', 'Variation des stocks de services en cours', 7, 2, 'PRODUIT'),
('736', 'Variation des stocks de produits finis', 7, 2, 'PRODUIT'),
('737', 'Variation des stocks de produits intermédiaires et résiduels', 7, 2, 'PRODUIT'),

-- Niveau 2 - Détail AUTRES PRODUITS
('754', 'Produits nets sur cessions de titres et valeurs de placement', 7, 2, 'PRODUIT'),
('758', 'Produits divers', 7, 2, 'PRODUIT'),
('759', 'Charges provisionnées - exploitation', 7, 2, 'PRODUIT'),

-- Niveau 3 - Détail Produits divers
('7581', 'Jetons de présence et autres rémunérations d''administrateurs', 7, 3, 'PRODUIT'),
('7582', 'Indemnités d''assurances reçues', 7, 3, 'PRODUIT'),
('7588', 'Autres produits divers', 7, 3, 'PRODUIT'),

-- Niveau 2 - Détail REPRISES DE CHARGES
('781', 'Transferts de charges d''exploitation', 7, 2, 'PRODUIT'),
('787', 'Transferts de charges financières', 7, 2, 'PRODUIT'),

-- Niveau 2 - Détail REVENUS FINANCIERS
('771', 'Intérêts de prêts', 7, 2, 'PRODUIT'),
('772', 'Revenus de participations', 7, 2, 'PRODUIT'),
('773', 'Escomptes obtenus', 7, 2, 'PRODUIT'),
('774', 'Revenus de titres de placement', 7, 2, 'PRODUIT'),
('776', 'Gains de change', 7, 2, 'PRODUIT'),
('777', 'Gains sur cessions de titres de placement', 7, 2, 'PRODUIT'),
('778', 'Gains sur risques financiers', 7, 2, 'PRODUIT'),
('779', 'Reprises de charges provisionnées financières', 7, 2, 'PRODUIT'),

-- Niveau 3 - Détail Intérêts de prêts
('7711', 'Intérêts sur prêts au personnel', 7, 3, 'PRODUIT'),
('7712', 'Intérêts sur prêts aux associés', 7, 3, 'PRODUIT'),
('7713', 'Intérêts sur créances commerciales', 7, 3, 'PRODUIT'),
('7714', 'Revenus sur créances financières', 7, 3, 'PRODUIT'),
('7715', 'Intérêts des comptes en banque', 7, 3, 'PRODUIT'),
('7718', 'Intérêts sur autres prêts', 7, 3, 'PRODUIT'),

-- Niveau 3 - Détail Revenus de participations
('7721', 'Revenus de participations dans des sociétés sous contrôle exclusif', 7, 3, 'PRODUIT'),
('7722', 'Revenus de participations dans des sociétés sous contrôle conjoint', 7, 3, 'PRODUIT'),
('7723', 'Revenus de participations dans des sociétés conférant une influence notable', 7, 3, 'PRODUIT'),
('7724', 'Revenus de participations dans des sociétés sans influence notable', 7, 3, 'PRODUIT'),
('7725', 'Revenus de parts dans des G.I.E.', 7, 3, 'PRODUIT'),

-- Niveau 2 - Détail REPRISES DE PROVISIONS
('791', 'Reprises de provisions d''exploitation', 7, 2, 'PRODUIT'),
('797', 'Reprises de provisions financières', 7, 2, 'PRODUIT'),
('798', 'Reprises de provisions H.A.O.', 7, 2, 'PRODUIT'),

-- Niveau 3 - Détail Reprises provisions exploitation
('7911', 'Reprises de provisions pour risques et charges', 7, 3, 'PRODUIT'),
('7912', 'Reprises de provisions pour grosses réparations', 7, 3, 'PRODUIT'),
('7913', 'Reprises de provisions pour dépréciation des immobilisations incorporelles', 7, 3, 'PRODUIT'),
('7914', 'Reprises de provisions pour dépréciation des immobilisations corporelles', 7, 3, 'PRODUIT'),
('7915', 'Reprises de provisions pour dépréciation des stocks', 7, 3, 'PRODUIT'),
('7916', 'Reprises de provisions pour dépréciation des créances', 7, 3, 'PRODUIT'),
('7917', 'Reprises de provisions pour dépréciation de comptes de tiers', 7, 3, 'PRODUIT'),
('7918', 'Reprises de provisions pour dépréciation d''autres comptes', 7, 3, 'PRODUIT');


-- ==========================================
-- CLASSE 8 : COMPTES DES AUTRES CHARGES ET DES AUTRES PRODUITS (H.A.O.)
-- ==========================================

INSERT INTO plan_comptable_ohada (numero_compte, libelle, classe, niveau, type_compte) VALUES
-- Niveau 1
('81', 'VALEURS COMPTABLES DES CESSIONS D''IMMOBILISATIONS', 8, 1, 'CHARGE'),
('82', 'PRODUITS DES CESSIONS D''IMMOBILISATIONS', 8, 1, 'PRODUIT'),
('83', 'CHARGES H.A.O.', 8, 1, 'CHARGE'),
('84', 'PRODUITS H.A.O.', 8, 1, 'PRODUIT'),
('85', 'DOTATIONS H.A.O.', 8, 1, 'CHARGE'),
('86', 'REPRISES H.A.O.', 8, 1, 'PRODUIT'),
('87', 'PARTICIPATION DES TRAVAILLEURS', 8, 1, 'CHARGE'),
('88', 'SUBVENTIONS D''EQUILIBRE', 8, 1, 'PRODUIT'),
('89', 'IMPOTS SUR LE RESULTAT', 8, 1, 'CHARGE'),

-- Niveau 2 - Détail VALEURS COMPTABLES DES CESSIONS
('811', 'Immobilisations incorporelles', 8, 2, 'CHARGE'),
('812', 'Immobilisations corporelles', 8, 2, 'CHARGE'),
('816', 'Immobilisations financières', 8, 2, 'CHARGE'),

-- Niveau 3 - Détail Immobilisations incorporelles
('8111', 'Frais de recherche et de développement', 8, 3, 'CHARGE'),
('8112', 'Brevets, licences, logiciels', 8, 3, 'CHARGE'),
('8113', 'Fonds commercial', 8, 3, 'CHARGE'),
('8118', 'Autres immobilisations incorporelles', 8, 3, 'CHARGE'),

-- Niveau 3 - Détail Immobilisations corporelles
('8121', 'Terrains', 8, 3, 'CHARGE'),
('8122', 'Bâtiments', 8, 3, 'CHARGE'),
('8123', 'Installations et agencements', 8, 3, 'CHARGE'),
('8124', 'Matériel', 8, 3, 'CHARGE'),
('8128', 'Autres immobilisations corporelles', 8, 3, 'CHARGE'),

-- Niveau 3 - Détail Immobilisations financières
('8161', 'Titres de participation', 8, 3, 'CHARGE'),
('8162', 'Autres titres immobilisés', 8, 3, 'CHARGE'),
('8168', 'Autres immobilisations financières', 8, 3, 'CHARGE'),

-- Niveau 2 - Détail PRODUITS DES CESSIONS
('821', 'Immobilisations incorporelles', 8, 2, 'PRODUIT'),
('822', 'Immobilisations corporelles', 8, 2, 'PRODUIT'),
('826', 'Immobilisations financières', 8, 2, 'PRODUIT'),

-- Niveau 3 - Détail Produits cessions incorporelles
('8211', 'Frais de recherche et de développement', 8, 3, 'PRODUIT'),
('8212', 'Brevets, licences, logiciels', 8, 3, 'PRODUIT'),
('8213', 'Fonds commercial', 8, 3, 'PRODUIT'),
('8218', 'Autres immobilisations incorporelles', 8, 3, 'PRODUIT'),

-- Niveau 3 - Détail Produits cessions corporelles
('8221', 'Terrains', 8, 3, 'PRODUIT'),
('8222', 'Bâtiments', 8, 3, 'PRODUIT'),
('8223', 'Installations et agencements', 8, 3, 'PRODUIT'),
('8224', 'Matériel', 8, 3, 'PRODUIT'),
('8228', 'Autres immobilisations corporelles', 8, 3, 'PRODUIT'),

-- Niveau 3 - Détail Produits cessions financières
('8261', 'Titres de participation', 8, 3, 'PRODUIT'),
('8262', 'Autres titres immobilisés', 8, 3, 'PRODUIT'),
('8268', 'Autres immobilisations financières', 8, 3, 'PRODUIT'),

-- Niveau 2 - Détail CHARGES H.A.O.
('831', 'Charges sur exercices antérieurs (à préciser)', 8, 2, 'CHARGE'),
('834', 'Pertes sur créances H.A.O.', 8, 2, 'CHARGE'),
('835', 'Dons et libéralités accordés', 8, 2, 'CHARGE'),
('836', 'Abandons de créances consentis', 8, 2, 'CHARGE'),
('838', 'Autres charges H.A.O.', 8, 2, 'CHARGE'),
('839', 'Charges provisionnées H.A.O.', 8, 2, 'CHARGE'),

-- Niveau 2 - Détail PRODUITS H.A.O.
('841', 'Produits sur exercices antérieurs (à préciser)', 8, 2, 'PRODUIT'),
('845', 'Rentrées sur créances amorties', 8, 2, 'PRODUIT'),
('846', 'Abandons de créances obtenus', 8, 2, 'PRODUIT'),
('848', 'Autres produits H.A.O.', 8, 2, 'PRODUIT'),
('849', 'Reprises de charges provisionnées H.A.O.', 8, 2, 'PRODUIT'),

-- Niveau 2 - Détail DOTATIONS H.A.O.
('851', 'Dotations aux provisions réglementées', 8, 2, 'CHARGE'),
('852', 'Dotations aux amortissements H.A.O.', 8, 2, 'CHARGE'),
('853', 'Dotations aux provisions pour dépréciation H.A.O.', 8, 2, 'CHARGE'),
('854', 'Dotations aux provisions pour risques et charges H.A.O.', 8, 2, 'CHARGE'),

-- Niveau 2 - Détail REPRISES H.A.O.
('861', 'Reprises de provisions réglementées', 8, 2, 'PRODUIT'),
('862', 'Reprises d''amortissements', 8, 2, 'PRODUIT'),
('863', 'Reprises de provisions pour dépréciation H.A.O.', 8, 2, 'PRODUIT'),
('864', 'Reprises de provisions pour risques et charges H.A.O.', 8, 2, 'PRODUIT'),

-- Niveau 2 - Détail PARTICIPATION
('871', 'Participation des travailleurs aux résultats', 8, 2, 'CHARGE'),

-- Niveau 2 - Détail SUBVENTIONS D'EQUILIBRE
('881', 'Subventions d''équilibre', 8, 2, 'PRODUIT'),
('884', 'Prélèvements pour impôts', 8, 2, 'PRODUIT'),

-- Niveau 2 - Détail IMPOTS SUR LE RESULTAT
('891', 'Impôts sur les bénéfices de l''exercice', 8, 2, 'CHARGE'),
('892', 'Rappel d''impôts sur résultats antérieurs', 8, 2, 'CHARGE'),
('893', 'Impôts différés actif', 8, 2, 'CHARGE'),
('894', 'Impôts différés passif', 8, 2, 'CHARGE'),
('895', 'Dégré vements sur impôts sur les bénéfices', 8, 2, 'CHARGE'),
('899', 'Dégrèvements d''impôts sur les bénéfices', 8, 2, 'CHARGE');


-- ==========================================
-- CLASSE 9 : COMPTES DE LA COMPTABILITE ANALYTIQUE
-- ==========================================

INSERT INTO plan_comptable_ohada (numero_compte, libelle, classe, niveau, type_compte) VALUES
-- Niveau 1
('90', 'COMPTES REFLECHIS', 9, 1, 'SPECIAL'),
('91', 'RECLASSEMENT DES CHARGES DE LA COMPTABILITE GENERALE', 9, 1, 'SPECIAL'),
('92', 'SECTIONS ANALYTIQUES', 9, 1, 'SPECIAL'),
('93', 'COUTS D''ACHATS', 9, 1, 'SPECIAL'),
('94', 'COUTS DE PRODUCTION', 9, 1, 'SPECIAL'),
('95', 'COUTS DE DISTRIBUTION', 9, 1, 'SPECIAL'),
('96', 'COUTS DE REVIENT', 9, 1, 'SPECIAL'),
('97', 'DIFFERENCES SUR NIVEAUX D''ACTIVITE', 9, 1, 'SPECIAL'),
('98', 'RESULTATS DE LA COMPTABILITE ANALYTIQUE', 9, 1, 'SPECIAL'),
('99', 'LIAISONS INTERNES', 9, 1, 'SPECIAL'),

-- Niveau 2 - Détail COMPTES REFLECHIS
('901', 'Comptes de bilan réfléchis', 9, 2, 'SPECIAL'),
('902', 'Comptes de gestion réfléchis', 9, 2, 'SPECIAL'),

-- Niveau 2 - Détail RECLASSEMENT
('911', 'Reclassement des charges', 9, 2, 'SPECIAL'),
('912', 'Reclassement des produits', 9, 2, 'SPECIAL'),

-- Niveau 2 - Détail SECTIONS ANALYTIQUES
('921', 'Section analytique A', 9, 2, 'SPECIAL'),
('922', 'Section analytique B', 9, 2, 'SPECIAL'),
('928', 'Autres sections analytiques', 9, 2, 'SPECIAL'),

-- Niveau 2 - Détail COUTS D'ACHATS
('931', 'Coûts d''achat de marchandises', 9, 2, 'SPECIAL'),
('932', 'Coûts d''achat de matières premières', 9, 2, 'SPECIAL'),
('933', 'Coûts d''achat d''approvisionnements', 9, 2, 'SPECIAL'),

-- Niveau 2 - Détail COUTS DE PRODUCTION
('941', 'Coûts de production de produits finis', 9, 2, 'SPECIAL'),
('942', 'Coûts de production de produits intermédiaires', 9, 2, 'SPECIAL'),
('943', 'Coûts de production de services', 9, 2, 'SPECIAL'),

-- Niveau 2 - Détail COUTS DE DISTRIBUTION
('951', 'Coûts de distribution de marchandises', 9, 2, 'SPECIAL'),
('952', 'Coûts de distribution de produits finis', 9, 2, 'SPECIAL'),
('953', 'Coûts de distribution de services', 9, 2, 'SPECIAL'),

-- Niveau 2 - Détail COUTS DE REVIENT
('961', 'Coûts de revient de marchandises', 9, 2, 'SPECIAL'),
('962', 'Coûts de revient de produits finis', 9, 2, 'SPECIAL'),
('963', 'Coûts de revient de services', 9, 2, 'SPECIAL'),

-- Niveau 2 - Détail DIFFERENCES
('971', 'Différences sur niveau d''activité constatées', 9, 2, 'SPECIAL'),
('972', 'Différences sur coûts préétablis', 9, 2, 'SPECIAL'),
('978', 'Autres différences', 9, 2, 'SPECIAL'),

-- Niveau 2 - Détail RESULTATS
('981', 'Résultats analytiques sur marchandises', 9, 2, 'SPECIAL'),
('982', 'Résultats analytiques sur produits finis', 9, 2, 'SPECIAL'),
('983', 'Résultats analytiques sur services', 9, 2, 'SPECIAL'),

-- Niveau 2 - Détail LIAISONS INTERNES
('991', 'Liaisons comptabilité générale - comptabilité analytique', 9, 2, 'SPECIAL'),
('992', 'Liaisons entre sections', 9, 2, 'SPECIAL'),
('998', 'Autres liaisons internes', 9, 2, 'SPECIAL');


-- ==========================================
-- FIN DU PLAN COMPTABLE OHADA
-- ==========================================

-- Commentaires finaux
COMMENT ON TABLE plan_comptable_ohada IS 'Plan Comptable OHADA complet - Organisation pour l''Harmonisation en Afrique du Droit des Affaires';
COMMENT ON COLUMN plan_comptable_ohada.numero_compte IS 'Numéro du compte comptable (ex: 601, 6011, etc.)';
COMMENT ON COLUMN plan_comptable_ohada.libelle IS 'Libellé explicite du compte';
COMMENT ON COLUMN plan_comptable_ohada.classe IS 'Classe du compte (1 à 9)';
COMMENT ON COLUMN plan_comptable_ohada.niveau IS 'Niveau hiérarchique du compte (1 à 5)';
COMMENT ON COLUMN plan_comptable_ohada.type_compte IS 'Type: ACTIF, PASSIF, CHARGE, PRODUIT ou SPECIAL';
COMMENT ON COLUMN plan_comptable_ohada.utilisable IS 'Indique si le compte peut être utilisé directement dans les écritures';

-- Index supplémentaires pour les performances
CREATE INDEX idx_libelle_compte ON plan_comptable_ohada(libelle);
CREATE INDEX idx_niveau ON plan_comptable_ohada(niveau);
CREATE INDEX idx_classe_niveau ON plan_comptable_ohada(classe, niveau);

-- Statistiques finales
DO $$
DECLARE
    total_comptes INTEGER;
    comptes_classe_1 INTEGER;
    comptes_classe_2 INTEGER;
    comptes_classe_3 INTEGER;
    comptes_classe_4 INTEGER;
    comptes_classe_5 INTEGER;
    comptes_classe_6 INTEGER;
    comptes_classe_7 INTEGER;
    comptes_classe_8 INTEGER;
    comptes_classe_9 INTEGER;
BEGIN
    SELECT COUNT(*) INTO total_comptes FROM plan_comptable_ohada;
    SELECT COUNT(*) INTO comptes_classe_1 FROM plan_comptable_ohada WHERE classe = 1;
    SELECT COUNT(*) INTO comptes_classe_2 FROM plan_comptable_ohada WHERE classe = 2;
    SELECT COUNT(*) INTO comptes_classe_3 FROM plan_comptable_ohada WHERE classe = 3;
    SELECT COUNT(*) INTO comptes_classe_4 FROM plan_comptable_ohada WHERE classe = 4;
    SELECT COUNT(*) INTO comptes_classe_5 FROM plan_comptable_ohada WHERE classe = 5;
    SELECT COUNT(*) INTO comptes_classe_6 FROM plan_comptable_ohada WHERE classe = 6;
    SELECT COUNT(*) INTO comptes_classe_7 FROM plan_comptable_ohada WHERE classe = 7;
    SELECT COUNT(*) INTO comptes_classe_8 FROM plan_comptable_ohada WHERE classe = 8;
    SELECT COUNT(*) INTO comptes_classe_9 FROM plan_comptable_ohada WHERE classe = 9;
    
    RAISE NOTICE '==========================================';
    RAISE NOTICE 'STATISTIQUES DU PLAN COMPTABLE OHADA';
    RAISE NOTICE '==========================================';
    RAISE NOTICE 'Total de comptes créés: %', total_comptes;
    RAISE NOTICE 'Classe 1 (Ressources durables): % comptes', comptes_classe_1;
    RAISE NOTICE 'Classe 2 (Actif immobilisé): % comptes', comptes_classe_2;
    RAISE NOTICE 'Classe 3 (Stocks): % comptes', comptes_classe_3;
    RAISE NOTICE 'Classe 4 (Tiers): % comptes', comptes_classe_4;
    RAISE NOTICE 'Classe 5 (Trésorerie): % comptes', comptes_classe_5;
    RAISE NOTICE 'Classe 6 (Charges): % comptes', comptes_classe_6;
    RAISE NOTICE 'Classe 7 (Produits): % comptes', comptes_classe_7;
    RAISE NOTICE 'Classe 8 (H.A.O.): % comptes', comptes_classe_8;
    RAISE NOTICE 'Classe 9 (Analytique): % comptes', comptes_classe_9;
    RAISE NOTICE '==========================================';
END $$;

