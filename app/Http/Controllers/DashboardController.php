<?php

namespace App\Http\Controllers;

use App\Models\FactureFournisseur;
use App\Models\FactureClient;
use App\Models\ReglementFournisseur;
use App\Models\CompteBancaire;
use App\Models\Fournisseur;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DashboardController extends Controller
{
    public function index(): InertiaResponse
    {
        $now = now();
        $debutMois = $now->copy()->startOfMonth();
        $finMois = $now->copy()->endOfMonth();

        // --- KPI ---

        // Chiffre d'affaires du mois (factures clients créées ce mois)
        $chiffreAffaires = FactureClient::whereBetween('date_facture', [$debutMois, $finMois])
            ->sum('montant');

        // Chiffre d'affaires du mois précédent (pour le trend)
        $debutMoisPrec = $now->copy()->subMonth()->startOfMonth();
        $finMoisPrec = $now->copy()->subMonth()->endOfMonth();
        $chiffreAffairesMoisPrec = FactureClient::whereBetween('date_facture', [$debutMoisPrec, $finMoisPrec])
            ->sum('montant');

        $trendCA = $chiffreAffairesMoisPrec > 0
            ? round((($chiffreAffaires - $chiffreAffairesMoisPrec) / $chiffreAffairesMoisPrec) * 100, 1)
            : null;

        // Factures fournisseurs en attente de règlement
        $facturesFournisseursEnAttente = FactureFournisseur::whereIn('statut', [
            FactureFournisseur::STATUT_VALIDEE,
            FactureFournisseur::STATUT_PARTIELLEMENT_PAYEE,
        ])->count();

        // Factures clients en attente de règlement
        $facturesClientsEnAttente = FactureClient::whereIn('statut', [
            FactureClient::STATUT_NON_PAYEE,
            FactureClient::STATUT_PARTIELLEMENT_PAYEE,
        ])->count();

        $facturesEnAttente = $facturesFournisseursEnAttente + $facturesClientsEnAttente;

        // Dettes fournisseurs (reste à payer sur factures non soldées)
        $dettesFournisseurs = FactureFournisseur::whereIn('statut', [
            FactureFournisseur::STATUT_VALIDEE,
            FactureFournisseur::STATUT_PARTIELLEMENT_PAYEE,
        ])->sum('reste_a_payer');

        $dettesMoisPrec = FactureFournisseur::whereIn('statut', [
                FactureFournisseur::STATUT_VALIDEE,
                FactureFournisseur::STATUT_PARTIELLEMENT_PAYEE,
            ])
            ->where('created_at', '<=', $finMoisPrec)
            ->sum('reste_a_payer');

        $trendDettes = $dettesMoisPrec > 0
            ? round((($dettesFournisseurs - $dettesMoisPrec) / $dettesMoisPrec) * 100, 1)
            : null;

        // Créances clients (reste à payer sur factures clients non soldées)
        $creancesClients = FactureClient::whereIn('statut', [
            FactureClient::STATUT_NON_PAYEE,
            FactureClient::STATUT_PARTIELLEMENT_PAYEE,
        ])->sum('reste_a_payer');

        $creancesMoisPrec = FactureClient::whereIn('statut', [
                FactureClient::STATUT_NON_PAYEE,
                FactureClient::STATUT_PARTIELLEMENT_PAYEE,
            ])
            ->where('created_at', '<=', $finMoisPrec)
            ->sum('reste_a_payer');

        $trendCreances = $creancesMoisPrec > 0
            ? round((($creancesClients - $creancesMoisPrec) / $creancesMoisPrec) * 100, 1)
            : null;

        // --- Dernières factures fournisseurs ---
        $dernieresFactures = FactureFournisseur::with('fournisseur')
            ->where('statut', '!=', FactureFournisseur::STATUT_ANNULEE)
            ->orderByDesc('date_facture_bc')
            ->limit(10)
            ->get()
            ->map(fn($f) => [
                'numero' => $f->numero_piece,
                'fournisseur' => $f->fournisseur?->nom ?? '-',
                'montant' => (float) $f->net_a_payer,
                'date' => $f->date_facture_bc?->format('Y-m-d'),
                'statut' => $f->statut_libelle,
                'statut_type' => $f->statut_couleur,
            ]);

        // --- Situation des banques ---
        $banques = CompteBancaire::with('banque')
            ->orderBy('banque_id')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->banque->nom,
                'account' => $c->numero_compte,
                'balance' => (float) $c->solde,
            ]);

        $soldeTotalBanques = CompteBancaire::sum('solde');

        // --- Stats complémentaires ---
        $stats = [
            'total_fournisseurs' => Fournisseur::count(),
            'total_clients' => Client::count(),
            'reglements_mois' => ReglementFournisseur::where('statut', '!=', ReglementFournisseur::STATUT_ANNULE)
                ->whereBetween('date_reglement', [$debutMois, $finMois])
                ->sum('montant'),
        ];

        // --- Graphique : Évolution mensuelle (12 derniers mois) ---
        $evolutionMensuelle = [];
        for ($i = 11; $i >= 0; $i--) {
            $moisDebut = $now->copy()->subMonths($i)->startOfMonth();
            $moisFin = $now->copy()->subMonths($i)->endOfMonth();
            $label = $moisDebut->translatedFormat('M Y');

            $recettes = (float) FactureClient::whereBetween('date_facture', [$moisDebut, $moisFin])
                ->sum('montant');

            $depenses = (float) ReglementFournisseur::where('statut', '!=', ReglementFournisseur::STATUT_ANNULE)
                ->whereBetween('date_reglement', [$moisDebut, $moisFin])
                ->sum('montant');

            $evolutionMensuelle[] = [
                'mois' => $label,
                'recettes' => $recettes,
                'depenses' => $depenses,
            ];
        }

        // --- Graphique : Répartition des charges (top 10 fournisseurs) ---
        $repartitionCharges = ReglementFournisseur::where('statut', '!=', ReglementFournisseur::STATUT_ANNULE)
            ->whereYear('date_reglement', $now->year)
            ->select('fournisseur_id', DB::raw('SUM(montant) as total'))
            ->groupBy('fournisseur_id')
            ->orderByDesc('total')
            ->limit(10)
            ->with('fournisseur')
            ->get()
            ->map(fn($r) => [
                'name' => $r->fournisseur?->nom ?? 'Inconnu',
                'value' => (float) $r->total,
            ]);

        // Si moins de données, ajouter "Autres"
        $totalChargesAnnee = (float) ReglementFournisseur::where('statut', '!=', ReglementFournisseur::STATUT_ANNULE)
            ->whereYear('date_reglement', $now->year)
            ->sum('montant');
        $topTotal = $repartitionCharges->sum('value');
        if ($totalChargesAnnee > $topTotal && $topTotal > 0) {
            $repartitionCharges->push([
                'name' => 'Autres',
                'value' => $totalChargesAnnee - $topTotal,
            ]);
        }

        return Inertia::render('Dashboard', [
            'kpis' => [
                'chiffre_affaires' => (float) $chiffreAffaires,
                'trend_ca' => $trendCA,
                'factures_en_attente' => $facturesEnAttente,
                'factures_clients_en_attente' => $facturesClientsEnAttente,
                'factures_fournisseurs_en_attente' => $facturesFournisseursEnAttente,
                'dettes_fournisseurs' => (float) $dettesFournisseurs,
                'trend_dettes' => $trendDettes,
                'creances_clients' => (float) $creancesClients,
                'trend_creances' => $trendCreances,
            ],
            'dernieres_factures' => $dernieresFactures,
            'banques' => $banques,
            'solde_total_banques' => (float) $soldeTotalBanques,
            'stats' => $stats,
            'evolution_mensuelle' => $evolutionMensuelle,
            'repartition_charges' => $repartitionCharges,
            'user' => [
                'name' => auth()->user()?->name ?? 'Utilisateur',
                'email' => auth()->user()?->email ?? '',
            ],
        ]);
    }
}
