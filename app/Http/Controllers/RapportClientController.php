<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\FactureClient;
use App\Models\ReglementClient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RapportClientController extends Controller
{
    // ==========================================
    // HELPERS
    // ==========================================

    private function getClientsList(): array
    {
        return Client::with('compteComptable')
            ->orderBy('nom')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'code' => $c->compteComptable?->numero_compte ?? '-',
                'nom' => $c->nom,
            ])
            ->toArray();
    }

    private function formatMontant(float $montant): string
    {
        return number_format($montant, 0, ',', ' ');
    }

    // ==========================================
    // PAGE INDEX (Inertia)
    // ==========================================

    public function index()
    {
        return Inertia::render('Rapports/Clients/Index', [
            'clients' => $this->getClientsList(),
            'user' => [
                'name' => auth()->user()?->name ?? 'Utilisateur',
                'email' => auth()->user()?->email ?? 'user@hospital.bj',
            ]
        ]);
    }

    // ==========================================
    // DATA BUILDERS (shared between API & PDF)
    // ==========================================

    private function buildEtatReglementsData(Request $request): array
    {
        $mode = $request->input('mode', 'par_client');
        $clientId = $request->input('client_id');
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');

        $data = [];

        if ($mode === 'par_client' || $mode === 'un_client') {
            $query = FactureClient::with(['client.compteComptable', 'reglements']);

            if ($mode === 'un_client' && $clientId) {
                $query->where('client_id', $clientId);
            }

            if ($dateDebut && $dateFin) {
                $query->whereBetween('date_facture', [$dateDebut, $dateFin]);
            }

            $factures = $query->orderBy('client_id')->orderBy('date_facture')->get();

            $grouped = $factures->groupBy('client_id');
            foreach ($grouped as $cId => $clientFactures) {
                $client = $clientFactures->first()->client;
                $lignes = [];
                $totalFacture = 0;
                $totalPaye = 0;
                $totalRejet = 0;

                foreach ($clientFactures as $index => $facture) {
                    $montantFacture = (float) $facture->montant - (float) ($facture->ristourne ?? 0);
                    $montantPaye = (float) $facture->montant_paye;
                    $rejet = $montantFacture - $montantPaye;

                    $dernierReglement = $facture->reglements->sortByDesc('date_reglement')->first();

                    $lignes[] = [
                        'numero' => $index + 1,
                        'reference' => $facture->reference,
                        'date_facture' => $facture->date_facture?->format('d/m/Y'),
                        'date_reglement' => $dernierReglement?->date_reglement?->format('d/m/Y') ?? '-',
                        'montant_facture' => $montantFacture,
                        'montant_paye' => $montantPaye,
                        'rejet' => max(0, $rejet),
                    ];

                    $totalFacture += $montantFacture;
                    $totalPaye += $montantPaye;
                    $totalRejet += max(0, $rejet);
                }

                $data[] = [
                    'client_id' => $cId,
                    'numero_compte' => $client->compteComptable?->numero_compte ?? '-',
                    'raison_sociale' => $client->nom,
                    'lignes' => $lignes,
                    'total_facture' => $totalFacture,
                    'total_paye' => $totalPaye,
                    'total_rejet' => $totalRejet,
                ];
            }
        } elseif ($mode === 'tous_clients') {
            $deb = $dateDebut ?: now()->startOfYear()->format('Y-m-d');
            $fin = $dateFin ?: now()->format('Y-m-d');
            $clientsAvecFactures = Client::with('compteComptable')
                ->whereHas('facturesClient', function ($q) use ($deb, $fin) {
                    $q->whereBetween('date_facture', [$deb, $fin]);
                })
                ->orderBy('nom')
                ->get();

            $numero = 0;
            foreach ($clientsAvecFactures as $client) {
                $factures = $client->facturesClient()
                    ->whereBetween('date_facture', [$deb, $fin])
                    ->get();

                $totalFacture = $factures->sum(fn($f) => (float) $f->montant - (float) ($f->ristourne ?? 0));
                $totalPaye = $factures->sum(fn($f) => (float) $f->montant_paye);
                $totalRejet = max(0, $totalFacture - $totalPaye);

                $numero++;
                $data[] = [
                    'numero' => $numero,
                    'numero_compte' => $client->compteComptable?->numero_compte ?? '-',
                    'raison_sociale' => $client->nom,
                    'total_facture' => $totalFacture,
                    'total_paye' => $totalPaye,
                    'total_rejet' => $totalRejet,
                ];
            }

            $dateDebut = $deb;
            $dateFin = $fin;
        }

        return [
            'data' => $data,
            'mode' => $mode,
            'periode' => ['debut' => $dateDebut, 'fin' => $dateFin],
            'selectedClientId' => $clientId ? (int) $clientId : null,
        ];
    }

    private function buildEtatCreancesData(Request $request): array
    {
        $mode = $request->input('mode', 'par_client');
        $clientId = $request->input('client_id');
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');

        $data = [];

        if ($mode === 'par_client' || $mode === 'un_client') {
            $query = FactureClient::with(['client.compteComptable'])
                ->where('reste_a_payer', '>', 0);

            if ($mode === 'un_client' && $clientId) {
                $query->where('client_id', $clientId);
            }

            $factures = $query->orderBy('client_id')->orderBy('date_facture')->get();

            $grouped = $factures->groupBy('client_id');
            foreach ($grouped as $cId => $clientFactures) {
                $client = $clientFactures->first()->client;
                $lignes = [];
                $totalFacture = 0;
                $totalPaye = 0;
                $totalReste = 0;

                foreach ($clientFactures as $index => $facture) {
                    $montantFacture = (float) $facture->montant - (float) ($facture->ristourne ?? 0);
                    $montantPaye = (float) $facture->montant_paye;
                    $resteAPayer = max(0, $montantFacture - $montantPaye);

                    $lignes[] = [
                        'numero' => $index + 1,
                        'reference' => $facture->reference,
                        'date_facture' => $facture->date_facture?->format('d/m/Y'),
                        'montant_facture' => $montantFacture,
                        'montant_paye' => $montantPaye,
                        'reste_a_payer' => $resteAPayer,
                    ];

                    $totalFacture += $montantFacture;
                    $totalPaye += $montantPaye;
                    $totalReste += $resteAPayer;
                }

                $data[] = [
                    'client_id' => $cId,
                    'numero_compte' => $client->compteComptable?->numero_compte ?? '-',
                    'raison_sociale' => $client->nom,
                    'lignes' => $lignes,
                    'total_facture' => $totalFacture,
                    'total_paye' => $totalPaye,
                    'total_reste' => $totalReste,
                ];
            }
        } elseif ($mode === 'tous_clients') {
            $clientsAvecCreances = Client::with('compteComptable')
                ->whereHas('facturesClient', function ($q) use ($dateDebut, $dateFin) {
                    $q->where('reste_a_payer', '>', 0);
                    if ($dateDebut) $q->where('date_facture', '>=', $dateDebut);
                    if ($dateFin) $q->where('date_facture', '<=', $dateFin);
                })
                ->orderBy('nom')
                ->get();

            $numero = 0;
            foreach ($clientsAvecCreances as $client) {
                $facturesQuery = $client->facturesClient()
                    ->where('reste_a_payer', '>', 0);
                if ($dateDebut) $facturesQuery->where('date_facture', '>=', $dateDebut);
                if ($dateFin) $facturesQuery->where('date_facture', '<=', $dateFin);
                $factures = $facturesQuery->get();

                $totalFacture = $factures->sum(fn($f) => (float) $f->montant - (float) ($f->ristourne ?? 0));
                $totalPaye = $factures->sum(fn($f) => (float) $f->montant_paye);
                $totalReste = max(0, $totalFacture - $totalPaye);

                $numero++;
                $data[] = [
                    'numero' => $numero,
                    'numero_compte' => $client->compteComptable?->numero_compte ?? '-',
                    'raison_sociale' => $client->nom,
                    'total_facture' => $totalFacture,
                    'total_paye' => $totalPaye,
                    'total_reste' => $totalReste,
                ];
            }
        }

        return [
            'data' => $data,
            'mode' => $mode,
            'periode' => ['debut' => $dateDebut, 'fin' => $dateFin],
            'selectedClientId' => $clientId ? (int) $clientId : null,
        ];
    }

    private function buildBrouillardChequesData(Request $request): array
    {
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');

        $data = [];

        if ($dateDebut && $dateFin) {
            $reglements = ReglementClient::with([
                    'client.compteComptable',
                    'facture',
                    'banqueDepot',
                    'compteBancaire.compteOhada',
                ])
                ->where('date_reglement', '>=', $dateDebut)
                ->where('date_reglement', '<=', $dateFin)
                ->orderBy('date_reglement')
                ->orderBy('id')
                ->get();

            $solde = 0;
            foreach ($reglements as $r) {
                $montant = (float) $r->montant;
                $isCheque = !empty($r->reference_cheque);

                $libelle = $isCheque
                    ? "CHn° {$r->reference_cheque}/{$r->client->nom}"
                    : ($r->institution
                        ? "SBn° {$r->institution}/{$r->client->nom}"
                        : $r->client->nom);

                $debit = $isCheque ? $montant : 0;
                $credit = !$isCheque ? $montant : 0;
                $solde += $debit - $credit;

                $compteDebit = $r->compteBancaire?->compteOhada?->numero_compte
                    ?? ($r->banqueDepot ? '52' : '57');
                $compteDebitLib = $r->compteBancaire?->compteOhada?->libelle
                    ?? ($r->banqueDepot ? 'Banque ' . $r->banqueDepot->nom : 'Caisse');
                $compteCredit = $r->client->compteComptable?->numero_compte ?? '411';
                $compteCreditLib = $r->client->compteComptable?->libelle ?? 'Clients';

                $data[] = [
                    'date' => $r->date_reglement->format('d/m/Y'),
                    'date_raw' => $r->date_reglement->format('Y-m-d'),
                    'libelle' => $libelle,
                    'debit' => $debit,
                    'credit' => $credit,
                    'solde' => $solde,
                    'montant' => $montant,
                    'reference' => $r->reference_cheque ?? $r->institution ?? '-',
                    'client_nom' => $r->client->nom,
                    'facture_ref' => $r->facture?->reference ?? '-',
                    'compte_debit' => $compteDebit,
                    'compte_debit_libelle' => $compteDebitLib,
                    'compte_credit' => $compteCredit,
                    'compte_credit_libelle' => $compteCreditLib,
                ];
            }
        }

        return [
            'data' => $data,
            'periode' => ['debut' => $dateDebut, 'fin' => $dateFin],
        ];
    }

    private function buildChiffreAffairesData(Request $request): array
    {
        $mode = $request->input('mode', 'global_du');
        $clientId = $request->input('client_id');
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');
        $dateRef = $request->input('date_ref');

        $data = [];

        if (in_array($mode, ['global_du', 'global_au', 'global_periode'])) {
            $facturesQuery = FactureClient::query();
            $reglementsQuery = ReglementClient::query();

            if ($mode === 'global_du' && $dateRef) {
                $facturesQuery->whereDate('date_facture', $dateRef);
                $reglementsQuery->whereDate('date_reglement', $dateRef);
            } elseif ($mode === 'global_au' && $dateRef) {
                $facturesQuery->where('date_facture', '<=', $dateRef);
                $reglementsQuery->where('date_reglement', '<=', $dateRef);
            } elseif ($mode === 'global_periode') {
                if ($dateDebut) {
                    $facturesQuery->where('date_facture', '>=', $dateDebut);
                    $reglementsQuery->where('date_reglement', '>=', $dateDebut);
                }
                if ($dateFin) {
                    $facturesQuery->where('date_facture', '<=', $dateFin);
                    $reglementsQuery->where('date_reglement', '<=', $dateFin);
                }
            }

            $factures = $facturesQuery->get();
            $theorique = $factures->sum(fn($f) => (float) $f->montant - (float) ($f->ristourne ?? 0));
            $physique = (float) $reglementsQuery->sum('montant');

            $data = [
                'theorique' => $theorique,
                'physique' => $physique,
                'ecart' => $theorique - $physique,
            ];
        } elseif ($mode === 'par_client' && $clientId) {
            $client = Client::with('compteComptable')->find($clientId);
            if ($client) {
                $facturesQuery = FactureClient::where('client_id', $clientId)
                    ->orderBy('date_facture');
                if ($dateDebut) $facturesQuery->where('date_facture', '>=', $dateDebut);
                if ($dateFin) $facturesQuery->where('date_facture', '<=', $dateFin);
                $factures = $facturesQuery->get();

                $lignes = [];
                $totalCA = 0;
                foreach ($factures as $index => $f) {
                    $montantCA = (float) $f->montant - (float) ($f->ristourne ?? 0);
                    $lignes[] = [
                        'numero' => $index + 1,
                        'reference' => $f->reference,
                        'date_facture' => $f->date_facture?->format('d/m/Y'),
                        'montant' => $montantCA,
                    ];
                    $totalCA += $montantCA;
                }

                $data = [
                    'numero_compte' => $client->compteComptable?->numero_compte ?? '-',
                    'raison_sociale' => $client->nom,
                    'lignes' => $lignes,
                    'total_ca' => $totalCA,
                ];
            }
        }

        return [
            'data' => $data,
            'mode' => $mode,
            'periode' => ['debut' => $dateDebut, 'fin' => $dateFin],
            'dateRef' => $dateRef,
            'selectedClientId' => $clientId ? (int) $clientId : null,
        ];
    }

    // ==========================================
    // JSON API ENDPOINTS
    // ==========================================

    public function etatReglements(Request $request): JsonResponse
    {
        return response()->json($this->buildEtatReglementsData($request));
    }

    public function etatCreances(Request $request): JsonResponse
    {
        return response()->json($this->buildEtatCreancesData($request));
    }

    public function brouillardCheques(Request $request): JsonResponse
    {
        return response()->json($this->buildBrouillardChequesData($request));
    }

    public function chiffreAffaires(Request $request): JsonResponse
    {
        return response()->json($this->buildChiffreAffairesData($request));
    }

    public function pertesRejets(Request $request): JsonResponse
    {
        return response()->json(['data' => [], 'message' => 'En cours de développement']);
    }

    // ==========================================
    // PDF EXPORT ENDPOINTS
    // ==========================================

    public function etatReglementsPdf(Request $request)
    {
        $result = $this->buildEtatReglementsData($request);
        $result['titre'] = 'État des Règlements Clients';
        $result['generatedAt'] = now()->format('d/m/Y à H:i');

        $pdf = Pdf::loadView('pdf.rapports-clients.etat-reglements', $result);
        $pdf->setPaper('a4', 'portrait');

        return $request->query('action') === 'stream'
            ? $pdf->stream('etat-reglements.pdf')
            : $pdf->download('etat-reglements.pdf');
    }

    public function etatCreancesPdf(Request $request)
    {
        $result = $this->buildEtatCreancesData($request);
        $result['titre'] = 'États Périodiques des Créances Clients';
        $result['generatedAt'] = now()->format('d/m/Y à H:i');

        $pdf = Pdf::loadView('pdf.rapports-clients.etat-creances', $result);
        $pdf->setPaper('a4', 'portrait');

        return $request->query('action') === 'stream'
            ? $pdf->stream('etat-creances.pdf')
            : $pdf->download('etat-creances.pdf');
    }

    public function brouillardChequesPdf(Request $request)
    {
        $result = $this->buildBrouillardChequesData($request);
        $result['titre'] = 'Brouillard de Chèques & Imputations Comptables';
        $result['generatedAt'] = now()->format('d/m/Y à H:i');

        $pdf = Pdf::loadView('pdf.rapports-clients.brouillard-cheques', $result);
        $pdf->setPaper('a4', 'portrait');

        return $request->query('action') === 'stream'
            ? $pdf->stream('brouillard-cheques.pdf')
            : $pdf->download('brouillard-cheques.pdf');
    }

    public function chiffreAffairesPdf(Request $request)
    {
        $result = $this->buildChiffreAffairesData($request);
        $result['titre'] = "Chiffre d'Affaire";
        $result['generatedAt'] = now()->format('d/m/Y à H:i');

        $pdf = Pdf::loadView('pdf.rapports-clients.chiffre-affaires', $result);
        $pdf->setPaper('a4', 'portrait');

        return $request->query('action') === 'stream'
            ? $pdf->stream('chiffre-affaires.pdf')
            : $pdf->download('chiffre-affaires.pdf');
    }

    // ==========================================
    // STANDALONE PAGES (backward compat)
    // ==========================================

    public function etatReglementsPage(Request $request)
    {
        $result = $this->buildEtatReglementsData($request);
        $result['clients'] = $this->getClientsList();
        return Inertia::render('Rapports/Clients/EtatReglements', $result);
    }

    public function etatCreancesPage(Request $request)
    {
        $result = $this->buildEtatCreancesData($request);
        $result['clients'] = $this->getClientsList();
        return Inertia::render('Rapports/Clients/EtatCreances', $result);
    }

    public function brouillardChequesPage(Request $request)
    {
        $result = $this->buildBrouillardChequesData($request);
        return Inertia::render('Rapports/Clients/BrouillardCheques', $result);
    }

    public function chiffreAffairesPage(Request $request)
    {
        $result = $this->buildChiffreAffairesData($request);
        $result['clients'] = $this->getClientsList();
        return Inertia::render('Rapports/Clients/ChiffreAffaires', $result);
    }

    public function pertesRejetsPage()
    {
        $periode = ['debut' => '2025-01-01', 'fin' => '2025-01-31'];
        return Inertia::render('Rapports/Clients/PertesRejets', [
            'pertes' => [],
            'rejets' => [],
            'regularisations' => [],
            'periode' => $periode,
        ]);
    }
}
