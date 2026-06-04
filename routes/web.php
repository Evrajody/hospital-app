<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FournisseurController;
use App\Http\Controllers\FactureFournisseurController;
use App\Http\Controllers\ReglementFournisseurController;
use App\Http\Controllers\BanqueController;
use App\Http\Controllers\FactureClientController;
use App\Http\Controllers\PlanComptableController;
use App\Http\Controllers\ReglementClientController;
use App\Http\Controllers\TauxFiscalController;
use App\Http\Controllers\RapportClientController;
use App\Http\Controllers\RapportFournisseurController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AvanceClientController;

// Page d'accueil → redirect to login or dashboard
Route::get('/', function () {
    return auth()->check() ? redirect('/dashboard') : redirect('/login');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginPage'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

// ==========================================
// TOUTES LES ROUTES PROTÉGÉES PAR AUTH
// ==========================================
Route::middleware('auth')->group(function () {

// ==========================================
// FOURNISSEURS ROUTES (CRUD FONCTIONNEL)
// ==========================================
Route::prefix('fournisseurs')->middleware('permission:fournisseurs.voir')->group(function () {
    Route::get('/', [FournisseurController::class, 'index'])->name('fournisseurs.index');
    Route::get('/{id}', [FournisseurController::class, 'show'])
        ->where('id', '[0-9]+')
        ->name('fournisseurs.show');
});

// API Fournisseurs (JSON)
Route::prefix('api/fournisseurs')->group(function () {
    Route::post('/', [FournisseurController::class, 'store'])->middleware('permission:fournisseurs.creer')->name('api.fournisseurs.store');
    Route::put('/{id}', [FournisseurController::class, 'update'])->middleware('permission:fournisseurs.modifier')
        ->where('id', '[0-9]+')
        ->name('api.fournisseurs.update');
    Route::delete('/{id}', [FournisseurController::class, 'destroy'])->middleware('permission:fournisseurs.supprimer')
        ->where('id', '[0-9]+')
        ->name('api.fournisseurs.destroy');
    Route::get('/stats', [FournisseurController::class, 'stats'])->middleware('permission:fournisseurs.voir')->name('api.fournisseurs.stats');
});

// API Factures Fournisseurs (JSON)
Route::prefix('api/factures-fournisseurs')->group(function () {
    Route::get('/', [FactureFournisseurController::class, 'index'])->middleware('permission:factures-fournisseurs.voir')->name('api.factures-fournisseurs.index');
    Route::get('/generer-numero', [FactureFournisseurController::class, 'genererNumero'])->middleware('permission:factures-fournisseurs.creer')->name('api.factures-fournisseurs.generer-numero');
    Route::post('/verifier-numero', [FactureFournisseurController::class, 'verifierNumeroPiece'])->middleware('permission:factures-fournisseurs.creer')->name('api.factures-fournisseurs.verifier-numero');
    Route::get('/stats', [FactureFournisseurController::class, 'stats'])->middleware('permission:factures-fournisseurs.voir')->name('api.factures-fournisseurs.stats');
    Route::post('/', [FactureFournisseurController::class, 'store'])->middleware('permission:factures-fournisseurs.creer')->name('api.factures-fournisseurs.store');
    Route::get('/{id}', [FactureFournisseurController::class, 'show'])->middleware('permission:factures-fournisseurs.voir')
        ->where('id', '[0-9]+')->name('api.factures-fournisseurs.show');
    Route::put('/{id}', [FactureFournisseurController::class, 'update'])->middleware('permission:factures-fournisseurs.modifier')
        ->where('id', '[0-9]+')->name('api.factures-fournisseurs.update');
    Route::delete('/{id}', [FactureFournisseurController::class, 'destroy'])->middleware('permission:factures-fournisseurs.supprimer')
        ->where('id', '[0-9]+')->name('api.factures-fournisseurs.destroy');
    Route::post('/{id}/valider', [FactureFournisseurController::class, 'valider'])->middleware('permission:factures-fournisseurs.valider')
        ->where('id', '[0-9]+')->name('api.factures-fournisseurs.valider');
    Route::post('/{id}/annuler', [FactureFournisseurController::class, 'annuler'])->middleware('permission:factures-fournisseurs.modifier')
        ->where('id', '[0-9]+')->name('api.factures-fournisseurs.annuler');
    Route::post('/{id}/solder', [FactureFournisseurController::class, 'solder'])->middleware('permission:factures-fournisseurs.modifier')
        ->where('id', '[0-9]+')->name('api.factures-fournisseurs.solder');
    Route::post('/{id}/imputation', [FactureFournisseurController::class, 'creerImputation'])->middleware('permission:factures-fournisseurs.modifier')
        ->where('id', '[0-9]+')->name('api.factures-fournisseurs.imputation');
    Route::get('/{id}/imputation-data', [FactureFournisseurController::class, 'imputationData'])->middleware('permission:factures-fournisseurs.voir')
        ->where('id', '[0-9]+')->name('api.factures-fournisseurs.imputation-data');
    Route::get('/{id}/etat-reglement-data', [FactureFournisseurController::class, 'etatReglementData'])->middleware('permission:factures-fournisseurs.voir')
        ->where('id', '[0-9]+')->name('api.factures-fournisseurs.etat-reglement-data');
});

// API Règlements Fournisseurs (JSON)
Route::prefix('api/reglements-fournisseurs')->group(function () {
    Route::get('/', [ReglementFournisseurController::class, 'index'])->middleware('permission:reglements-fournisseurs.voir')->name('api.reglements-fournisseurs.index');
    Route::get('/generer-numero', [ReglementFournisseurController::class, 'genererNumero'])->middleware('permission:reglements-fournisseurs.creer')->name('api.reglements-fournisseurs.generer-numero');
    Route::get('/stats', [ReglementFournisseurController::class, 'stats'])->middleware('permission:reglements-fournisseurs.voir')->name('api.reglements-fournisseurs.stats');
    Route::post('/', [ReglementFournisseurController::class, 'store'])->middleware('permission:reglements-fournisseurs.creer')->name('api.reglements-fournisseurs.store');
    Route::get('/{id}', [ReglementFournisseurController::class, 'show'])->middleware('permission:reglements-fournisseurs.voir')
        ->where('id', '[0-9]+')->name('api.reglements-fournisseurs.show');
    Route::put('/{id}', [ReglementFournisseurController::class, 'update'])->middleware('permission:reglements-fournisseurs.modifier')
        ->where('id', '[0-9]+')->name('api.reglements-fournisseurs.update');
    Route::delete('/{id}', [ReglementFournisseurController::class, 'destroy'])->middleware('permission:reglements-fournisseurs.supprimer')
        ->where('id', '[0-9]+')->name('api.reglements-fournisseurs.destroy');
    Route::get('/facture/{factureId}', [ReglementFournisseurController::class, 'parFacture'])->middleware('permission:reglements-fournisseurs.voir')
        ->where('factureId', '[0-9]+')->name('api.reglements-fournisseurs.par-facture');
    Route::get('/{id}/mandat-data', [ReglementFournisseurController::class, 'mandatData'])->middleware('permission:reglements-fournisseurs.voir')
        ->where('id', '[0-9]+')->name('api.reglements-fournisseurs.mandat-data');
    Route::get('/{id}/imputation-data', [ReglementFournisseurController::class, 'imputationData'])->middleware('permission:reglements-fournisseurs.voir')
        ->where('id', '[0-9]+')->name('api.reglements-fournisseurs.imputation-data');
    Route::post('/{id}/creer-ecritures', [ReglementFournisseurController::class, 'creerEcritures'])->middleware('permission:reglements-fournisseurs.modifier')
        ->where('id', '[0-9]+')->name('api.reglements-fournisseurs.creer-ecritures');
});

// Factures Fournisseurs Routes
Route::prefix('factures-fournisseurs')->middleware('permission:factures-fournisseurs.voir')->group(function () {
    Route::get('/', [FactureFournisseurController::class, 'indexView'])->name('factures-fournisseurs.index');
    Route::get('/{id}', [FactureFournisseurController::class, 'showView'])->where('id', '[0-9]+')->name('factures-fournisseurs.show');
    Route::get('/{id}/regler', [FactureFournisseurController::class, 'reglementView'])->where('id', '[0-9]+')->name('factures-fournisseurs.regler');
    Route::get('/{id}/imputation-pdf', [FactureFournisseurController::class, 'imputationPdf'])->where('id', '[0-9]+')->name('factures-fournisseurs.imputation-pdf');
    Route::get('/{id}/etat-reglement-pdf', [FactureFournisseurController::class, 'etatReglementPdf'])->where('id', '[0-9]+')->name('factures-fournisseurs.etat-reglement-pdf');
});

// Règlements Fournisseurs Routes
Route::prefix('reglements-fournisseurs')->middleware('permission:reglements-fournisseurs.voir')->group(function () {
    Route::get('/', [ReglementFournisseurController::class, 'indexView'])->name('reglements-fournisseurs.index');
    Route::get('/{id}/mandat', [ReglementFournisseurController::class, 'mandat'])->name('reglements-fournisseurs.mandat');
    Route::get('/{id}/imputation-pdf', [ReglementFournisseurController::class, 'imputationPdf'])->where('id', '[0-9]+')->name('reglements-fournisseurs.imputation-pdf');
});

// Clients Routes
Route::prefix('clients')->middleware('permission:clients.voir')->group(function () {
    Route::get('/', [App\Http\Controllers\ClientController::class, 'index'])->name('clients.index');
    Route::get('/{id}', [App\Http\Controllers\ClientController::class, 'show'])
        ->where('id', '[0-9]+')->name('clients.show');
});

// API Clients
Route::prefix('api/clients')->group(function () {
    Route::post('/', [App\Http\Controllers\ClientController::class, 'store'])->middleware('permission:clients.creer')->name('api.clients.store');
    Route::put('/{id}', [App\Http\Controllers\ClientController::class, 'update'])->middleware('permission:clients.modifier')->name('api.clients.update');
    Route::delete('/{id}', [App\Http\Controllers\ClientController::class, 'destroy'])->middleware('permission:clients.supprimer')->name('api.clients.destroy');
});

// Factures Clients Routes
Route::prefix('factures-clients')->middleware('permission:factures-clients.voir')->group(function () {
    Route::get('/', [FactureClientController::class, 'indexView'])->name('factures-clients.index');
    Route::get('/{id}', [FactureClientController::class, 'showView'])
        ->where('id', '[0-9]+')->name('factures-clients.show');
    Route::get('/{id}/regler', [FactureClientController::class, 'reglementView'])
        ->where('id', '[0-9]+')->name('factures-clients.regler');
    Route::get('/{id}/etat-reglement-pdf', [FactureClientController::class, 'etatReglementPdf'])
        ->where('id', '[0-9]+')->name('factures-clients.etat-reglement-pdf');
});

// API Factures Clients
Route::prefix('api/factures-clients')->group(function () {
    Route::post('/', [FactureClientController::class, 'store'])->middleware('permission:factures-clients.creer')->name('api.factures-clients.store');
    Route::put('/{id}', [FactureClientController::class, 'update'])->middleware('permission:factures-clients.modifier')->name('api.factures-clients.update');
    Route::post('/{id}/solder', [FactureClientController::class, 'solder'])->middleware('permission:factures-clients.modifier')->name('api.factures-clients.solder');
    Route::get('/{id}/etat-reglement-data', [FactureClientController::class, 'etatReglementData'])->middleware('permission:factures-clients.voir')
        ->where('id', '[0-9]+')->name('api.factures-clients.etat-reglement-data');
    Route::delete('/{id}', [FactureClientController::class, 'destroy'])->middleware('permission:factures-clients.supprimer')->name('api.factures-clients.destroy');
});

// Règlements Clients Routes
Route::prefix('reglements-clients')->middleware('permission:reglements-clients.voir')->group(function () {
    Route::get('/', [ReglementClientController::class, 'indexView'])->name('reglements-clients.index');
});

// API Règlements Clients
Route::prefix('api/reglements-clients')->group(function () {
    Route::post('/', [ReglementClientController::class, 'store'])->middleware('permission:reglements-clients.creer')->name('api.reglements-clients.store');
    Route::put('/{id}', [ReglementClientController::class, 'update'])->middleware('permission:reglements-clients.modifier')->name('api.reglements-clients.update');
    Route::delete('/{id}', [ReglementClientController::class, 'destroy'])->middleware('permission:reglements-clients.supprimer')->name('api.reglements-clients.destroy');
});

// Avances Clients Routes
Route::prefix('avances-clients')->middleware('permission:reglements-clients.voir')->group(function () {
    Route::get('/', [AvanceClientController::class, 'indexView'])->name('avances-clients.index');
});

// API Avances Clients
Route::prefix('api/avances-clients')->group(function () {
    Route::post('/', [AvanceClientController::class, 'store'])->middleware('permission:reglements-clients.creer')->name('api.avances-clients.store');
    Route::put('/{id}', [AvanceClientController::class, 'update'])->middleware('permission:reglements-clients.modifier')->name('api.avances-clients.update');
    Route::delete('/{id}', [AvanceClientController::class, 'destroy'])->middleware('permission:reglements-clients.supprimer')->name('api.avances-clients.destroy');
    Route::get('/client/{clientId}', [AvanceClientController::class, 'disponiblesParClient'])
        ->where('clientId', '[0-9]+')
        ->middleware('permission:reglements-clients.voir')
        ->name('api.avances-clients.disponibles-par-client');
});

// Plan Comptable Routes
Route::prefix('plan-comptable')->middleware('permission:plan-comptable.voir')->group(function () {
    Route::get('/', [PlanComptableController::class, 'index'])->name('plan-comptable.index');
    Route::get('/export/pdf', [PlanComptableController::class, 'exportPdf'])->name('plan-comptable.export.pdf');
    Route::get('/export/excel', [PlanComptableController::class, 'exportExcel'])->name('plan-comptable.export.excel');
});

// API Plan Comptable
Route::prefix('api/plan-comptable')->group(function () {
    Route::get('/search', [PlanComptableController::class, 'search'])->middleware('permission:plan-comptable.voir')->name('api.plan-comptable.search');
    Route::post('/', [PlanComptableController::class, 'store'])->middleware('permission:plan-comptable.modifier')->name('api.plan-comptable.store');
    Route::get('/{compte}', [PlanComptableController::class, 'show'])->middleware('permission:plan-comptable.voir')->name('api.plan-comptable.show');
    Route::put('/{compte}', [PlanComptableController::class, 'update'])->middleware('permission:plan-comptable.modifier')->name('api.plan-comptable.update');
    Route::delete('/{compte}', [PlanComptableController::class, 'destroy'])->middleware('permission:plan-comptable.modifier')->name('api.plan-comptable.destroy');
});

// Banques Routes
Route::prefix('banques')->middleware('permission:banques.voir')->group(function () {
    Route::get('/', [BanqueController::class, 'index'])->name('banques.index');
    Route::get('/{id}/mouvements', [BanqueController::class, 'mouvements'])->name('banques.mouvements');
});

// API Banques
Route::prefix('api')->group(function () {
    Route::post('/banques', [BanqueController::class, 'storeBanque'])->middleware('permission:banques.creer')->name('api.banques.store');
    Route::get('/banques/liste', [BanqueController::class, 'listeBanques'])->middleware('permission:banques.voir')->name('api.banques.liste');
    Route::delete('/banques/{banque}', [BanqueController::class, 'destroyBanque'])->middleware('permission:banques.supprimer')->name('api.banques.destroy');

    Route::post('/comptes-bancaires', [BanqueController::class, 'storeCompte'])->middleware('permission:banques.creer')->name('api.comptes-bancaires.store');
    Route::put('/comptes-bancaires/{compte}', [BanqueController::class, 'updateCompte'])->middleware('permission:banques.modifier')->name('api.comptes-bancaires.update');
    Route::delete('/comptes-bancaires/{compte}', [BanqueController::class, 'destroyCompte'])->middleware('permission:banques.supprimer')->name('api.comptes-bancaires.destroy');
    Route::get('/comptes-bancaires/liste', [BanqueController::class, 'listeComptes'])->middleware('permission:banques.voir')->name('api.comptes-bancaires.liste');

    Route::post('/banques/approvisionnement', [BanqueController::class, 'storeApprovisionnement'])->middleware('permission:banques.modifier')->name('api.banques.approvisionnement');
    Route::put('/banques/approvisionnement/{id}', [BanqueController::class, 'updateApprovisionnement'])->middleware('permission:banques.modifier')->where('id', '[0-9]+')->name('api.banques.approvisionnement.update');
    Route::delete('/banques/approvisionnement/{id}', [BanqueController::class, 'destroyApprovisionnement'])->middleware('permission:banques.supprimer')->where('id', '[0-9]+')->name('api.banques.approvisionnement.destroy');
});

// Rapports Routes
Route::prefix('rapports')->middleware('permission:rapports.voir')->group(function () {
    // Rapports Fournisseurs
    Route::prefix('fournisseurs')->group(function () {
        Route::get('/', [RapportFournisseurController::class, 'index'])->name('rapports.fournisseurs');

        // Mouvement périodique
        Route::get('/mouvement-periodique', [RapportFournisseurController::class, 'mouvementFacturesPage'])->name('rapports.fournisseurs.mouvement-periodique');
        Route::get('/api/mouvement-factures', [RapportFournisseurController::class, 'mouvementFactures']);
        Route::get('/pdf/mouvement-factures', [RapportFournisseurController::class, 'mouvementFacturesPdf']);
        Route::get('/excel/mouvement-factures', [RapportFournisseurController::class, 'mouvementFacturesExcel']);

        // Situation des fournisseurs
        Route::get('/api/situation-fournisseurs', [RapportFournisseurController::class, 'situationFournisseurs']);
        Route::get('/pdf/situation-fournisseurs', [RapportFournisseurController::class, 'situationFournisseursPdf']);
        Route::get('/excel/situation-fournisseurs', [RapportFournisseurController::class, 'situationFournisseursExcel']);

        // Factures réglées
        Route::get('/api/factures-reglees', [RapportFournisseurController::class, 'facturesReglees']);
        Route::get('/pdf/factures-reglees', [RapportFournisseurController::class, 'facturesRegleesPdf']);
        Route::get('/excel/factures-reglees', [RapportFournisseurController::class, 'facturesRegleesExcel']);

        // Déclaration AIB
        Route::get('/api/declaration-aib', [RapportFournisseurController::class, 'declarationAib']);
        Route::get('/pdf/declaration-aib', [RapportFournisseurController::class, 'declarationAibPdf']);

        // Point périodique des PC
        Route::get('/api/point-periodique', [RapportFournisseurController::class, 'pointPeriodique']);
        Route::get('/pdf/point-periodique', [RapportFournisseurController::class, 'pointPeriodiquePdf']);
        Route::get('/excel/point-periodique', [RapportFournisseurController::class, 'pointPeriodiqueExcel']);

        // Situation périodique des banques
        Route::get('/api/situation-banques', [RapportFournisseurController::class, 'situationBanques']);
        Route::get('/pdf/situation-banques', [RapportFournisseurController::class, 'situationBanquesPdf']);
        Route::get('/excel/situation-banques', [RapportFournisseurController::class, 'situationBanquesExcel']);

        // Bordereau de transmission
        Route::get('/api/bordereau-transmission', [RapportFournisseurController::class, 'bordereauTransmission']);
        Route::get('/pdf/bordereau-transmission', [RapportFournisseurController::class, 'bordereauTransmissionPdf']);
        Route::get('/excel/bordereau-transmission', [RapportFournisseurController::class, 'bordereauTransmissionExcel']);
        Route::get('/pdf/mandats', [RapportFournisseurController::class, 'mandatsMultiplesPdf']);

        // Récapitulatif des charges
        Route::get('/api/recap-charges', [RapportFournisseurController::class, 'recapCharges']);
        Route::get('/pdf/recap-charges', [RapportFournisseurController::class, 'recapChargesPdf']);
        Route::get('/excel/recap-charges', [RapportFournisseurController::class, 'recapChargesExcel']);

        // Récapitulatif des investissements
        Route::get('/api/recap-investissements', [RapportFournisseurController::class, 'recapInvestissements']);
        Route::get('/pdf/recap-investissements', [RapportFournisseurController::class, 'recapInvestissementsPdf']);
        Route::get('/excel/recap-investissements', [RapportFournisseurController::class, 'recapInvestissementsExcel']);

        // Factures et soldes
        Route::get('/api/factures-soldes', [RapportFournisseurController::class, 'facturesSoldes']);
        Route::get('/excel/factures-soldes', [RapportFournisseurController::class, 'facturesSoldesExcel']);

        // Déclaration TVA
        Route::get('/api/declaration-tva', [RapportFournisseurController::class, 'declarationTva']);
        Route::get('/pdf/declaration-tva', [RapportFournisseurController::class, 'declarationTvaPdf']);
        Route::get('/excel/declaration-tva', [RapportFournisseurController::class, 'declarationTvaExcel']);

        // État banques par compte
        Route::get('/api/banques-par-compte', [RapportFournisseurController::class, 'etatBanquesParCompte']);
        Route::get('/pdf/banques-par-compte', [RapportFournisseurController::class, 'etatBanquesParComptePdf']);
        Route::get('/excel/banques-par-compte', [RapportFournisseurController::class, 'banquesParCompteExcel']);

        // Export Excel Déclaration AIB
        Route::get('/excel/declaration-aib', [RapportFournisseurController::class, 'declarationAibExcel']);
    });

    // Rapports Banques
    Route::prefix('banques')->group(function () {
        Route::get('/', [RapportFournisseurController::class, 'indexBanques'])->name('rapports.banques');
        Route::get('/api/situation-banques', [RapportFournisseurController::class, 'situationBanques']);
        Route::get('/pdf/situation-banques', [RapportFournisseurController::class, 'situationBanquesPdf']);
    });

    // Rapports Clients
    Route::prefix('clients')->group(function () {
        // Page index (dashboard avec onglets)
        Route::get('/', [RapportClientController::class, 'index'])->name('rapports.clients');

        // API JSON pour les onglets
        Route::get('/api/etat-reglements', [RapportClientController::class, 'etatReglements']);
        Route::get('/api/etat-creances', [RapportClientController::class, 'etatCreances']);
        Route::get('/api/brouillard-cheques', [RapportClientController::class, 'brouillardCheques']);
        Route::get('/api/imputations-comptables', [RapportClientController::class, 'imputationsComptables']);
        Route::get('/api/chiffre-affaires', [RapportClientController::class, 'chiffreAffaires']);
        Route::get('/api/pertes-rejets', [RapportClientController::class, 'pertesRejets']);

        // Export PDF
        Route::get('/pdf/etat-reglements', [RapportClientController::class, 'etatReglementsPdf']);
        Route::get('/pdf/etat-creances', [RapportClientController::class, 'etatCreancesPdf']);
        Route::get('/pdf/brouillard-cheques', [RapportClientController::class, 'brouillardChequesPdf']);
        Route::get('/pdf/imputations-comptables', [RapportClientController::class, 'imputationsComptablesPdf']);
        Route::get('/pdf/chiffre-affaires', [RapportClientController::class, 'chiffreAffairesPdf']);
        Route::get('/pdf/pertes-rejets', [RapportClientController::class, 'pertesRejetsPdf']);

        // Export Excel
        Route::get('/excel/etat-reglements', [RapportClientController::class, 'etatReglementsExcel']);
        Route::get('/excel/etat-creances', [RapportClientController::class, 'etatCreancesExcel']);
        Route::get('/excel/brouillard-cheques', [RapportClientController::class, 'brouillardChequesExcel']);
        Route::get('/excel/imputations-comptables', [RapportClientController::class, 'imputationsComptablesExcel']);
        Route::get('/excel/chiffre-affaires', [RapportClientController::class, 'chiffreAffairesExcel']);
        Route::get('/excel/pertes-rejets', [RapportClientController::class, 'pertesRejetsExcel']);

        // Pages standalone (backward compat)
        Route::get('/etat-reglements', [RapportClientController::class, 'etatReglementsPage'])->name('rapports.clients.etat-reglements');
        Route::get('/etat-creances', [RapportClientController::class, 'etatCreancesPage'])->name('rapports.clients.etat-creances');
        Route::get('/brouillard-cheques', [RapportClientController::class, 'brouillardChequesPage'])->name('rapports.clients.brouillard-cheques');
        Route::get('/chiffre-affaires', [RapportClientController::class, 'chiffreAffairesPage'])->name('rapports.clients.chiffre-affaires');
        Route::get('/pertes-rejets', [RapportClientController::class, 'pertesRejetsPage'])->name('rapports.clients.pertes-rejets');
    });

});

// Paramètres Routes
Route::get('/taux-fiscaux', [TauxFiscalController::class, 'index'])->middleware('permission:parametres.voir')->name('taux-fiscaux.index');

// API Taux Fiscaux
Route::prefix('api/taux-fiscaux')->middleware('permission:parametres.modifier')->group(function () {
    Route::post('/', [TauxFiscalController::class, 'store'])->name('api.taux-fiscaux.store');
    Route::put('/{id}', [TauxFiscalController::class, 'update'])->name('api.taux-fiscaux.update');
    Route::delete('/{id}', [TauxFiscalController::class, 'destroy'])->name('api.taux-fiscaux.destroy');
    Route::patch('/{id}/toggle', [TauxFiscalController::class, 'toggleActif'])->name('api.taux-fiscaux.toggle');
});

// Administration - Utilisateurs
Route::get('/utilisateurs', [UserController::class, 'index'])->middleware('permission:utilisateurs.voir')->name('utilisateurs.index');
Route::prefix('api/utilisateurs')->group(function () {
    Route::post('/', [UserController::class, 'store'])->middleware('permission:utilisateurs.creer')->name('api.utilisateurs.store');
    Route::put('/{id}', [UserController::class, 'update'])->middleware('permission:utilisateurs.modifier')->name('api.utilisateurs.update');
    Route::delete('/{id}', [UserController::class, 'destroy'])->middleware('permission:utilisateurs.supprimer')->name('api.utilisateurs.destroy');
    Route::patch('/{id}/toggle-active', [UserController::class, 'toggleActive'])->middleware('permission:utilisateurs.modifier')->name('api.utilisateurs.toggle-active');
});

// Administration - Rôles & Permissions
Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:roles.voir')->name('roles.index');
Route::prefix('api/roles')->group(function () {
    Route::post('/', [RoleController::class, 'storeRole'])->middleware('permission:roles.creer')->name('api.roles.store');
    Route::put('/{id}', [RoleController::class, 'updateRole'])->middleware('permission:roles.modifier')->name('api.roles.update');
    Route::delete('/{id}', [RoleController::class, 'destroyRole'])->middleware('permission:roles.supprimer')->name('api.roles.destroy');
    Route::patch('/{id}/permission', [RoleController::class, 'togglePermission'])->middleware('permission:roles.modifier')->name('api.roles.toggle-permission');
    Route::patch('/{id}/permissions/bulk', [RoleController::class, 'bulkPermissions'])->middleware('permission:roles.modifier')->name('api.roles.bulk-permissions');
});
Route::prefix('api/permissions')->middleware('permission:roles.modifier')->group(function () {
    Route::post('/', [RoleController::class, 'storePermission'])->name('api.permissions.store');
    Route::delete('/{id}', [RoleController::class, 'destroyPermission'])->name('api.permissions.destroy');
});

// Journal d'activité
Route::get('/journal-activite', [ActivityLogController::class, 'index'])->middleware('permission:journal.voir')->name('journal-activite.index');

// User Profile Routes
Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
Route::post('/profile/signature', [ProfileController::class, 'uploadSignature'])->name('profile.signature');
Route::delete('/profile/signature', [ProfileController::class, 'deleteSignature'])->name('profile.signature.delete');
// Paramètres Établissement
Route::get('/parametres/etablissement', [ProfileController::class, 'etablissement'])->middleware('permission:parametres.voir')->name('parametres.etablissement');
Route::put('/parametres/etablissement', [ProfileController::class, 'updateEtablissement'])->middleware('permission:parametres.modifier')->name('parametres.etablissement.update');

}); // Fin du middleware auth
