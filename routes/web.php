<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\FournisseurController;
use App\Http\Controllers\FactureFournisseurController;
use App\Http\Controllers\ReglementFournisseurController;
use App\Http\Controllers\BanqueController;
use App\Http\Controllers\PlanComptableController;

// Page d'accueil (Welcome)
Route::get('/', function () {

    return Inertia::render('Welcome');
});

// Authentication Routes (UI only for now)
Route::get('/login', function () {
    return Inertia::render('Auth/Login');
})->name('login');

// Dashboard (Protected - pour l'instant accessible sans auth)
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard', [
        'user' => [
            'name' => 'Utilisateur Test',
            'email' => 'test@example.com'
        ]
    ]);
})->name('dashboard');

// ==========================================
// FOURNISSEURS ROUTES (CRUD FONCTIONNEL)
// ==========================================
Route::prefix('fournisseurs')->group(function () {
    // Liste des fournisseurs (Vue)
    Route::get('/', [FournisseurController::class, 'index'])->name('fournisseurs.index');

    // Détail fournisseur (Vue)
    Route::get('/{id}', [FournisseurController::class, 'show'])
        ->where('id', '[0-9]+')
        ->name('fournisseurs.show');
});

// API Fournisseurs (JSON)
Route::prefix('api/fournisseurs')->group(function () {
    // Créer un fournisseur
    Route::post('/', [FournisseurController::class, 'store'])->name('api.fournisseurs.store');

    // Modifier un fournisseur
    Route::put('/{id}', [FournisseurController::class, 'update'])
        ->where('id', '[0-9]+')
        ->name('api.fournisseurs.update');

    // Supprimer un fournisseur
    Route::delete('/{id}', [FournisseurController::class, 'destroy'])
        ->where('id', '[0-9]+')
        ->name('api.fournisseurs.destroy');

    // Statistiques
    Route::get('/stats', [FournisseurController::class, 'stats'])->name('api.fournisseurs.stats');
});

// API Factures Fournisseurs (JSON)
Route::prefix('api/factures-fournisseurs')->group(function () {
    // Lister les factures
    Route::get('/', [FactureFournisseurController::class, 'index'])->name('api.factures-fournisseurs.index');

    // Générer un numéro de pièce
    Route::get('/generer-numero', [FactureFournisseurController::class, 'genererNumero'])->name('api.factures-fournisseurs.generer-numero');

    // Statistiques
    Route::get('/stats', [FactureFournisseurController::class, 'stats'])->name('api.factures-fournisseurs.stats');

    // Créer une facture
    Route::post('/', [FactureFournisseurController::class, 'store'])->name('api.factures-fournisseurs.store');

    // Voir une facture
    Route::get('/{id}', [FactureFournisseurController::class, 'show'])
        ->where('id', '[0-9]+')
        ->name('api.factures-fournisseurs.show');

    // Modifier une facture
    Route::put('/{id}', [FactureFournisseurController::class, 'update'])
        ->where('id', '[0-9]+')
        ->name('api.factures-fournisseurs.update');

    // Supprimer une facture
    Route::delete('/{id}', [FactureFournisseurController::class, 'destroy'])
        ->where('id', '[0-9]+')
        ->name('api.factures-fournisseurs.destroy');

    // Valider une facture
    Route::post('/{id}/valider', [FactureFournisseurController::class, 'valider'])
        ->where('id', '[0-9]+')
        ->name('api.factures-fournisseurs.valider');

    // Annuler une facture
    Route::post('/{id}/annuler', [FactureFournisseurController::class, 'annuler'])
        ->where('id', '[0-9]+')
        ->name('api.factures-fournisseurs.annuler');

    // Solder une facture (marquer comme payée)
    Route::post('/{id}/solder', [FactureFournisseurController::class, 'solder'])
        ->where('id', '[0-9]+')
        ->name('api.factures-fournisseurs.solder');
});

// API Règlements Fournisseurs (JSON)
Route::prefix('api/reglements-fournisseurs')->group(function () {
    // Lister les règlements
    Route::get('/', [ReglementFournisseurController::class, 'index'])->name('api.reglements-fournisseurs.index');

    // Générer un numéro de règlement
    Route::get('/generer-numero', [ReglementFournisseurController::class, 'genererNumero'])->name('api.reglements-fournisseurs.generer-numero');

    // Statistiques
    Route::get('/stats', [ReglementFournisseurController::class, 'stats'])->name('api.reglements-fournisseurs.stats');

    // Créer un règlement
    Route::post('/', [ReglementFournisseurController::class, 'store'])->name('api.reglements-fournisseurs.store');

    // Voir un règlement
    Route::get('/{id}', [ReglementFournisseurController::class, 'show'])
        ->where('id', '[0-9]+')
        ->name('api.reglements-fournisseurs.show');

    // Modifier un règlement
    Route::put('/{id}', [ReglementFournisseurController::class, 'update'])
        ->where('id', '[0-9]+')
        ->name('api.reglements-fournisseurs.update');

    // Supprimer (annuler) un règlement
    Route::delete('/{id}', [ReglementFournisseurController::class, 'destroy'])
        ->where('id', '[0-9]+')
        ->name('api.reglements-fournisseurs.destroy');

    // Règlements d'une facture
    Route::get('/facture/{factureId}', [ReglementFournisseurController::class, 'parFacture'])
        ->where('factureId', '[0-9]+')
        ->name('api.reglements-fournisseurs.par-facture');
});

// Factures Fournisseurs Routes
Route::prefix('factures-fournisseurs')->group(function () {
    // Liste des factures (depuis la base de données)
    Route::get('/', [FactureFournisseurController::class, 'indexView'])
        ->name('factures-fournisseurs.index');

    // Routes commentées - Création et édition se font maintenant via modal
    /*
    // Formulaire création
    Route::get('/create', function () {
        $fournisseurs = [
            ['id' => 1, 'code' => 'FOUR001', 'nom' => 'Pharmacie Centrale du Bénin', 'compte_numero' => '401001'],
            ['id' => 2, 'code' => 'FOUR002', 'nom' => 'SOBEMAP Matériel Médical', 'compte_numero' => '401002'],
            ['id' => 3, 'code' => 'FOUR003', 'nom' => 'SONEB (Eau)', 'compte_numero' => '401003'],
        ];

        $comptesImputation = [
            ['id' => 1, 'numero' => '601100', 'libelle' => 'Achats de médicaments'],
            ['id' => 2, 'numero' => '601200', 'libelle' => 'Achats de matériel médical'],
            ['id' => 3, 'numero' => '604100', 'libelle' => 'Achats de fournitures de bureau'],
            ['id' => 4, 'numero' => '605100', 'libelle' => 'Eau et électricité'],
            ['id' => 5, 'numero' => '622100', 'libelle' => 'Services extérieurs'],
        ];

        return Inertia::render('Fournisseurs/Factures/Form', [
            'fournisseurs' => $fournisseurs,
            'comptesImputation' => $comptesImputation,
            'user' => [
                'name' => 'Utilisateur Test',
                'email' => 'test@example.com'
            ]
        ]);
    })->name('factures-fournisseurs.create');
    */

    // Détail facture (depuis la base de données)
    Route::get('/{id}', [FactureFournisseurController::class, 'showView'])
        ->where('id', '[0-9]+')
        ->name('factures-fournisseurs.show');

    // Formulaire règlement (depuis la base de données)
    Route::get('/{id}/regler', [FactureFournisseurController::class, 'reglementView'])
        ->where('id', '[0-9]+')
        ->name('factures-fournisseurs.regler');

    // Routes commentées - Édition se fait maintenant via modal
    /*
    // Formulaire édition
    Route::get('/{id}/edit', function ($id) {
        // Facture existante avec ses lignes
        $facture = [
            'id' => $id,
            'numero' => 'PC/025/0001',
            'date_facture' => '2025-01-15',
            'date_echeance' => '2025-02-15',
            'reference' => 'REF-001',
            'fournisseur_id' => 1,
            'remarques' => 'Commande urgente de médicaments essentiels',
            'lignes' => [
                [
                    'id' => 1,
                    'description' => 'Paracétamol 500mg (Boîte de 1000)',
                    'compte_imputation_id' => 1,
                    'quantite' => 50,
                    'prix_unitaire' => 80000,
                    'taux_tva' => 18,
                    'taux_aib' => 1,
                    'taux_escompte' => 0,
                    'montant_ht' => 4000000,
                    'montant_tva' => 720000,
                    'montant_aib' => 40000,
                    'montant_escompte' => 0,
                    'montant_ttc' => 4760000
                ],
                [
                    'id' => 2,
                    'description' => 'Amoxicilline 1g (Boîte de 500)',
                    'compte_imputation_id' => 1,
                    'quantite' => 20,
                    'prix_unitaire' => 50000,
                    'taux_tva' => 18,
                    'taux_aib' => 1,
                    'taux_escompte' => 0,
                    'montant_ht' => 1000000,
                    'montant_tva' => 180000,
                    'montant_aib' => 10000,
                    'montant_escompte' => 0,
                    'montant_ttc' => 1190000
                ]
            ]
        ];

        $fournisseurs = [
            ['id' => 1, 'code' => 'FOUR001', 'nom' => 'Pharmacie Centrale du Bénin', 'compte_numero' => '401001'],
            ['id' => 2, 'code' => 'FOUR002', 'nom' => 'SOBEMAP Matériel Médical', 'compte_numero' => '401002'],
            ['id' => 3, 'code' => 'FOUR003', 'nom' => 'SONEB (Eau)', 'compte_numero' => '401003'],
        ];

        $comptesImputation = [
            ['id' => 1, 'numero' => '601100', 'libelle' => 'Achats de médicaments'],
            ['id' => 2, 'numero' => '601200', 'libelle' => 'Achats de matériel médical'],
            ['id' => 3, 'numero' => '604100', 'libelle' => 'Achats de fournitures de bureau'],
            ['id' => 4, 'numero' => '605100', 'libelle' => 'Eau et électricité'],
            ['id' => 5, 'numero' => '622100', 'libelle' => 'Services extérieurs'],
        ];

        return Inertia::render('Fournisseurs/Factures/Form', [
            'facture' => $facture,
            'fournisseurs' => $fournisseurs,
            'comptesImputation' => $comptesImputation,
            'user' => [
                'name' => 'Utilisateur Test',
                'email' => 'test@example.com'
            ]
        ]);
    })->name('factures-fournisseurs.edit');
    */
});

// Règlements Fournisseurs Routes
Route::prefix('reglements-fournisseurs')->group(function () {
    // Liste des règlements (depuis la base de données)
    Route::get('/', [ReglementFournisseurController::class, 'indexView'])
        ->name('reglements-fournisseurs.index');

    // Documents de règlement
    Route::get('/{id}/recu', function ($id) {
        // Trouver le règlement dans les données mock
        $reglement = [
            'id' => $id,
            'date_reglement' => '2025-01-20',
            'facture' => [
                'id' => 1,
                'numero' => 'PC/025/0001',
                'date_facture' => '2025-01-15'
            ],
            'fournisseur' => [
                'id' => 1,
                'code' => 'FOUR001',
                'nom' => 'Pharmacie Centrale du Bénin',
                'compte_numero' => '401001',
                'ifu' => '0000000000001',
                'rccm' => 'RB/COT/XX-X-00001'
            ],
            'mode_paiement' => 'virement',
            'reference' => 'VIR-2025-001',
            'compte_bancaire' => [
                'banque' => 'ORABANK',
                'numero' => 'BJ123456789'
            ],
            'montant' => 2000000,
            'user' => [
                'name' => 'Admin User'
            ]
        ];

        return Inertia::render('Documents/RecuPaiement', [
            'reglement' => $reglement
        ]);
    })->name('reglements-fournisseurs.recu');

    Route::get('/{id}/mandat', function ($id) {
        // Trouver le règlement dans les données mock
        $reglement = [
            'id' => $id,
            'date_reglement' => '2025-01-20',
            'facture' => [
                'id' => 1,
                'numero' => 'PC/025/0001',
                'date_facture' => '2025-01-15'
            ],
            'fournisseur' => [
                'id' => 1,
                'code' => 'FOUR001',
                'nom' => 'Pharmacie Centrale du Bénin',
                'compte_numero' => '401001',
                'ifu' => '0000000000001',
                'rccm' => 'RB/COT/XX-X-00001'
            ],
            'mode_paiement' => 'virement',
            'reference' => 'VIR-2025-001',
            'compte_bancaire' => [
                'banque' => 'ORABANK',
                'numero' => 'BJ123456789'
            ],
            'montant' => 2000000,
            'user' => [
                'name' => 'Admin User'
            ]
        ];

        return Inertia::render('Documents/MandatPaiement', [
            'reglement' => $reglement
        ]);
    })->name('reglements-fournisseurs.mandat');

    Route::get('/{id}/imputation', function ($id) {
        // Trouver le règlement dans les données mock
        $reglement = [
            'id' => $id,
            'date_reglement' => '2025-01-20',
            'facture' => [
                'id' => 1,
                'numero' => 'PC/025/0001',
                'date_facture' => '2025-01-15'
            ],
            'fournisseur' => [
                'id' => 1,
                'code' => 'FOUR001',
                'nom' => 'Pharmacie Centrale du Bénin',
                'compte_numero' => '401001',
                'ifu' => '0000000000001',
                'rccm' => 'RB/COT/XX-X-00001'
            ],
            'mode_paiement' => 'virement',
            'reference' => 'VIR-2025-001',
            'compte_bancaire' => [
                'banque' => 'ORABANK',
                'numero' => 'BJ123456789'
            ],
            'montant' => 2000000,
            'user' => [
                'name' => 'Admin User'
            ]
        ];

        return Inertia::render('Documents/FicheImputation', [
            'reglement' => $reglement
        ]);
    })->name('reglements-fournisseurs.imputation');
});

// Clients Routes
Route::prefix('clients')->group(function () {
    // Liste des clients
    Route::get('/', function () {
        $clients = [
            [
                'id' => 1,
                'code' => 'CLI-001',
                'nom' => 'Assurance UNIAF',
                'type' => 'assurance',
                'telephone' => '+229 21 31 22 33',
                'email' => 'contact@uniaf.bj',
                'solde' => 3500000,
                'nb_factures' => 12
            ],
            [
                'id' => 2,
                'code' => 'CLI-002',
                'nom' => 'M. Kouadio Jean',
                'type' => 'particulier',
                'telephone' => '+229 97 XX XX XX',
                'email' => 'j.kouadio@email.com',
                'solde' => 150000,
                'nb_factures' => 2
            ],
            [
                'id' => 3,
                'code' => 'CLI-003',
                'nom' => 'Mutuelle MUGEF-CI',
                'type' => 'mutuelle',
                'telephone' => '+229 21 30 45 67',
                'email' => 'info@mugef.ci',
                'solde' => 0,
                'nb_factures' => 8
            ]
        ];

        $stats = [
            'factures_en_cours' => 22,
            'creances_total' => 3650000
        ];

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'stats' => $stats,
            'user' => [
                'name' => 'Utilisateur Test',
                'email' => 'test@example.com'
            ]
        ]);
    })->name('clients.index');

    // Formulaire création
    Route::get('/create', function () {
        return Inertia::render('Clients/Create', [
            'user' => [
                'name' => 'Utilisateur Test',
                'email' => 'test@example.com'
            ]
        ]);
    })->name('clients.create');

    // Formulaire édition
    Route::get('/{id}/edit', function ($id) {
        $client = [
            'id' => $id,
            'code' => 'CLI-001',
            'nom' => 'Assurance UNIAF',
            'type' => 'assurance',
            'telephone' => '+229 21 31 22 33',
            'email' => 'contact@uniaf.bj',
            'adresse' => 'Avenue Jean-Paul II, Cotonou',
            'ifu' => '1234567890123',
            'numero_assurance' => 'ASS-001',
            'notes' => 'Client important'
        ];

        return Inertia::render('Clients/Edit', [
            'client' => $client,
            'user' => [
                'name' => 'Utilisateur Test',
                'email' => 'test@example.com'
            ]
        ]);
    })->name('clients.edit');

    // Détail client
    Route::get('/{id}', function ($id) {
        $client = [
            'id' => $id,
            'code' => 'CLI-001',
            'nom' => 'Assurance UNIAF',
            'type' => 'assurance',
            'telephone' => '+229 21 31 22 33',
            'email' => 'contact@uniaf.bj',
            'adresse' => 'Avenue Jean-Paul II, Cotonou',
            'ifu' => '1234567890123',
            'numero_assurance' => 'ASS-001',
            'notes' => 'Client important'
        ];

        $factures = [
            [
                'id' => 1,
                'numero' => 'FC-2025-001',
                'date_facture' => '2025-01-15',
                'montant_ttc' => 2950000,
                'montant_paye' => 1000000,
                'statut_paiement' => 'partielle'
            ],
            [
                'id' => 2,
                'numero' => 'FC-2025-005',
                'date_facture' => '2025-01-25',
                'montant_ttc' => 500000,
                'montant_paye' => 500000,
                'statut_paiement' => 'payee'
            ]
        ];

        $stats = [
            'nombre_factures' => 12,
            'montant_total' => 3450000,
            'montant_paye' => 1500000,
            'solde' => 1950000
        ];

        return Inertia::render('Clients/Show', [
            'client' => $client,
            'factures' => $factures,
            'stats' => $stats,
            'user' => [
                'name' => 'Utilisateur Test',
                'email' => 'test@example.com'
            ]
        ]);
    })->name('clients.show');

});

// Factures Clients Routes
Route::prefix('factures-clients')->group(function () {
    // Liste des factures clients
    Route::get('/', function () {
        $factures = [
            [
                'id' => 1,
                'numero' => 'FC-2025-001',
                'date_facture' => '2025-01-15',
                'date_echeance' => '2025-02-15',
                'client' => [
                    'id' => 1,
                    'code' => 'CLI-001',
                    'nom' => 'Assurance UNIAF'
                ],
                'montant_ht' => 2500000,
                'montant_tva' => 450000,
                'montant_ttc' => 2950000,
                'reglements' => [
                    ['id' => 1, 'montant' => 1000000]
                ],
                'soldee' => false
            ],
            [
                'id' => 2,
                'numero' => 'FC-2025-002',
                'date_facture' => '2025-01-20',
                'date_echeance' => '2025-02-20',
                'client' => [
                    'id' => 2,
                    'code' => 'CLI-002',
                    'nom' => 'M. Kouadio Jean'
                ],
                'montant_ht' => 120000,
                'montant_tva' => 21600,
                'montant_ttc' => 141600,
                'reglements' => [],
                'soldee' => false
            ]
        ];

        $clients = [
            ['id' => 1, 'code' => 'CLI-001', 'nom' => 'Assurance UNIAF'],
            ['id' => 2, 'code' => 'CLI-002', 'nom' => 'M. Kouadio Jean'],
            ['id' => 3, 'code' => 'CLI-003', 'nom' => 'Mutuelle MUGEF-CI']
        ];

        return Inertia::render('Clients/Factures/Index', [
            'factures' => $factures,
            'clients' => $clients,
            'user' => [
                'name' => 'Utilisateur Test',
                'email' => 'test@example.com'
            ]
        ]);
    })->name('factures-clients.index');

    // Formulaire création facture
    Route::get('/create', function () {
        $clients = [
            ['id' => 1, 'code' => 'CLI-001', 'nom' => 'Assurance UNIAF'],
            ['id' => 2, 'code' => 'CLI-002', 'nom' => 'M. Kouadio Jean'],
            ['id' => 3, 'code' => 'CLI-003', 'nom' => 'Mutuelle MUGEF-CI']
        ];

        return Inertia::render('Clients/Factures/Create', [
            'clients' => $clients,
            'user' => [
                'name' => 'Utilisateur Test',
                'email' => 'test@example.com'
            ]
        ]);
    })->name('factures-clients.create');

    // Détail facture et règlement
    Route::get('/{id}', function ($id) {
        $facture = [
            'id' => $id,
            'numero' => 'FC-2025-001',
            'date_facture' => '2025-01-15',
            'date_echeance' => '2025-02-15',
            'client' => [
                'id' => 1,
                'code' => 'CLI-001',
                'nom' => 'Assurance UNIAF'
            ],
            'description' => 'Consultation et soins hospitaliers',
            'observation' => 'Dossier urgent',
            'montant_ht' => 2500000,
            'montant_tva' => 450000,
            'montant_ttc' => 2950000,
            'reglements' => [
                [
                    'id' => 1,
                    'date_reglement' => '2025-01-20',
                    'mode_paiement' => 'virement',
                    'reference' => 'VIR-CLI-001',
                    'banque' => 'ORABANK',
                    'montant' => 1000000
                ]
            ],
            'soldee' => false
        ];

        return Inertia::render('Clients/Factures/Show', [
            'facture' => $facture,
            'user' => [
                'name' => 'Utilisateur Test',
                'email' => 'test@example.com'
            ]
        ]);
    })->name('factures-clients.show');
});

// Règlements Clients Routes
Route::prefix('reglements-clients')->group(function () {
    Route::get('/', function () {
        $reglements = [
            [
                'id' => 1,
                'date_reglement' => '2025-01-20',
                'facture' => [
                    'id' => 1,
                    'numero' => 'FC-2025-001'
                ],
                'client' => [
                    'id' => 1,
                    'code' => 'CLI-001',
                    'nom' => 'Assurance UNIAF'
                ],
                'mode_paiement' => 'virement',
                'reference' => 'VIR-CLI-001',
                'compte_bancaire' => [
                    'banque' => 'ORABANK',
                    'numero' => 'BJ123456789'
                ],
                'montant' => 1000000,
                'user' => [
                    'name' => 'Admin User'
                ]
            ],
            [
                'id' => 2,
                'date_reglement' => '2025-01-22',
                'facture' => [
                    'id' => 2,
                    'numero' => 'FC-2025-002'
                ],
                'client' => [
                    'id' => 2,
                    'code' => 'CLI-002',
                    'nom' => 'M. Kouadio Jean'
                ],
                'mode_paiement' => 'cheque',
                'reference' => 'CHQ-001',
                'compte_bancaire' => [
                    'banque' => 'BOA BENIN',
                    'numero' => 'BJ987654321'
                ],
                'montant' => 141600,
                'user' => [
                    'name' => 'Admin User'
                ]
            ],
            [
                'id' => 3,
                'date_reglement' => '2025-01-25',
                'facture' => [
                    'id' => 1,
                    'numero' => 'FC-2025-001'
                ],
                'client' => [
                    'id' => 1,
                    'code' => 'CLI-001',
                    'nom' => 'Assurance UNIAF'
                ],
                'mode_paiement' => 'especes',
                'reference' => null,
                'compte_bancaire' => null,
                'montant' => 500000,
                'user' => [
                    'name' => 'Admin User'
                ]
            ]
        ];

        $clients = [
            ['id' => 1, 'nom' => 'Assurance UNIAF'],
            ['id' => 2, 'nom' => 'M. Kouadio Jean'],
            ['id' => 3, 'nom' => 'Mutuelle MUGEF-CI']
        ];

        $stats = [
            'total_reglements' => 1641600,
            'reglements_mois' => 1641600,
            'nombre_reglements' => count($reglements),
            'montant_moyen' => 1641600 / count($reglements)
        ];

        return Inertia::render('ReglementClients/Index', [
            'reglements' => $reglements,
            'clients' => $clients,
            'stats' => $stats,
            'pagination' => [
                'current_page' => 1,
                'per_page' => 20,
                'total' => count($reglements)
            ],
            'user' => [
                'name' => 'Utilisateur Test',
                'email' => 'test@example.com'
            ]
        ]);
    })->name('reglements-clients.index');

    // Reçu de règlement
    Route::get('/{id}/recu', function ($id) {
        $reglement = [
            'id' => $id,
            'date_reglement' => '2025-01-20',
            'facture' => [
                'id' => 1,
                'numero' => 'FC-2025-001',
                'date_facture' => '2025-01-15'
            ],
            'client' => [
                'id' => 1,
                'code' => 'CLI-001',
                'nom' => 'Assurance UNIAF',
                'ifu' => '1234567890123'
            ],
            'mode_paiement' => 'virement',
            'reference' => 'VIR-CLI-001',
            'compte_bancaire' => [
                'banque' => 'ORABANK',
                'numero' => 'BJ123456789'
            ],
            'montant' => 1000000,
            'user' => [
                'name' => 'Admin User'
            ]
        ];

        return Inertia::render('Documents/RecuPaiement', [
            'reglement' => $reglement,
            'type' => 'client'
        ]);
    })->name('reglements-clients.recu');
});

// Plan Comptable Routes
Route::prefix('plan-comptable')->group(function () {
    Route::get('/', [PlanComptableController::class, 'index'])->name('plan-comptable.index');
});

// API Plan Comptable
Route::prefix('api/plan-comptable')->group(function () {
    Route::get('/search', [PlanComptableController::class, 'search'])->name('api.plan-comptable.search');
    Route::post('/', [PlanComptableController::class, 'store'])->name('api.plan-comptable.store');
    Route::get('/{compte}', [PlanComptableController::class, 'show'])->name('api.plan-comptable.show');
    Route::delete('/{compte}', [PlanComptableController::class, 'destroy'])->name('api.plan-comptable.destroy');
});

// Banques Routes
Route::prefix('banques')->group(function () {
    Route::get('/', [BanqueController::class, 'index'])->name('banques.index');
});

// API Banques
Route::prefix('api')->group(function () {
    // Banques
    Route::post('/banques', [BanqueController::class, 'storeBanque'])->name('api.banques.store');
    Route::get('/banques/liste', [BanqueController::class, 'listeBanques'])->name('api.banques.liste');
    Route::delete('/banques/{banque}', [BanqueController::class, 'destroyBanque'])->name('api.banques.destroy');

    // Comptes bancaires
    Route::post('/comptes-bancaires', [BanqueController::class, 'storeCompte'])->name('api.comptes-bancaires.store');
    Route::get('/comptes-bancaires/liste', [BanqueController::class, 'listeComptes'])->name('api.comptes-bancaires.liste');

    // Approvisionnements
    Route::post('/banques/approvisionnement', [BanqueController::class, 'storeApprovisionnement'])->name('api.banques.approvisionnement');
});

// Rapports Routes
Route::prefix('rapports')->group(function () {
    // Rapports Fournisseurs
    Route::prefix('fournisseurs')->group(function () {
        // Page index des rapports fournisseurs
        Route::get('/', function () {
            $fournisseurs = [
                ['id' => 1, 'code' => 'FOUR001', 'nom' => 'Pharmacie Centrale du Bénin'],
                ['id' => 2, 'code' => 'FOUR002', 'nom' => 'SOBEMAP Matériel Médical'],
                ['id' => 3, 'code' => 'FOUR003', 'nom' => 'SONEB (Eau)'],
            ];

            return Inertia::render('Rapports/Fournisseurs/Index', [
                'fournisseurs' => $fournisseurs,
                'user' => [
                    'name' => 'Utilisateur Test',
                    'email' => 'test@example.com'
                ]
            ]);
        })->name('rapports.fournisseurs');

        // État de règlement d'une facture
        Route::get('/etat-reglement-facture', function () {
            $facture = [
                'id' => 1,
                'numero' => 'PC/025/0001',
                'date_facture' => '2025-01-15',
                'date_echeance' => '2025-02-15',
                'reference' => 'REF-001',
                'fournisseur' => [
                    'code' => 'FOUR001',
                    'nom' => 'Pharmacie Centrale du Bénin'
                ],
                'montant_ht' => 5000000,
                'montant_tva' => 900000,
                'montant_aib' => 50000,
                'montant_escompte' => 0,
                'montant_ttc' => 5950000
            ];

            $reglements = [
                [
                    'id' => 1,
                    'date_reglement' => '2025-01-20',
                    'mode_paiement' => 'virement',
                    'reference' => 'VIR-2025-001',
                    'compte_bancaire' => ['banque' => 'ORABANK', 'numero' => 'BJ123456789'],
                    'montant' => 2000000,
                    'user' => ['name' => 'Admin User']
                ],
                [
                    'id' => 2,
                    'date_reglement' => '2025-01-25',
                    'mode_paiement' => 'cheque',
                    'reference' => 'CHQ-2025-010',
                    'compte_bancaire' => ['banque' => 'BOA BENIN', 'numero' => 'BJ987654321'],
                    'montant' => 1950000,
                    'user' => ['name' => 'Admin User']
                ]
            ];

            return Inertia::render('Rapports/Fournisseurs/EtatReglementFacture', [
                'facture' => $facture,
                'reglements' => $reglements
            ]);
        })->name('rapports.fournisseurs.etat-reglement-facture');

        // Mouvement périodique fournisseur
        Route::get('/mouvement-periodique', function () {
            $fournisseur = [
                'id' => 1,
                'code' => 'FOUR001',
                'nom' => 'Pharmacie Centrale du Bénin',
                'compte_numero' => '401001',
                'contact' => 'M. Adamou'
            ];

            $factures = [
                [
                    'id' => 1,
                    'numero' => 'PC/025/0001',
                    'date_facture' => '2025-01-15',
                    'reference' => 'REF-001',
                    'montant_ht' => 5000000,
                    'montant_tva' => 900000,
                    'montant_aib' => 50000,
                    'montant_ttc' => 5950000,
                    'montant_paye' => 3950000
                ],
                [
                    'id' => 2,
                    'numero' => 'PC/025/0005',
                    'date_facture' => '2025-01-25',
                    'reference' => 'REF-005',
                    'montant_ht' => 3000000,
                    'montant_tva' => 540000,
                    'montant_aib' => 30000,
                    'montant_ttc' => 3570000,
                    'montant_paye' => 3570000
                ]
            ];

            $periode = [
                'debut' => '2025-01-01',
                'fin' => '2025-01-31'
            ];

            return Inertia::render('Rapports/Fournisseurs/MouvementPeriodique', [
                'fournisseur' => $fournisseur,
                'factures' => $factures,
                'periode' => $periode
            ]);
        })->name('rapports.fournisseurs.mouvement-periodique');

        // Situation des fournisseurs
        Route::get('/situation-fournisseurs', function () {
            $fournisseurs = [
                [
                    'id' => 1,
                    'code' => 'FOUR001',
                    'nom' => 'Pharmacie Centrale du Bénin',
                    'compte_numero' => '401001',
                    'nb_factures' => 5,
                    'total_facture' => 15000000,
                    'total_paye' => 10000000,
                    'dette' => 5000000
                ],
                [
                    'id' => 2,
                    'code' => 'FOUR002',
                    'nom' => 'SOBEMAP Matériel Médical',
                    'compte_numero' => '401002',
                    'nb_factures' => 3,
                    'total_facture' => 8000000,
                    'total_paye' => 8000000,
                    'dette' => 0
                ]
            ];

            return Inertia::render('Rapports/Fournisseurs/SituationFournisseurs', [
                'fournisseurs' => $fournisseurs,
                'date_situation' => '2025-01-31'
            ]);
        })->name('rapports.fournisseurs.situation-fournisseurs');

        // Factures réglées
        Route::get('/factures-reglees', function () {
            $factures = [
                [
                    'id' => 1,
                    'numero' => 'PC/025/0002',
                    'date_reglement_final' => '2025-01-20',
                    'fournisseur' => ['nom' => 'SOBEMAP Matériel Médical'],
                    'montant_ttc' => 3570000,
                    'montant_paye' => 3570000,
                    'mode_paiement_final' => 'Virement',
                    'reference_final' => 'VIR-2025-002'
                ]
            ];

            $periode = ['debut' => '2025-01-01', 'fin' => '2025-01-31'];

            return Inertia::render('Rapports/Fournisseurs/FacturesReglees', [
                'factures' => $factures,
                'periode' => $periode
            ]);
        })->name('rapports.fournisseurs.factures-reglees');

        // Déclaration AIB
        Route::get('/declaration-aib', function () {
            $factures = [
                [
                    'id' => 1,
                    'numero' => 'PC/025/0001',
                    'date_facture' => '2025-01-15',
                    'fournisseur' => ['nom' => 'Pharmacie Centrale du Bénin'],
                    'montant_ht' => 5000000,
                    'taux_aib' => 1,
                    'montant_aib' => 50000
                ],
                [
                    'id' => 2,
                    'numero' => 'PC/025/0003',
                    'date_facture' => '2025-01-20',
                    'fournisseur' => ['nom' => 'SOBEMAP Matériel Médical'],
                    'montant_ht' => 3000000,
                    'taux_aib' => 3,
                    'montant_aib' => 90000
                ]
            ];

            $periode = ['debut' => '2025-01-01', 'fin' => '2025-01-31'];

            return Inertia::render('Rapports/Fournisseurs/DeclarationAIB', [
                'factures' => $factures,
                'periode' => $periode
            ]);
        })->name('rapports.fournisseurs.declaration-aib');

        // Déclaration TVA
        Route::get('/declaration-tva', function () {
            $factures = [
                [
                    'id' => 1,
                    'numero' => 'PC/025/0001',
                    'date_facture' => '2025-01-15',
                    'fournisseur' => ['nom' => 'Pharmacie Centrale du Bénin'],
                    'montant_ht' => 5000000,
                    'montant_tva' => 900000,
                    'montant_ttc' => 5900000
                ]
            ];

            $periode = ['debut' => '2025-01-01', 'fin' => '2025-01-31'];

            return Inertia::render('Rapports/Fournisseurs/DeclarationTVA', [
                'factures' => $factures,
                'periode' => $periode
            ]);
        })->name('rapports.fournisseurs.declaration-tva');

        // Bordereau de transmission
        Route::get('/bordereau-transmission', function () {
            $reglements = [
                [
                    'id' => 1,
                    'date_reglement' => '2025-01-20',
                    'fournisseur' => ['nom' => 'Pharmacie Centrale du Bénin'],
                    'facture' => ['numero' => 'PC/025/0001'],
                    'mode_paiement' => 'virement',
                    'reference' => 'VIR-2025-001',
                    'montant' => 2000000
                ]
            ];

            return Inertia::render('Rapports/Fournisseurs/BordereauTransmission', [
                'reglements' => $reglements,
                'numero_bordereau' => 'BT-2025-001',
                'date_bordereau' => '2025-01-31'
            ]);
        })->name('rapports.fournisseurs.bordereau-transmission');

        // Récapitulatif des charges
        Route::get('/recap-charges', function () {
            $factures = [
                [
                    'id' => 1,
                    'numero' => 'PC/025/0001',
                    'date_facture' => '2025-01-15',
                    'fournisseur' => ['nom' => 'Pharmacie Centrale du Bénin'],
                    'categorie' => 'Achats de médicaments',
                    'compte_imputation' => '601100',
                    'montant_ht' => 5000000,
                    'montant_tva' => 900000,
                    'montant_ttc' => 5900000
                ]
            ];

            $periode = ['debut' => '2025-01-01', 'fin' => '2025-01-31'];

            return Inertia::render('Rapports/Fournisseurs/RecapCharges', [
                'factures' => $factures,
                'periode' => $periode
            ]);
        })->name('rapports.fournisseurs.recap-charges');

        // Récapitulatif des investissements
        Route::get('/recap-investissements', function () {
            $factures = [
                [
                    'id' => 1,
                    'numero' => 'PC/025/0010',
                    'date_facture' => '2025-01-10',
                    'fournisseur' => ['nom' => 'SOBEMAP Matériel Médical'],
                    'description' => 'Scanner médical',
                    'type_immo' => 'Matériel médical',
                    'compte_immo' => '221000',
                    'montant_ht' => 15000000,
                    'montant_tva' => 2700000,
                    'montant_ttc' => 17700000
                ]
            ];

            $periode = ['debut' => '2025-01-01', 'fin' => '2025-01-31'];

            return Inertia::render('Rapports/Fournisseurs/RecapInvestissements', [
                'factures' => $factures,
                'periode' => $periode
            ]);
        })->name('rapports.fournisseurs.recap-investissements');

        // Factures et soldes
        Route::get('/factures-soldes', function () {
            $factures = [
                [
                    'id' => 1,
                    'numero' => 'PC/025/0001',
                    'date_facture' => '2025-01-15',
                    'fournisseur' => ['nom' => 'Pharmacie Centrale du Bénin'],
                    'montant_ttc' => 5950000,
                    'reglements' => [
                        [
                            'id' => 1,
                            'date_reglement' => '2025-01-20',
                            'mode_paiement' => 'virement',
                            'reference' => 'VIR-2025-001',
                            'banque' => 'ORABANK',
                            'montant' => 2000000
                        ],
                        [
                            'id' => 2,
                            'date_reglement' => '2025-01-25',
                            'mode_paiement' => 'cheque',
                            'reference' => 'CHQ-2025-010',
                            'banque' => 'BOA BENIN',
                            'montant' => 1950000
                        ]
                    ]
                ]
            ];

            $periode = ['debut' => '2025-01-01', 'fin' => '2025-01-31'];

            return Inertia::render('Rapports/Fournisseurs/FacturesSoldes', [
                'factures' => $factures,
                'periode' => $periode
            ]);
        })->name('rapports.fournisseurs.factures-soldes');
    });

    // Rapports Clients
    Route::prefix('clients')->group(function () {
        // Page index des rapports clients
        Route::get('/', function () {
            $clients = [
                ['id' => 1, 'code' => 'CLI-001', 'nom' => 'Assurance UNIAF'],
                ['id' => 2, 'code' => 'CLI-002', 'nom' => 'M. Kouadio Jean'],
                ['id' => 3, 'code' => 'CLI-003', 'nom' => 'Mutuelle MUGEF-CI']
            ];

            return Inertia::render('Rapports/Clients/Index', [
                'clients' => $clients,
                'user' => [
                    'name' => 'Utilisateur Test',
                    'email' => 'test@example.com'
                ]
            ]);
        })->name('rapports.clients');

        // État des règlements
        Route::get('/etat-reglements', function () {
            $reglements = [
                [
                    'id' => 1,
                    'date_reglement' => '2025-01-20',
                    'client' => ['id' => 1, 'code' => 'CLI-001', 'nom' => 'Assurance UNIAF'],
                    'facture' => ['numero' => 'FC-2025-001'],
                    'mode_paiement' => 'virement',
                    'reference' => 'VIR-CLI-001',
                    'banque' => 'ORABANK',
                    'montant' => 1000000
                ],
                [
                    'id' => 2,
                    'date_reglement' => '2025-01-22',
                    'client' => ['id' => 2, 'code' => 'CLI-002', 'nom' => 'M. Kouadio Jean'],
                    'facture' => ['numero' => 'FC-2025-002'],
                    'mode_paiement' => 'cheque',
                    'reference' => 'CHQ-001',
                    'banque' => 'BOA BENIN',
                    'montant' => 141600
                ]
            ];

            $periode = ['debut' => '2025-01-01', 'fin' => '2025-01-31'];

            return Inertia::render('Rapports/Clients/EtatReglements', [
                'reglements' => $reglements,
                'periode' => $periode
            ]);
        })->name('rapports.clients.etat-reglements');

        // État des créances
        Route::get('/etat-creances', function () {
            $factures = [
                [
                    'id' => 1,
                    'numero' => 'FC-2025-001',
                    'date_facture' => '2025-01-15',
                    'date_echeance' => '2025-02-15',
                    'client' => ['id' => 1, 'code' => 'CLI-001', 'nom' => 'Assurance UNIAF'],
                    'montant_ttc' => 2950000,
                    'reglements' => [['montant' => 1000000]]
                ],
                [
                    'id' => 2,
                    'numero' => 'FC-2025-002',
                    'date_facture' => '2025-01-20',
                    'date_echeance' => '2025-02-20',
                    'client' => ['id' => 2, 'code' => 'CLI-002', 'nom' => 'M. Kouadio Jean'],
                    'montant_ttc' => 141600,
                    'reglements' => []
                ]
            ];

            $periode = ['debut' => '2025-01-01', 'fin' => '2025-01-31'];

            return Inertia::render('Rapports/Clients/EtatCreances', [
                'factures' => $factures,
                'periode' => $periode
            ]);
        })->name('rapports.clients.etat-creances');

        // Brouillard de chèques
        Route::get('/brouillard-cheques', function () {
            $cheques = [
                [
                    'id' => 1,
                    'date_reglement' => '2025-01-22',
                    'client' => ['id' => 2, 'code' => 'CLI-002', 'nom' => 'M. Kouadio Jean'],
                    'facture' => ['numero' => 'FC-2025-002'],
                    'reference' => 'CHQ-001',
                    'banque' => 'BOA BENIN',
                    'montant' => 141600,
                    'statut_cheque' => 'en_attente'
                ]
            ];

            $periode = ['debut' => '2025-01-01', 'fin' => '2025-01-31'];

            return Inertia::render('Rapports/Clients/BrouillardCheques', [
                'cheques' => $cheques,
                'periode' => $periode
            ]);
        })->name('rapports.clients.brouillard-cheques');

        // Chiffre d'affaires
        Route::get('/chiffre-affaires', function () {
            $clients = [
                [
                    'id' => 1,
                    'code' => 'CLI-001',
                    'nom' => 'Assurance UNIAF',
                    'type' => 'assurance',
                    'nb_factures' => 12,
                    'ca_ht' => 25000000,
                    'ca_tva' => 4500000,
                    'ca_ttc' => 29500000,
                    'ca_encaisse' => 26000000
                ],
                [
                    'id' => 2,
                    'code' => 'CLI-002',
                    'nom' => 'M. Kouadio Jean',
                    'type' => 'particulier',
                    'nb_factures' => 2,
                    'ca_ht' => 200000,
                    'ca_tva' => 36000,
                    'ca_ttc' => 236000,
                    'ca_encaisse' => 86400
                ]
            ];

            $periode = ['debut' => '2025-01-01', 'fin' => '2025-01-31'];

            return Inertia::render('Rapports/Clients/ChiffreAffaires', [
                'clients' => $clients,
                'periode' => $periode
            ]);
        })->name('rapports.clients.chiffre-affaires');

        // Pertes, rejets et régularisations
        Route::get('/pertes-rejets', function () {
            $pertes = [
                [
                    'id' => 1,
                    'date_operation' => '2025-01-15',
                    'client' => ['code' => 'CLI-099', 'nom' => 'Entreprise ABC'],
                    'facture' => ['numero' => 'FC-2024-089'],
                    'motif' => 'Client en faillite',
                    'montant' => 500000,
                    'decision' => 'Abandon de créance'
                ]
            ];

            $rejets = [
                [
                    'id' => 1,
                    'date_rejet' => '2025-01-18',
                    'client' => ['code' => 'CLI-002', 'nom' => 'M. Kouadio Jean'],
                    'numero_cheque' => 'CHQ-0012345',
                    'banque' => 'BOA BENIN',
                    'motif_rejet' => 'Provision insuffisante',
                    'montant' => 141600,
                    'statut' => 'en_attente'
                ]
            ];

            $regularisations = [
                [
                    'id' => 1,
                    'date_operation' => '2025-01-25',
                    'client' => ['code' => 'CLI-001', 'nom' => 'Assurance UNIAF'],
                    'type' => 'avoir',
                    'description' => 'Avoir sur facture FC-2025-001',
                    'montant' => 100000,
                    'reference' => 'AV-2025-001'
                ]
            ];

            $periode = ['debut' => '2025-01-01', 'fin' => '2025-01-31'];

            return Inertia::render('Rapports/Clients/PertesRejets', [
                'pertes' => $pertes,
                'rejets' => $rejets,
                'regularisations' => $regularisations,
                'periode' => $periode
            ]);
        })->name('rapports.clients.pertes-rejets');
    });

    Route::get('/comptables', function () {
        return Inertia::render('Dashboard'); // Placeholder
    })->name('rapports.comptables');
});

// Paramètres Routes
Route::get('/utilisateurs', function () {
    return Inertia::render('Dashboard'); // Placeholder
})->name('utilisateurs.index');

Route::get('/taux-fiscaux', function () {
    return Inertia::render('Dashboard'); // Placeholder
})->name('taux-fiscaux.index');

// User Profile Routes
Route::get('/profile', function () {
    return Inertia::render('Dashboard'); // Placeholder
})->name('profile');

Route::get('/settings', function () {
    return Inertia::render('Dashboard'); // Placeholder
})->name('settings');
