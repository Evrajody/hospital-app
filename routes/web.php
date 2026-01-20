<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

// Fournisseurs Routes
Route::prefix('fournisseurs')->group(function () {
    // Liste des fournisseurs
    Route::get('/', function () {
        // Mock data - TODO: Replace with actual database queries
        $fournisseurs = [
            [
                'id' => 1,
                'code' => 'FOUR001',
                'nom' => 'Pharmacie Centrale du Bénin',
                'contact' => 'M. Adamou',
                'telephone' => '+229 21 30 45 67',
                'email' => 'contact@pcb.bj',
                'status' => 'actif',
                'compte_comptable' => [
                    'numero' => '401001',
                    'libelle' => 'Pharmacie Centrale du Bénin'
                ]
            ],
            [
                'id' => 2,
                'code' => 'FOUR002',
                'nom' => 'SOBEMAP Matériel Médical',
                'contact' => 'Mme Koffi',
                'telephone' => '+229 21 31 22 33',
                'email' => 'info@sobemap.bj',
                'status' => 'actif',
                'compte_comptable' => [
                    'numero' => '401002',
                    'libelle' => 'SOBEMAP Matériel Médical'
                ]
            ],
            [
                'id' => 3,
                'code' => 'FOUR003',
                'nom' => 'SONEB (Eau)',
                'contact' => 'Service Client',
                'telephone' => '+229 21 30 00 00',
                'email' => 'clientele@soneb.bj',
                'status' => 'actif',
                'compte_comptable' => [
                    'numero' => '401003',
                    'libelle' => 'SONEB (Eau)'
                ]
            ]
        ];

        $comptesFournisseurs = [
            ['id' => 1, 'numero' => '401001', 'libelle' => 'Pharmacie Centrale du Bénin'],
            ['id' => 2, 'numero' => '401002', 'libelle' => 'SOBEMAP Matériel Médical'],
            ['id' => 3, 'numero' => '401003', 'libelle' => 'SONEB (Eau)'],
            ['id' => 4, 'numero' => '401004', 'libelle' => 'SBEE (Électricité)'],
        ];

        return Inertia::render('Fournisseurs/Index', [
            'fournisseurs' => $fournisseurs,
            'comptesFournisseurs' => $comptesFournisseurs,
            'pagination' => [
                'current_page' => 1,
                'per_page' => 20,
                'total' => count($fournisseurs)
            ],
            'user' => [
                'name' => 'Utilisateur Test',
                'email' => 'test@example.com'
            ]
        ]);
    })->name('fournisseurs.index');

    // Formulaire création
    Route::get('/create', function () {
        $comptesFournisseurs = [
            ['id' => 1, 'numero' => '401001', 'libelle' => 'Pharmacie Centrale du Bénin'],
            ['id' => 2, 'numero' => '401002', 'libelle' => 'SOBEMAP Matériel Médical'],
            ['id' => 3, 'numero' => '401003', 'libelle' => 'SONEB (Eau)'],
            ['id' => 4, 'numero' => '401004', 'libelle' => 'SBEE (Électricité)'],
        ];

        $comptesParents = [
            ['id' => 10, 'numero' => '401000', 'libelle' => 'Fournisseurs'],
            ['id' => 11, 'numero' => '401100', 'libelle' => 'Fournisseurs - Médicaments'],
            ['id' => 12, 'numero' => '401200', 'libelle' => 'Fournisseurs - Matériel médical'],
            ['id' => 13, 'numero' => '401300', 'libelle' => 'Fournisseurs - Services'],
        ];

        return Inertia::render('Fournisseurs/Form', [
            'comptesFournisseurs' => $comptesFournisseurs,
            'comptesParents' => $comptesParents,
            'user' => [
                'name' => 'Utilisateur Test',
                'email' => 'test@example.com'
            ]
        ]);
    })->name('fournisseurs.create');

    // Formulaire édition
    Route::get('/{id}/edit', function ($id) {
        $fournisseur = [
            'id' => $id,
            'code' => 'FOUR001',
            'nom' => 'Pharmacie Centrale du Bénin',
            'contact' => 'M. Adamou',
            'telephone' => '+229 21 30 45 67',
            'email' => 'contact@pcb.bj',
            'adresse' => 'Avenue Clozel, Cotonou',
            'status' => 'actif',
            'compte_comptable_id' => 1,
            'ifu' => '0000000000000',
            'rccm' => 'RB/COT/XX-X-XXXXX',
            'remarques' => ''
        ];

        $comptesFournisseurs = [
            ['id' => 1, 'numero' => '401001', 'libelle' => 'Pharmacie Centrale du Bénin'],
            ['id' => 2, 'numero' => '401002', 'libelle' => 'SOBEMAP Matériel Médical'],
        ];

        $comptesParents = [
            ['id' => 10, 'numero' => '401000', 'libelle' => 'Fournisseurs'],
        ];

        return Inertia::render('Fournisseurs/Form', [
            'fournisseur' => $fournisseur,
            'comptesFournisseurs' => $comptesFournisseurs,
            'comptesParents' => $comptesParents,
            'user' => [
                'name' => 'Utilisateur Test',
                'email' => 'test@example.com'
            ]
        ]);
    })->name('fournisseurs.edit');

    // Détail fournisseur
    Route::get('/{id}', function ($id) {
        $fournisseur = [
            'id' => $id,
            'code' => 'FOUR001',
            'nom' => 'Pharmacie Centrale du Bénin',
            'contact' => 'M. Adamou',
            'telephone' => '+229 21 30 45 67',
            'email' => 'contact@pcb.bj',
            'adresse' => 'Avenue Clozel, Cotonou',
            'status' => 'actif',
            'compte_comptable' => [
                'numero' => '401001',
                'libelle' => 'Pharmacie Centrale du Bénin'
            ],
            'ifu' => '0000000000001',
            'rccm' => 'RB/COT/XX-X-00001',
            'remarques' => 'Fournisseur principal de médicaments'
        ];

        $factures = [
            [
                'id' => 1,
                'numero' => 'PC/025/0001',
                'date_facture' => '2025-01-15',
                'montant_ttc' => 5950000,
                'montant_paye' => 2000000,
                'statut_paiement' => 'partielle'
            ],
            [
                'id' => 4,
                'numero' => 'PC/025/0004',
                'date_facture' => '2025-01-10',
                'montant_ttc' => 2500000,
                'montant_paye' => 2500000,
                'statut_paiement' => 'payee'
            ]
        ];

        $stats = [
            'nombre_factures' => 2,
            'montant_total' => 8450000,
            'montant_paye' => 4500000,
            'montant_reste' => 3950000
        ];

        return Inertia::render('Fournisseurs/Show', [
            'fournisseur' => $fournisseur,
            'factures' => $factures,
            'stats' => $stats,
            'user' => [
                'name' => 'Utilisateur Test',
                'email' => 'test@example.com'
            ]
        ]);
    })->name('fournisseurs.show');
});

// Factures Fournisseurs Routes
Route::prefix('factures-fournisseurs')->group(function () {
    // Liste des factures
    Route::get('/', function () {
        $factures = [
            [
                'id' => 1,
                'numero' => 'PC/025/0001',
                'date_facture' => '2025-01-15',
                'reference' => 'REF-001',
                'fournisseur' => [
                    'id' => 1,
                    'code' => 'FOUR001',
                    'nom' => 'Pharmacie Centrale du Bénin'
                ],
                'montant_ht' => 5000000,
                'montant_tva' => 900000,
                'montant_aib' => 50000,
                'montant_ttc' => 5950000,
                'montant_paye' => 0,
                'statut_paiement' => 'impayee'
            ],
            [
                'id' => 2,
                'numero' => 'PC/025/0002',
                'date_facture' => '2025-01-20',
                'reference' => 'REF-002',
                'fournisseur' => [
                    'id' => 2,
                    'code' => 'FOUR002',
                    'nom' => 'SOBEMAP Matériel Médical'
                ],
                'montant_ht' => 3000000,
                'montant_tva' => 540000,
                'montant_aib' => 90000,
                'montant_ttc' => 3630000,
                'montant_paye' => 2000000,
                'statut_paiement' => 'partielle'
            ],
            [
                'id' => 3,
                'numero' => 'PC/025/0003',
                'date_facture' => '2025-01-25',
                'reference' => 'REF-003',
                'fournisseur' => [
                    'id' => 3,
                    'code' => 'FOUR003',
                    'nom' => 'SONEB (Eau)'
                ],
                'montant_ht' => 150000,
                'montant_tva' => 27000,
                'montant_aib' => 0,
                'montant_ttc' => 177000,
                'montant_paye' => 177000,
                'statut_paiement' => 'payee'
            ]
        ];

        $fournisseurs = [
            ['id' => 1, 'nom' => 'Pharmacie Centrale du Bénin'],
            ['id' => 2, 'nom' => 'SOBEMAP Matériel Médical'],
            ['id' => 3, 'nom' => 'SONEB (Eau)']
        ];

        $stats = [
            'total' => count($factures),
            'montant_impaye' => 5950000,
            'montant_partiel' => 3630000,
            'montant_paye' => 177000
        ];

        return Inertia::render('FacturesFournisseurs/Index', [
            'factures' => $factures,
            'fournisseurs' => $fournisseurs,
            'stats' => $stats,
            'pagination' => [
                'current_page' => 1,
                'per_page' => 20,
                'total' => count($factures)
            ],
            'user' => [
                'name' => 'Utilisateur Test',
                'email' => 'test@example.com'
            ]
        ]);
    })->name('factures-fournisseurs.index');

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

        return Inertia::render('FacturesFournisseurs/Form', [
            'fournisseurs' => $fournisseurs,
            'comptesImputation' => $comptesImputation,
            'user' => [
                'name' => 'Utilisateur Test',
                'email' => 'test@example.com'
            ]
        ]);
    })->name('factures-fournisseurs.create');

    // Détail facture
    Route::get('/{id}', function ($id) {
        $facture = [
            'id' => $id,
            'numero' => 'PC/025/0001',
            'date_facture' => '2025-01-15',
            'date_echeance' => '2025-02-15',
            'reference' => 'REF-001',
            'fournisseur' => [
                'id' => 1,
                'code' => 'FOUR001',
                'nom' => 'Pharmacie Centrale du Bénin',
                'contact' => 'M. Adamou',
                'telephone' => '+229 21 30 45 67',
                'email' => 'contact@pcb.bj'
            ],
            'remarques' => 'Commande urgente de médicaments essentiels',
            'lignes' => [
                [
                    'id' => 1,
                    'description' => 'Paracétamol 500mg (Boîte de 1000)',
                    'compte_imputation' => [
                        'numero' => '601100',
                        'libelle' => 'Achats de médicaments'
                    ],
                    'quantite' => 50,
                    'prix_unitaire' => 80000,
                    'taux_tva' => 18,
                    'taux_aib' => 1,
                    'taux_escompte' => 0,
                    'montant_ht' => 4000000
                ],
                [
                    'id' => 2,
                    'description' => 'Amoxicilline 1g (Boîte de 500)',
                    'compte_imputation' => [
                        'numero' => '601100',
                        'libelle' => 'Achats de médicaments'
                    ],
                    'quantite' => 20,
                    'prix_unitaire' => 50000,
                    'taux_tva' => 18,
                    'taux_aib' => 1,
                    'taux_escompte' => 0,
                    'montant_ht' => 1000000
                ]
            ],
            'montant_ht' => 5000000,
            'montant_tva' => 900000,
            'montant_aib' => 50000,
            'montant_escompte' => 0,
            'montant_ttc' => 5950000,
            'montant_paye' => 2000000,
            'statut_paiement' => 'partielle'
        ];

        $reglements = [
            [
                'id' => 1,
                'date_reglement' => '2025-01-20',
                'mode_paiement' => 'virement',
                'montant' => 2000000,
                'reference' => 'VIR-2025-001',
                'compte_bancaire' => [
                    'banque' => 'ORABANK',
                    'numero' => 'BJ123456789'
                ],
                'remarques' => 'Premier acompte'
            ]
        ];

        return Inertia::render('FacturesFournisseurs/Show', [
            'facture' => $facture,
            'reglements' => $reglements,
            'user' => [
                'name' => 'Utilisateur Test',
                'email' => 'test@example.com'
            ]
        ]);
    })->name('factures-fournisseurs.show');

    // Formulaire règlement
    Route::get('/{id}/regler', function ($id) {
        $facture = [
            'id' => $id,
            'numero' => 'PC/025/0001',
            'date_facture' => '2025-01-15',
            'reference' => 'REF-001',
            'fournisseur' => [
                'id' => 1,
                'code' => 'FOUR001',
                'nom' => 'Pharmacie Centrale du Bénin'
            ],
            'montant_ht' => 5000000,
            'montant_tva' => 900000,
            'montant_aib' => 50000,
            'montant_ttc' => 5950000,
            'montant_paye' => 2000000,
            'statut_paiement' => 'partielle'
        ];

        $reglements = [
            [
                'id' => 1,
                'date_reglement' => '2025-01-20',
                'mode_paiement' => 'virement',
                'montant' => 2000000,
                'reference' => 'VIR-2025-001',
                'compte_bancaire' => [
                    'banque' => 'ORABANK',
                    'numero' => 'BJ123456789'
                ]
            ]
        ];

        $comptesBancaires = [
            ['id' => 1, 'banque' => 'ORABANK', 'numero' => 'BJ123456789', 'libelle' => 'Compte Courant'],
            ['id' => 2, 'banque' => 'BOA BENIN', 'numero' => 'BJ987654321', 'libelle' => 'Compte Dépenses'],
        ];

        return Inertia::render('FacturesFournisseurs/Reglement', [
            'facture' => $facture,
            'reglements' => $reglements,
            'comptesBancaires' => $comptesBancaires,
            'user' => [
                'name' => 'Utilisateur Test',
                'email' => 'test@example.com'
            ]
        ]);
    })->name('factures-fournisseurs.regler');

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

        return Inertia::render('FacturesFournisseurs/Form', [
            'facture' => $facture,
            'fournisseurs' => $fournisseurs,
            'comptesImputation' => $comptesImputation,
            'user' => [
                'name' => 'Utilisateur Test',
                'email' => 'test@example.com'
            ]
        ]);
    })->name('factures-fournisseurs.edit');
});

// Règlements Fournisseurs Routes
Route::prefix('reglements-fournisseurs')->group(function () {
    Route::get('/', function () {
        $reglements = [
            [
                'id' => 1,
                'date_reglement' => '2025-01-20',
                'facture' => [
                    'id' => 1,
                    'numero' => 'PC/025/0001'
                ],
                'fournisseur' => [
                    'id' => 1,
                    'code' => 'FOUR001',
                    'nom' => 'Pharmacie Centrale du Bénin'
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
            ],
            [
                'id' => 2,
                'date_reglement' => '2025-01-22',
                'facture' => [
                    'id' => 2,
                    'numero' => 'PC/025/0002'
                ],
                'fournisseur' => [
                    'id' => 2,
                    'code' => 'FOUR002',
                    'nom' => 'SOBEMAP Matériel Médical'
                ],
                'mode_paiement' => 'cheque',
                'reference' => 'CHQ-2025-005',
                'compte_bancaire' => [
                    'banque' => 'BOA BENIN',
                    'numero' => 'BJ987654321'
                ],
                'montant' => 1500000,
                'user' => [
                    'name' => 'Admin User'
                ]
            ],
            [
                'id' => 3,
                'date_reglement' => '2025-01-25',
                'facture' => [
                    'id' => 3,
                    'numero' => 'PC/025/0003'
                ],
                'fournisseur' => [
                    'id' => 3,
                    'code' => 'FOUR003',
                    'nom' => 'SONEB (Eau)'
                ],
                'mode_paiement' => 'especes',
                'reference' => null,
                'compte_bancaire' => null,
                'montant' => 177000,
                'user' => [
                    'name' => 'Admin User'
                ]
            ],
            [
                'id' => 4,
                'date_reglement' => '2025-01-26',
                'facture' => [
                    'id' => 2,
                    'numero' => 'PC/025/0002'
                ],
                'fournisseur' => [
                    'id' => 2,
                    'code' => 'FOUR002',
                    'nom' => 'SOBEMAP Matériel Médical'
                ],
                'mode_paiement' => 'mobile_money',
                'reference' => 'MM-2025-123',
                'compte_bancaire' => null,
                'montant' => 500000,
                'user' => [
                    'name' => 'Admin User'
                ]
            ]
        ];

        $fournisseurs = [
            ['id' => 1, 'nom' => 'Pharmacie Centrale du Bénin'],
            ['id' => 2, 'nom' => 'SOBEMAP Matériel Médical'],
            ['id' => 3, 'nom' => 'SONEB (Eau)']
        ];

        $stats = [
            'total_reglements' => 4177000,
            'reglements_mois' => 4177000,
            'nombre_reglements' => count($reglements),
            'montant_moyen' => 4177000 / count($reglements)
        ];

        return Inertia::render('ReglementsFournisseurs/Index', [
            'reglements' => $reglements,
            'fournisseurs' => $fournisseurs,
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
    })->name('reglements-fournisseurs.index');

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

// Clients Routes (UI placeholders)
Route::prefix('clients')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Dashboard'); // Placeholder
    })->name('clients.index');
});

Route::prefix('factures-clients')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Dashboard'); // Placeholder
    })->name('factures-clients.index');

    Route::get('/create', function () {
        return Inertia::render('Dashboard'); // Placeholder
    })->name('factures-clients.create');
});

Route::prefix('reglements-clients')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Dashboard'); // Placeholder
    })->name('reglements-clients.index');
});

// Plan Comptable Routes
Route::prefix('plan-comptable')->group(function () {
    Route::get('/', function () {
        $comptes = [
            [
                'id' => 1,
                'numero' => '401001',
                'libelle' => 'Pharmacie Centrale du Bénin',
                'type' => 'tiers',
                'parent' => ['numero' => '401000'],
                'utilisations' => 12
            ],
            [
                'id' => 2,
                'numero' => '401002',
                'libelle' => 'SOBEMAP Matériel Médical',
                'type' => 'tiers',
                'parent' => ['numero' => '401000'],
                'utilisations' => 8
            ],
            [
                'id' => 3,
                'numero' => '601100',
                'libelle' => 'Achats de médicaments',
                'type' => 'charge',
                'parent' => ['numero' => '601000'],
                'utilisations' => 25
            ],
            [
                'id' => 4,
                'numero' => '601200',
                'libelle' => 'Achats de matériel médical',
                'type' => 'charge',
                'parent' => ['numero' => '601000'],
                'utilisations' => 15
            ],
            [
                'id' => 5,
                'numero' => '221000',
                'libelle' => 'Matériel et mobilier',
                'type' => 'immobilisation',
                'parent' => null,
                'utilisations' => 3
            ],
            [
                'id' => 6,
                'numero' => '521000',
                'libelle' => 'Banques - Compte courant',
                'type' => 'banque',
                'parent' => ['numero' => '521000'],
                'utilisations' => 45
            ],
            [
                'id' => 7,
                'numero' => '571000',
                'libelle' => 'Caisse - Espèces',
                'type' => 'banque',
                'parent' => null,
                'utilisations' => 18
            ],
            [
                'id' => 8,
                'numero' => '445100',
                'libelle' => 'TVA collectée',
                'type' => 'tva',
                'parent' => null,
                'utilisations' => 156
            ],
            [
                'id' => 9,
                'numero' => '442100',
                'libelle' => 'AIB à payer',
                'type' => 'aib',
                'parent' => null,
                'utilisations' => 89
            ]
        ];

        $stats = [
            'total' => count($comptes),
            'charges' => 2,
            'immobilisations' => 1,
            'tiers' => 2,
            'banques' => 2,
            'autres' => 2
        ];

        return Inertia::render('PlanComptable/Index', [
            'comptes' => $comptes,
            'stats' => $stats,
            'pagination' => [
                'current_page' => 1,
                'per_page' => 20,
                'total' => count($comptes)
            ],
            'user' => [
                'name' => 'Utilisateur Test',
                'email' => 'test@example.com'
            ]
        ]);
    })->name('plan-comptable.index');

    // Formulaire création
    Route::get('/create', function () {
        $comptesParents = [
            ['id' => 10, 'numero' => '401000', 'libelle' => 'Fournisseurs'],
            ['id' => 11, 'numero' => '411000', 'libelle' => 'Clients'],
            ['id' => 12, 'numero' => '601000', 'libelle' => 'Achats de marchandises'],
            ['id' => 13, 'numero' => '521000', 'libelle' => 'Banques'],
        ];

        return Inertia::render('PlanComptable/Form', [
            'comptesParents' => $comptesParents,
            'user' => [
                'name' => 'Utilisateur Test',
                'email' => 'test@example.com'
            ]
        ]);
    })->name('plan-comptable.create');

    // Formulaire édition
    Route::get('/{id}/edit', function ($id) {
        $compte = [
            'id' => $id,
            'numero' => '601100',
            'libelle' => 'Achats de médicaments',
            'type' => 'charge',
            'compte_parent_id' => 12,
            'description' => 'Compte utilisé pour l\'enregistrement des achats de médicaments'
        ];

        $comptesParents = [
            ['id' => 10, 'numero' => '401000', 'libelle' => 'Fournisseurs'],
            ['id' => 11, 'numero' => '411000', 'libelle' => 'Clients'],
            ['id' => 12, 'numero' => '601000', 'libelle' => 'Achats de marchandises'],
            ['id' => 13, 'numero' => '521000', 'libelle' => 'Banques'],
        ];

        return Inertia::render('PlanComptable/Form', [
            'compte' => $compte,
            'comptesParents' => $comptesParents,
            'user' => [
                'name' => 'Utilisateur Test',
                'email' => 'test@example.com'
            ]
        ]);
    })->name('plan-comptable.edit');
});

// Banques Routes
Route::prefix('banques')->group(function () {
    Route::get('/', function () {
        $banques = [
            [
                'id' => 1,
                'nom' => 'ORABANK - Compte Courant',
                'numero' => 'BJ123456789',
                'type' => 'banque',
                'compte_comptable' => '521001',
                'devise' => 'XOF',
                'solde' => 45250000,
                'mouvements' => [
                    'entrees' => 125,
                    'sorties' => 98
                ],
                'remarques' => 'Compte principal pour les opérations courantes'
            ],
            [
                'id' => 2,
                'nom' => 'BOA BENIN - Compte Dépenses',
                'numero' => 'BJ987654321',
                'type' => 'banque',
                'compte_comptable' => '521002',
                'devise' => 'XOF',
                'solde' => 12800000,
                'mouvements' => [
                    'entrees' => 45,
                    'sorties' => 156
                ],
                'remarques' => null
            ],
            [
                'id' => 3,
                'nom' => 'Caisse - Espèces',
                'numero' => 'CAISSE-001',
                'type' => 'especes',
                'compte_comptable' => '571000',
                'devise' => 'XOF',
                'solde' => 2450000,
                'mouvements' => [
                    'entrees' => 89,
                    'sorties' => 112
                ],
                'remarques' => 'Caisse principale du service comptabilité'
            ],
            [
                'id' => 4,
                'nom' => 'ECOBANK - Compte Épargne',
                'numero' => 'BJ555777888',
                'type' => 'banque',
                'compte_comptable' => '521003',
                'devise' => 'XOF',
                'solde' => 28000000,
                'mouvements' => [
                    'entrees' => 12,
                    'sorties' => 3
                ],
                'remarques' => null
            ]
        ];

        $stats = [
            'solde_total' => 88500000,
            'entrees_mois' => 45000000,
            'sorties_mois' => 38200000
        ];

        return Inertia::render('Banques/Index', [
            'banques' => $banques,
            'stats' => $stats,
            'user' => [
                'name' => 'Utilisateur Test',
                'email' => 'test@example.com'
            ]
        ]);
    })->name('banques.index');

    Route::get('/approvisionner', function () {
        $banques = [
            ['id' => 1, 'nom' => 'ORABANK - Compte Courant', 'numero' => 'BJ123456789', 'type' => 'banque', 'compte_comptable' => '521001', 'solde' => 45250000],
            ['id' => 2, 'nom' => 'BOA BENIN - Compte Dépenses', 'numero' => 'BJ987654321', 'type' => 'banque', 'compte_comptable' => '521002', 'solde' => 12800000],
            ['id' => 3, 'nom' => 'Caisse - Espèces', 'numero' => 'CAISSE-001', 'type' => 'especes', 'compte_comptable' => '571000', 'solde' => 2450000],
        ];

        return Inertia::render('Banques/Approvisionner', [
            'banques' => $banques,
            'banque_preselection_id' => request()->query('banque_id') ? (int)request()->query('banque_id') : null,
            'user' => [
                'name' => 'Utilisateur Test',
                'email' => 'test@example.com'
            ]
        ]);
    })->name('banques.approvisionner');

    Route::get('/{id}/mouvements', function ($id) {
        $banque = [
            'id' => $id,
            'nom' => 'ORABANK - Compte Courant',
            'numero' => 'BJ123456789',
            'type' => 'banque',
            'compte_comptable' => '521001',
            'solde' => 45250000
        ];

        $mouvements = [
            [
                'id' => 1,
                'date' => '2025-01-20',
                'type' => 'entree',
                'reference' => 'DEPOT-2025-001',
                'description' => 'Dépôt de recette journalière',
                'origine' => 'recette',
                'montant' => 5000000,
                'solde_apres' => 45250000,
                'user' => ['name' => 'Admin User']
            ],
            [
                'id' => 2,
                'date' => '2025-01-18',
                'type' => 'sortie',
                'reference' => 'VIR-2025-001',
                'description' => 'Règlement facture Pharmacie Centrale',
                'origine' => 'reglement_fournisseur',
                'montant' => 2000000,
                'solde_apres' => 40250000,
                'user' => ['name' => 'Admin User']
            ],
            [
                'id' => 3,
                'date' => '2025-01-15',
                'type' => 'entree',
                'reference' => 'SUB-2025-001',
                'description' => 'Subvention ministère de la santé',
                'origine' => 'subvention',
                'montant' => 15000000,
                'solde_apres' => 42250000,
                'user' => ['name' => 'Admin User']
            ],
            [
                'id' => 4,
                'date' => '2025-01-10',
                'type' => 'sortie',
                'reference' => 'CHQ-2025-005',
                'description' => 'Règlement SOBEMAP Matériel Médical',
                'origine' => 'reglement_fournisseur',
                'montant' => 1500000,
                'solde_apres' => 27250000,
                'user' => ['name' => 'Admin User']
            ]
        ];

        $stats = [
            'total_entrees' => 20000000,
            'total_sorties' => 3500000,
            'nombre_mouvements' => count($mouvements)
        ];

        return Inertia::render('Banques/Mouvements', [
            'banque' => $banque,
            'mouvements' => $mouvements,
            'stats' => $stats,
            'pagination' => [
                'current_page' => 1,
                'per_page' => 20,
                'total' => count($mouvements)
            ],
            'user' => [
                'name' => 'Utilisateur Test',
                'email' => 'test@example.com'
            ]
        ]);
    })->name('banques.mouvements');
});

// Rapports Routes
Route::prefix('rapports')->group(function () {
    Route::get('/fournisseurs', function () {
        return Inertia::render('Dashboard'); // Placeholder
    })->name('rapports.fournisseurs');

    Route::get('/clients', function () {
        return Inertia::render('Dashboard'); // Placeholder
    })->name('rapports.clients');

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
