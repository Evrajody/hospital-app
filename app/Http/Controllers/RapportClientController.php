<?php

namespace App\Http\Controllers;

use App\Models\ApprovisionnementBanque;
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
                'type_client' => $c->type_client,
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
            'banques' => \App\Models\Banque::orderBy('nom')->get(['id', 'nom']),
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
        $typeClient = $request->input('type_client');

        $data = [];

        if ($mode === 'par_client' || $mode === 'un_client') {
            $query = FactureClient::with(['client.compteComptable', 'reglements']);

            if ($mode === 'un_client' && $clientId) {
                $query->where('client_id', $clientId);
            }

            if ($typeClient) {
                $query->whereHas('client', fn($q) => $q->where('type_client', $typeClient));
            }

            if ($dateDebut && $dateFin) {
                $query->whereBetween('date_facture', [$dateDebut, $dateFin]);
            }

            $factures = $query->orderBy('client_id')->orderByDesc('date_facture')->get();

            $grouped = $factures->groupBy('client_id');
            foreach ($grouped as $cId => $clientFactures) {
                $client = $clientFactures->first()->client;
                $lignes = [];
                $totalFacture = 0;
                $totalPaye = 0;
                $totalPerte = 0;
                $totalRejet = 0;
                $totalSolde = 0;

                foreach ($clientFactures as $index => $facture) {
                    $montantFacture = (float) $facture->montant - (float) ($facture->ristourne ?? 0);
                    $reglementsNonPerte = $facture->reglements->where('type_reglement', '!=', 'perte');
                    $reglementsPerte = $facture->reglements->where('type_reglement', 'perte');
                    $montantPaye = (float) $reglementsNonPerte->sum('montant');
                    $totalPerteFacture = (float) $reglementsPerte->sum('montant');
                    $totalRejetFacture = (float) $facture->reglements->sum('montant_rejet');
                    $solde = $montantFacture - $montantPaye - $totalPerteFacture - $totalRejetFacture;

                    $dernierReglement = $facture->reglements->sortByDesc('date_reglement')->first();

                    $lignes[] = [
                        'numero' => $index + 1,
                        'reference' => $facture->reference,
                        'date_facture' => $facture->date_facture?->format('d/m/Y'),
                        'date_reglement' => $dernierReglement?->date_reglement?->format('d/m/Y') ?? '-',
                        'montant_facture' => $montantFacture,
                        'montant_paye' => $montantPaye,
                        'total_perte' => $totalPerteFacture,
                        'total_rejet' => $totalRejetFacture,
                        'solde' => $solde,
                    ];

                    $totalFacture += $montantFacture;
                    $totalPaye += $montantPaye;
                    $totalPerte += $totalPerteFacture;
                    $totalRejet += $totalRejetFacture;
                    $totalSolde += $solde;
                }

                $data[] = [
                    'client_id' => $cId,
                    'numero_compte' => $client->compteComptable?->numero_compte ?? '-',
                    'raison_sociale' => $client->nom,
                    'type_client' => $client->type_client,
                    'type_client_label' => $client->type_client
                        ? (Client::TYPES_LABELS[$client->type_client] ?? ucfirst($client->type_client))
                        : null,
                    'lignes' => $lignes,
                    'total_facture' => $totalFacture,
                    'total_paye' => $totalPaye,
                    'total_perte' => $totalPerte,
                    'total_rejet' => $totalRejet,
                    'total_solde' => $totalSolde,
                ];
            }
        } elseif ($mode === 'tous_clients') {
            $deb = $dateDebut ?: now()->startOfYear()->format('Y-m-d');
            $fin = $dateFin ?: now()->format('Y-m-d');
            // Eager-load des factures de la période + de leurs règlements → évite le N+1
            // (1 requête clients + 1 factures + 1 règlements au lieu d'une requête par client).
            $clientsAvecFactures = Client::with([
                    'compteComptable',
                    'facturesClient' => fn($q) => $q->whereBetween('date_facture', [$deb, $fin]),
                    'facturesClient.reglements',
                ])
                ->when($typeClient, fn($q) => $q->where('type_client', $typeClient))
                ->whereHas('facturesClient', function ($q) use ($deb, $fin) {
                    $q->whereBetween('date_facture', [$deb, $fin]);
                })
                ->orderBy('nom')
                ->get();

            $numero = 0;
            foreach ($clientsAvecFactures as $client) {
                $factures = $client->facturesClient; // déjà chargées et filtrées (eager-load)

                $totalFacture = 0;
                $totalPaye = 0;
                $totalPerte = 0;
                $totalRejet = 0;
                foreach ($factures as $f) {
                    $mf = (float) $f->montant - (float) ($f->ristourne ?? 0);
                    $mp = (float) $f->reglements->where('type_reglement', '!=', 'perte')->sum('montant');
                    $tp = (float) $f->reglements->where('type_reglement', 'perte')->sum('montant');
                    $tr = (float) $f->reglements->sum('montant_rejet');
                    $totalFacture += $mf;
                    $totalPaye += $mp;
                    $totalPerte += $tp;
                    $totalRejet += $tr;
                }
                $totalSolde = $totalFacture - $totalPaye - $totalPerte - $totalRejet;

                $numero++;
                $data[] = [
                    'numero' => $numero,
                    'numero_compte' => $client->compteComptable?->numero_compte ?? '-',
                    'raison_sociale' => $client->nom,
                    'type_client' => $client->type_client,
                    'type_client_label' => $client->type_client
                        ? (Client::TYPES_LABELS[$client->type_client] ?? ucfirst($client->type_client))
                        : null,
                    'total_facture' => $totalFacture,
                    'total_paye' => $totalPaye,
                    'total_perte' => $totalPerte,
                    'total_rejet' => $totalRejet,
                    'total_solde' => $totalSolde,
                ];
            }

            $dateDebut = $deb;
            $dateFin = $fin;
        }

        // Groupement par type uniquement pour le mode "par_client" sans filtre de type
        $groupesParType = null;
        if ($mode === 'par_client' && !$typeClient && count($data) > 0) {
            $groupes = [];
            foreach ($data as $clientData) {
                $type = $clientData['type_client'] ?: 'non_defini';
                $label = $clientData['type_client_label'] ?: 'Non défini';
                if (!isset($groupes[$type])) {
                    $groupes[$type] = [
                        'type' => $type,
                        'label' => $label,
                        'clients' => [],
                    ];
                }
                $groupes[$type]['clients'][] = $clientData;
            }
            $groupesParType = array_values($groupes);
        }

        return [
            'data' => $data,
            'groupes_par_type' => $groupesParType,
            'mode' => $mode,
            'periode' => ['debut' => $dateDebut, 'fin' => $dateFin],
            'selectedClientId' => $clientId ? (int) $clientId : null,
            'type_client' => $typeClient,
            'type_client_label' => $typeClient
                ? (Client::TYPES_LABELS[$typeClient] ?? ucfirst($typeClient))
                : null,
        ];
    }

    private function buildEtatCreancesData(Request $request): array
    {
        $mode = $request->input('mode', 'par_client');
        $clientId = $request->input('client_id');
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');
        $typeClient = $request->input('type_client');

        $data = [];

        if ($mode === 'par_client' || $mode === 'un_client') {
            $query = FactureClient::with(['client.compteComptable', 'reglements'])
                ->where('reste_a_payer', '>', 0);

            if ($mode === 'un_client' && $clientId) {
                $query->where('client_id', $clientId);
            }

            if ($typeClient) {
                $query->whereHas('client', fn($q) => $q->where('type_client', $typeClient));
            }

            if ($dateDebut) {
                $query->where('date_facture', '>=', $dateDebut);
            }
            if ($dateFin) {
                $query->where('date_facture', '<=', $dateFin);
            }

            $factures = $query->orderBy('client_id')->orderByDesc('date_facture')->get();

            $grouped = $factures->groupBy('client_id');
            foreach ($grouped as $cId => $clientFactures) {
                $client = $clientFactures->first()->client;
                $lignes = [];
                $totalFacture = 0;
                $totalPaye = 0;
                $totalPerte = 0;
                $totalRejet = 0;
                $totalReste = 0;

                foreach ($clientFactures as $index => $facture) {
                    $montantFacture = (float) $facture->montant - (float) ($facture->ristourne ?? 0);
                    $montantPaye = (float) $facture->reglements->where('type_reglement', '!=', 'perte')->sum('montant');
                    $perteFacture = (float) $facture->reglements->where('type_reglement', 'perte')->sum('montant');
                    $rejetFacture = (float) $facture->reglements->sum('montant_rejet');
                    $resteAPayer = max(0, $montantFacture - $montantPaye);

                    $lignes[] = [
                        'numero' => $index + 1,
                        'reference' => $facture->reference,
                        'date_facture' => $facture->date_facture?->format('d/m/Y'),
                        'montant_facture' => $montantFacture,
                        'montant_paye' => $montantPaye,
                        'total_rejet' => $rejetFacture,
                        'total_perte' => $perteFacture,
                        'reste_a_payer' => $resteAPayer,
                    ];

                    $totalFacture += $montantFacture;
                    $totalPaye += $montantPaye;
                    $totalPerte += $perteFacture;
                    $totalRejet += $rejetFacture;
                    $totalReste += $resteAPayer;
                }

                $data[] = [
                    'client_id' => $cId,
                    'numero_compte' => $client->compteComptable?->numero_compte ?? '-',
                    'raison_sociale' => $client->nom,
                    'type_client' => $client->type_client,
                    'type_client_label' => $client->type_client
                        ? (Client::TYPES_LABELS[$client->type_client] ?? ucfirst($client->type_client))
                        : null,
                    'lignes' => $lignes,
                    'total_facture' => $totalFacture,
                    'total_paye' => $totalPaye,
                    'total_perte' => $totalPerte,
                    'total_rejet' => $totalRejet,
                    'total_reste' => $totalReste,
                ];
            }
        } elseif ($mode === 'tous_clients') {
            $clientsAvecCreances = Client::with('compteComptable')
                ->when($typeClient, fn($q) => $q->where('type_client', $typeClient))
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
                    ->with('reglements')
                    ->where('reste_a_payer', '>', 0);
                if ($dateDebut) $facturesQuery->where('date_facture', '>=', $dateDebut);
                if ($dateFin) $facturesQuery->where('date_facture', '<=', $dateFin);
                $factures = $facturesQuery->get();

                $totalFacture = 0;
                $totalPaye = 0;
                $totalPerte = 0;
                $totalRejet = 0;
                foreach ($factures as $f) {
                    $totalFacture += (float) $f->montant - (float) ($f->ristourne ?? 0);
                    $totalPaye += (float) $f->reglements->where('type_reglement', '!=', 'perte')->sum('montant');
                    $totalPerte += (float) $f->reglements->where('type_reglement', 'perte')->sum('montant');
                    $totalRejet += (float) $f->reglements->sum('montant_rejet');
                }
                $totalReste = max(0, $totalFacture - $totalPaye);

                $numero++;
                $data[] = [
                    'numero' => $numero,
                    'numero_compte' => $client->compteComptable?->numero_compte ?? '-',
                    'raison_sociale' => $client->nom,
                    'type_client' => $client->type_client,
                    'type_client_label' => $client->type_client
                        ? (Client::TYPES_LABELS[$client->type_client] ?? ucfirst($client->type_client))
                        : null,
                    'total_perte' => $totalPerte,
                    'total_rejet' => $totalRejet,
                    'total_facture' => $totalFacture,
                    'total_paye' => $totalPaye,
                    'total_reste' => $totalReste,
                ];
            }
        }

        // Groupement par type uniquement pour le mode "par_client" sans filtre de type
        $groupesParType = null;
        if ($mode === 'par_client' && !$typeClient && count($data) > 0) {
            $groupes = [];
            foreach ($data as $clientData) {
                $type = $clientData['type_client'] ?: 'non_defini';
                $label = $clientData['type_client_label'] ?: 'Non défini';
                if (!isset($groupes[$type])) {
                    $groupes[$type] = [
                        'type' => $type,
                        'label' => $label,
                        'clients' => [],
                    ];
                }
                $groupes[$type]['clients'][] = $clientData;
            }
            $groupesParType = array_values($groupes);
        }

        return [
            'data' => $data,
            'groupes_par_type' => $groupesParType,
            'mode' => $mode,
            'periode' => ['debut' => $dateDebut, 'fin' => $dateFin],
            'selectedClientId' => $clientId ? (int) $clientId : null,
            'type_client' => $typeClient,
            'type_client_label' => $typeClient
                ? (Client::TYPES_LABELS[$typeClient] ?? ucfirst($typeClient))
                : null,
        ];
    }

    private function buildBrouillardChequesData(Request $request): array
    {
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');

        $data = [];

        if ($dateDebut && $dateFin) {
            // On groupe par approvisionnement (SB) : pour chaque SB de la période,
            // on liste ses chèques (CH liés via approvisionnement_id), puis la ligne SB.
            // Le solde repart à 0 à chaque nouveau groupe SB.
            $approvisionnements = ApprovisionnementBanque::with([
                    'compteBancaire.banque',
                    'reglementsClients' => fn($q) => $q
                        ->where('type_reglement', '!=', 'perte')
                        ->whereNotNull('reference_cheque')
                        ->where('reference_cheque', '!=', '')
                        ->with('client.compteComptable', 'facture')
                        ->orderBy('date_reglement')
                        ->orderBy('id'),
                ])
                ->whereNotNull('reference_bordereau')
                ->where('reference_bordereau', '!=', '')
                ->where('date_depot', '>=', $dateDebut)
                ->where('date_depot', '<=', $dateFin)
                ->orderByDesc('date_depot')
                ->orderBy('id')
                ->get();

            $lastDate = null;
            foreach ($approvisionnements as $appro) {
                // Un bordereau SANS chèque n'a pas sa place dans un brouillard de CHÈQUES.
                if ($appro->reglementsClients->isEmpty()) {
                    continue;
                }
                $solde = 0;
                $dateGroupe = $appro->date_depot->format('d/m/Y');
                $dateRawGroupe = $appro->date_depot->format('Y-m-d');
                $showDateForGroup = $dateGroupe !== $lastDate;
                $lastDate = $dateGroupe;
                $isFirstLineOfGroup = true;

                // Lignes CH du groupe
                foreach ($appro->reglementsClients as $r) {
                    $montant = (float) $r->montant;
                    $solde += $montant;
                    $compteCredit = $r->client?->compteComptable?->numero_compte ?? '411';
                    $compteCreditLib = $r->client?->compteComptable?->libelle ?? 'Clients';

                    // Libellé du chèque complété par l'institution saisie lors du règlement.
                    $chequeInfos = collect([$r->client?->nom, $r->institution])
                        ->map(fn($v) => trim((string) $v))
                        ->filter()
                        ->implode(' - ');

                    $data[] = [
                        'date' => $dateGroupe,
                        'show_date' => $showDateForGroup && $isFirstLineOfGroup,
                        'date_raw' => $dateRawGroupe,
                        'group_id' => 'sb-' . $appro->id,
                        'nature' => 'CH',
                        'libelle' => "CHn° {$r->reference_cheque}/" . ($chequeInfos !== '' ? $chequeInfos : '-'),
                        'debit' => $montant,
                        'credit' => 0,
                        'solde' => $solde,
                        'montant' => $montant,
                        'reference' => $r->reference_cheque,
                        'client_nom' => $r->client?->nom ?? '-',
                        'facture_ref' => $r->facture?->reference ?? '-',
                        'compte_debit' => '57',
                        'compte_debit_libelle' => 'Chèques à encaisser',
                        'compte_credit' => $compteCredit,
                        'compte_credit_libelle' => $compteCreditLib,
                    ];
                    $isFirstLineOfGroup = false;
                }

                // Ligne SB (clôture du groupe)
                $montantSB = (float) $appro->montant;
                $solde -= $montantSB;
                $banqueNom = $appro->compteBancaire?->banque?->nom ?? '-';

                $data[] = [
                    'date' => $dateGroupe,
                    'show_date' => $showDateForGroup && $isFirstLineOfGroup,
                    'date_raw' => $dateRawGroupe,
                    'group_id' => 'sb-' . $appro->id,
                    'nature' => 'SB',
                    'libelle' => "SBn° {$appro->reference_bordereau}/{$banqueNom}",
                    'debit' => 0,
                    'credit' => $montantSB,
                    'solde' => $solde,
                    'montant' => $montantSB,
                ];
            }
        }

        return [
            'data' => $data,
            'periode' => ['debut' => $dateDebut, 'fin' => $dateFin],
        ];
    }

    /**
     * Imputations comptables clients : pour chaque SB (bordereau déposé) de la période,
     * on génère une écriture comptable groupée :
     *  - 1 ligne débit : compte bancaire OHADA + montant SB + "S/Rglt {banque}"
     *  - N lignes crédit : compte client OHADA + montant CH + "Fn°{ref_facture}"
     */
    private function buildImputationsComptablesData(Request $request): array
    {
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');

        $groupes = [];

        if ($dateDebut && $dateFin) {
            $approvisionnements = ApprovisionnementBanque::with([
                    'compteBancaire.banque',
                    'compteBancaire.compteOhada',
                    'reglementsClients' => fn($q) => $q
                        ->where('type_reglement', '!=', 'perte')
                        ->whereNotNull('reference_cheque')
                        ->where('reference_cheque', '!=', '')
                        ->with('client.compteComptable', 'facture')
                        ->orderBy('date_reglement')
                        ->orderBy('id'),
                ])
                ->whereNotNull('reference_bordereau')
                ->where('reference_bordereau', '!=', '')
                ->where('date_depot', '>=', $dateDebut)
                ->where('date_depot', '<=', $dateFin)
                ->orderByDesc('date_depot')
                ->orderBy('id')
                ->get();

            foreach ($approvisionnements as $appro) {
                // Pas de chèque sur ce bordereau → aucune écriture d'imputation à générer
                // (sinon on aurait un débit banque sans contrepartie crédit = écriture déséquilibrée).
                if ($appro->reglementsClients->isEmpty()) {
                    continue;
                }
                $banqueNom = $appro->compteBancaire?->banque?->nom ?? '-';
                $compteBanque = $appro->compteBancaire?->compteOhada?->numero_compte ?? '52';
                $montantSB = (float) $appro->montant;

                $lignes = [];

                // Ligne débit (banque)
                $lignes[] = [
                    'compte' => $compteBanque,
                    'debit' => $montantSB,
                    'credit' => 0,
                    'libelle' => "S/Rglt {$banqueNom}",
                ];

                // Lignes crédit (clients)
                foreach ($appro->reglementsClients as $r) {
                    $compteClient = $r->client?->compteComptable?->numero_compte ?? '411';
                    $ref = $r->facture_reference ?: ($r->facture?->reference ?? '-');
                    $lignes[] = [
                        'compte' => $compteClient,
                        'debit' => 0,
                        'credit' => (float) $r->montant,
                        'libelle' => "Fn°{$ref}",
                    ];
                }

                $groupes[] = [
                    'date' => $appro->date_depot->format('d/m/Y'),
                    'date_raw' => $appro->date_depot->format('Y-m-d'),
                    'reference_bordereau' => $appro->reference_bordereau,
                    'lignes' => $lignes,
                ];
            }
        }

        return [
            'groupes' => $groupes,
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
                    ->orderByDesc('date_facture');
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

    private function buildPertesRejetsData(Request $request): array
    {
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');
        $typeFilter = $request->input('type_reglement'); // perte, rejet or null for all
        $clientId = $request->input('client_id');
        $typeClient = $request->input('type_client');

        // Un règlement entre dans le rapport s'il est :
        //  - de type "perte" (pertes sèches), OU
        //  - de type "reglement" mais avec un montant_rejet > 0 (rejet partiel/total du chèque).
        $query = ReglementClient::with(['client.compteComptable', 'facture', 'banqueDepot'])
            ->where(function ($q) {
                $q->where('type_reglement', 'perte')
                  ->orWhere('montant_rejet', '>', 0);
            });

        if ($typeFilter === 'perte') {
            $query->where('type_reglement', 'perte');
        } elseif ($typeFilter === 'rejet') {
            $query->where('montant_rejet', '>', 0);
        }

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        if ($typeClient) {
            $query->whereHas('client', fn($q) => $q->where('type_client', $typeClient));
        }

        if ($dateDebut) {
            $query->where('date_reglement', '>=', $dateDebut);
        }
        if ($dateFin) {
            $query->where('date_reglement', '<=', $dateFin);
        }

        $reglements = $query->orderByDesc('date_reglement')->orderBy('id')->get();

        // Chaque règlement peut générer 1 ou 2 lignes dans le rapport :
        //  - une ligne "perte"  si type_reglement = 'perte'
        //  - une ligne "rejet"  si montant_rejet > 0
        $eclate = [];
        foreach ($reglements as $r) {
            $base = [
                'reglement_id' => $r->id,
                'date_reglement' => $r->date_reglement?->format('d/m/Y'),
                'date_reglement_raw' => $r->date_reglement?->format('Y-m-d'),
                'client_id' => $r->client_id,
                'client_nom' => $r->client_nom ?: $r->client?->nom,
                'client_code' => $r->client?->compteComptable?->numero_compte ?? '-',
                'type_client' => $r->client?->type_client,
                'type_client_label' => $r->client ? (\App\Models\Client::TYPES_LABELS[$r->client->type_client] ?? $r->client->type_client) : null,
                'facture_reference' => $r->facture_reference ?: $r->facture?->reference,
                'reference_cheque' => $r->reference_cheque,
                'institution' => $r->institution,
                'banque_depot' => $r->banqueDepot?->nom,
                'observations' => $r->observations,
            ];

            if ($r->type_reglement === 'perte') {
                $eclate[] = $base + [
                    'nature' => 'perte',
                    'nature_libelle' => 'Perte',
                    'montant' => (float) $r->montant,
                ];
            }

            $montantRejet = (float) ($r->montant_rejet ?? 0);
            if ($montantRejet > 0 && $r->type_reglement !== 'perte') {
                $eclate[] = $base + [
                    'nature' => 'rejet',
                    'nature_libelle' => 'Rejet',
                    'montant' => $montantRejet,
                ];
            }
        }

        // Application du filtre nature côté lignes éclatées (utile si une perte aurait aussi un montant_rejet, cas marginal).
        if ($typeFilter === 'perte' || $typeFilter === 'rejet') {
            $eclate = array_values(array_filter($eclate, fn($l) => $l['nature'] === $typeFilter));
        }

        $totaux = ['pertes' => 0, 'rejets' => 0];
        foreach ($eclate as $l) {
            if ($l['nature'] === 'perte') $totaux['pertes'] += $l['montant'];
            elseif ($l['nature'] === 'rejet') $totaux['rejets'] += $l['montant'];
        }
        $totaux['solde'] = $totaux['pertes'] + $totaux['rejets'];

        // Construire la sortie "data" plate (pour le tableau de l'onglet)
        $data = [];
        foreach ($eclate as $l) {
            $data[] = [
                'id' => $l['reglement_id'] . '-' . $l['nature'],
                'reglement_id' => $l['reglement_id'],
                'date_reglement' => $l['date_reglement'],
                'date_reglement_raw' => $l['date_reglement_raw'],
                'type_reglement' => $l['nature'],
                'type_reglement_libelle' => $l['nature_libelle'],
                'type_reglement_couleur' => $l['nature'] === 'perte' ? 'danger' : 'warning',
                'client_nom' => $l['client_nom'],
                'client_code' => $l['client_code'],
                'type_client' => $l['type_client'],
                'type_client_label' => $l['type_client_label'],
                'facture_reference' => $l['facture_reference'],
                'montant' => $l['montant'],
                'reference_cheque' => $l['reference_cheque'],
                'institution' => $l['institution'],
                'banque_depot' => $l['banque_depot'],
                'observations' => $l['observations'],
            ];
        }

        // Grouper par client pour le PDF
        $parClient = [];
        foreach ($eclate as $l) {
            $cId = $l['client_id'];
            if (!isset($parClient[$cId])) {
                $parClient[$cId] = [
                    'client_id' => $cId,
                    'numero_compte' => $l['client_code'],
                    'raison_sociale' => $l['client_nom'] ?? '-',
                    'lignes' => [],
                    'total' => 0,
                ];
            }
            $parClient[$cId]['lignes'][] = [
                'numero' => count($parClient[$cId]['lignes']) + 1,
                'date_reglement' => $l['date_reglement'],
                'nature' => $l['nature'],
                'nature_libelle' => $l['nature_libelle'],
                'facture_reference' => $l['facture_reference'],
                'montant' => $l['montant'],
                'observations' => $l['observations'],
            ];
            $parClient[$cId]['total'] += $l['montant'];
        }
        $dataParClient = array_values($parClient);

        return [
            'data' => $data,
            'dataParClient' => $dataParClient,
            'totaux' => $totaux,
            'periode' => ['debut' => $dateDebut, 'fin' => $dateFin],
            'typeFilter' => $typeFilter,
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

    public function imputationsComptables(Request $request): JsonResponse
    {
        return response()->json($this->buildImputationsComptablesData($request));
    }

    public function chiffreAffaires(Request $request): JsonResponse
    {
        return response()->json($this->buildChiffreAffairesData($request));
    }

    public function pertesRejets(Request $request): JsonResponse
    {
        return response()->json($this->buildPertesRejetsData($request));
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
        // Le brouillard peut être volumineux (plusieurs milliers de lignes sur une
        // large période) → on relève la mémoire/le temps pour éviter un 500 en
        // génération synchrone (stream/print), comme le fait le job d'export asynchrone.
        @ini_set('memory_limit', '1024M');
        @set_time_limit(300);

        $result = $this->buildBrouillardChequesData($request);
        $result['titre'] = 'Brouillard de Chèques';
        $result['generatedAt'] = now()->format('d/m/Y à H:i');

        $pdf = Pdf::loadView('pdf.rapports-clients.brouillard-cheques', $result);
        $pdf->setPaper('a4', 'portrait');

        return $request->query('action') === 'stream'
            ? $pdf->stream('brouillard-cheques.pdf')
            : $pdf->download('brouillard-cheques.pdf');
    }

    public function imputationsComptablesPdf(Request $request)
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(300);

        $result = $this->buildImputationsComptablesData($request);
        $result['titre'] = 'Imputations Comptables Clients';
        $result['generatedAt'] = now()->format('d/m/Y à H:i');

        $pdf = Pdf::loadView('pdf.rapports-clients.imputations-comptables', $result);
        $pdf->setPaper('a4', 'portrait');

        return $request->query('action') === 'stream'
            ? $pdf->stream('imputations-comptables.pdf')
            : $pdf->download('imputations-comptables.pdf');
    }

    public function chiffreAffairesPdf(Request $request)
    {
        $result = $this->buildChiffreAffairesData($request);
        $result['titre'] = "CHIFFRES D'AFFAIRES (CA)";
        $result['generatedAt'] = now()->format('d/m/Y à H:i');

        $pdf = Pdf::loadView('pdf.rapports-clients.chiffre-affaires', $result);
        $pdf->setPaper('a4', 'portrait');

        return $request->query('action') === 'stream'
            ? $pdf->stream('chiffre-affaires.pdf')
            : $pdf->download('chiffre-affaires.pdf');
    }

    public function pertesRejetsPdf(Request $request)
    {
        $result = $this->buildPertesRejetsData($request);
        $result['titre'] = 'Pertes et Rejets';
        $result['generatedAt'] = now()->format('d/m/Y à H:i');

        $pdf = Pdf::loadView('pdf.rapports-clients.pertes-rejets', $result);
        $pdf->setPaper('a4', 'portrait');

        return $request->query('action') === 'stream'
            ? $pdf->stream('pertes-rejets.pdf')
            : $pdf->download('pertes-rejets.pdf');
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

    public function pertesRejetsPage(Request $request)
    {
        $result = $this->buildPertesRejetsData($request);
        $result['clients'] = $this->getClientsList();
        return Inertia::render('Rapports/Clients/PertesRejets', $result);
    }

    // ==========================================
    // EXCEL EXPORT ENDPOINTS
    // ==========================================

    public function etatReglementsExcel(Request $request)
    {
        $result = $this->buildEtatReglementsData($request);
        $mode = $result['mode'];
        $titre = 'État des Règlements Clients';
        if (!empty($result['type_client_label'])) {
            $titre .= ' — ' . strtoupper($result['type_client_label']);
        }
        if (($result['periode']['debut'] ?? null) && ($result['periode']['fin'] ?? null)) {
            $titre .= ' du ' . \Carbon\Carbon::parse($result['periode']['debut'])->format('d/m/Y')
                . ' au ' . \Carbon\Carbon::parse($result['periode']['fin'])->format('d/m/Y');
        }

        if ($mode === 'tous_clients') {
            $rows = [];
            if (empty($result['type_client'])) {
                $sorted = collect($result['data'])
                    ->sortBy(fn($r) => ($r['type_client_label'] ?? 'zzz') . '|' . ($r['raison_sociale'] ?? ''))
                    ->values();
                $lastType = null;
                foreach ($sorted as $c) {
                    $label = $c['type_client_label'] ?? 'Non défini';
                    if ($label !== $lastType) {
                        $rows[] = [$label, '', '', '', '', '', '', ''];
                        $lastType = $label;
                    }
                    $rows[] = [$c['numero'] ?? '', $c['numero_compte'], $c['raison_sociale'], $c['total_facture'], $c['total_paye'], $c['total_rejet'], $c['total_perte'], $c['total_solde']];
                }
            } else {
                foreach ($result['data'] as $c) {
                    $rows[] = [$c['numero'] ?? '', $c['numero_compte'], $c['raison_sociale'], $c['total_facture'], $c['total_paye'], $c['total_rejet'], $c['total_perte'], $c['total_solde']];
                }
            }
            return \App\Support\ExcelExporter::download(
                ['N°', 'Compte', 'Raison sociale', 'Total Facture', 'Total Payé', 'Total rejet', 'Total pertes', 'Solde'],
                $rows,
                'etat-reglements-clients',
                $titre,
            );
        }

        // Mode par_client / un_client : aplatir avec en-têtes (+ groupes par type si dispo)
        $rows = [];
        $renderGroupes = (!empty($result['groupes_par_type']))
            ? $result['groupes_par_type']
            : [['label' => null, 'clients' => $result['data']]];

        foreach ($renderGroupes as $groupe) {
            if (!empty($groupe['label'])) {
                $rows[] = ['=== ' . strtoupper($groupe['label']) . ' ===', '', '', '', '', '', '', '', '', ''];
            }
            foreach ($groupe['clients'] as $bloc) {
                $suffixe = ($mode === 'un_client' && !empty($bloc['type_client_label']))
                    ? ' - ' . $bloc['type_client_label']
                    : '';
                $rows[] = ['[' . $bloc['numero_compte'] . '] ' . $bloc['raison_sociale'] . $suffixe, '', '', '', '', '', '', '', '', ''];
                foreach ($bloc['lignes'] as $l) {
                    $rows[] = ['', $l['numero'], $l['reference'], $l['date_facture'], $l['date_reglement'], $l['montant_facture'], $l['montant_paye'], $l['total_rejet'], $l['total_perte'], $l['solde']];
                }
                $rows[] = ['', '', '', '', 'Sous-total', $bloc['total_facture'], $bloc['total_paye'], $bloc['total_rejet'], $bloc['total_perte'], $bloc['total_solde']];
            }
        }

        return \App\Support\ExcelExporter::download(
            ['Client', 'N°', 'Référence', 'Date Facture', 'Date Règlement', 'Mt Facture', 'Mt Payé', 'Total rejet', 'Total pertes', 'Solde'],
            $rows,
            'etat-reglements-clients',
            $titre,
        );
    }

    public function etatCreancesExcel(Request $request)
    {
        $result = $this->buildEtatCreancesData($request);
        $mode = $result['mode'];
        $titre = 'États Périodiques des Créances Clients';
        if (!empty($result['type_client_label'])) {
            $titre .= ' — ' . strtoupper($result['type_client_label']);
        }
        if (($result['periode']['debut'] ?? null) && ($result['periode']['fin'] ?? null)) {
            $titre .= ' du ' . \Carbon\Carbon::parse($result['periode']['debut'])->format('d/m/Y')
                . ' au ' . \Carbon\Carbon::parse($result['periode']['fin'])->format('d/m/Y');
        } elseif (!empty($result['periode']['fin'])) {
            $titre .= ' au ' . \Carbon\Carbon::parse($result['periode']['fin'])->format('d/m/Y');
        }

        if ($mode === 'tous_clients') {
            // Sans filtre de type, on insère des lignes-séparateurs par type.
            $rows = [];
            if (empty($result['type_client'])) {
                $sorted = collect($result['data'])
                    ->sortBy(fn($r) => ($r['type_client_label'] ?? 'zzz') . '|' . ($r['raison_sociale'] ?? ''))
                    ->values();
                $lastType = null;
                foreach ($sorted as $c) {
                    $label = $c['type_client_label'] ?? 'Non défini';
                    if ($label !== $lastType) {
                        $rows[] = [$label, '', '', '', '', '', '', ''];
                        $lastType = $label;
                    }
                    $rows[] = [$c['numero'] ?? '', $c['numero_compte'], $c['raison_sociale'], $c['total_facture'], $c['total_paye'], $c['total_rejet'], $c['total_perte'], $c['total_reste']];
                }
            } else {
                foreach ($result['data'] as $c) {
                    $rows[] = [$c['numero'] ?? '', $c['numero_compte'], $c['raison_sociale'], $c['total_facture'], $c['total_paye'], $c['total_rejet'], $c['total_perte'], $c['total_reste']];
                }
            }
            return \App\Support\ExcelExporter::download(
                ['N°', 'Compte', 'Raison sociale', 'Total Facture', 'Total Payé', 'Total rejet', 'Total pertes', 'Reste à payer'],
                $rows,
                'etat-creances-clients',
                $titre,
            );
        }

        // par_client / un_client. Si groupes par type disponibles, on intercale un en-tête de type.
        $rows = [];
        $renderGroupes = (!empty($result['groupes_par_type']))
            ? $result['groupes_par_type']
            : [['label' => null, 'clients' => $result['data']]];

        foreach ($renderGroupes as $groupe) {
            if (!empty($groupe['label'])) {
                $rows[] = ['=== ' . strtoupper($groupe['label']) . ' ===', '', '', '', '', '', '', ''];
            }
            foreach ($groupe['clients'] as $bloc) {
                $suffixe = ($mode === 'un_client' && !empty($bloc['type_client_label']))
                    ? ' - ' . $bloc['type_client_label']
                    : '';
                $rows[] = ['[' . $bloc['numero_compte'] . '] ' . $bloc['raison_sociale'] . $suffixe, '', '', '', '', '', '', ''];
                foreach ($bloc['lignes'] as $l) {
                    $rows[] = ['', $l['numero'], $l['reference'], $l['date_facture'], $l['montant_facture'], $l['montant_paye'], $l['total_rejet'], $l['total_perte'], $l['reste_a_payer']];
                }
                $rows[] = ['', '', '', 'Sous-total', $bloc['total_facture'], $bloc['total_paye'], $bloc['total_rejet'], $bloc['total_perte'], $bloc['total_reste']];
            }
        }

        return \App\Support\ExcelExporter::download(
            ['Client', 'N°', 'Référence', 'Date Facture', 'Mt Facture', 'Mt Payé', 'Total rejet', 'Total pertes', 'Reste à payer'],
            $rows,
            'etat-creances-clients',
            $titre,
        );
    }

    public function brouillardChequesExcel(Request $request)
    {
        $result = $this->buildBrouillardChequesData($request);
        $titre = 'Brouillard de Chèques';
        if (($result['periode']['debut'] ?? null) && ($result['periode']['fin'] ?? null)) {
            $titre .= ' du ' . \Carbon\Carbon::parse($result['periode']['debut'])->format('d/m/Y')
                . ' au ' . \Carbon\Carbon::parse($result['periode']['fin'])->format('d/m/Y');
        }

        $rows = array_map(fn($l) => [
            $l['date'],
            $l['nature'],
            $l['libelle'],
            $l['debit'] ?: '',
            $l['credit'] ?: '',
            $l['solde'],
        ], $result['data']);

        return \App\Support\ExcelExporter::download(
            ['Date', 'Nature', 'Libellé', 'Débit', 'Crédit', 'Solde'],
            $rows,
            'brouillard-cheques',
            $titre,
        );
    }

    public function imputationsComptablesExcel(Request $request)
    {
        $result = $this->buildImputationsComptablesData($request);
        $titre = 'Imputations Comptables Clients';
        if (($result['periode']['debut'] ?? null) && ($result['periode']['fin'] ?? null)) {
            $titre .= ' du ' . \Carbon\Carbon::parse($result['periode']['debut'])->format('d/m/Y')
                . ' au ' . \Carbon\Carbon::parse($result['periode']['fin'])->format('d/m/Y');
        }

        $rows = [];
        foreach ($result['groupes'] as $g) {
            $rows[] = ["Date d'imputation : {$g['date']}", '', '', ''];
            foreach ($g['lignes'] as $l) {
                $rows[] = [
                    $l['compte'],
                    $l['debit'] ?: '',
                    $l['credit'] ?: '',
                    $l['libelle'],
                ];
            }
            $rows[] = ['', '', '', ''];
        }

        return \App\Support\ExcelExporter::download(
            ['Compte', 'Débit', 'Crédit', 'Libellé'],
            $rows,
            'imputations-comptables',
            $titre,
        );
    }

    public function chiffreAffairesExcel(Request $request)
    {
        $result = $this->buildChiffreAffairesData($request);
        $mode = $result['mode'];
        $titre = "CHIFFRES D'AFFAIRES (CA)";

        if (in_array($mode, ['global_du', 'global_au', 'global_periode'])) {
            $d = $result['data'];
            $rows = [
                ['CA Théorique (factures)', $d['theorique'] ?? 0],
                ['CA Physique (règlements)', $d['physique'] ?? 0],
                ['Écart', $d['ecart'] ?? 0],
            ];
            return \App\Support\ExcelExporter::download(
                ['Indicateur', 'Montant'],
                $rows,
                'chiffre-affaires',
                $titre,
            );
        }

        // Mode par_client
        $d = $result['data'];
        if (empty($d)) {
            $rows = [['Aucune donnée']];
            return \App\Support\ExcelExporter::download(['Info'], $rows, 'chiffre-affaires', $titre);
        }
        $rows = [['Client', '[' . $d['numero_compte'] . '] ' . $d['raison_sociale'], '', '']];
        foreach ($d['lignes'] as $l) {
            $rows[] = [$l['numero'], $l['reference'], $l['date_facture'], $l['montant']];
        }
        $rows[] = ['', '', 'TOTAL CA', $d['total_ca']];

        return \App\Support\ExcelExporter::download(
            ['N°', 'Référence', 'Date Facture', 'Montant'],
            $rows,
            'chiffre-affaires-client',
            $titre . ' — ' . ($d['raison_sociale'] ?? ''),
        );
    }

    public function pertesRejetsExcel(Request $request)
    {
        $result = $this->buildPertesRejetsData($request);
        $titre = 'Pertes et Rejets';
        if (($result['periode']['debut'] ?? null) && ($result['periode']['fin'] ?? null)) {
            $titre .= ' du ' . \Carbon\Carbon::parse($result['periode']['debut'])->format('d/m/Y')
                . ' au ' . \Carbon\Carbon::parse($result['periode']['fin'])->format('d/m/Y');
        }

        $rows = array_map(fn($l) => [
            $l['date_reglement'],
            $l['client_code'] . ' — ' . ($l['client_nom'] ?? '-'),
            $l['facture_reference'] ?? '',
            $l['reference_cheque'] ?? '',
            $l['montant'],
            $l['observations'] ?? '',
        ], $result['data']);

        return \App\Support\ExcelExporter::download(
            ['Date', 'Client', 'Réf. Facture', 'N° Chèque', 'Montant', 'Observations'],
            $rows,
            'pertes-rejets',
            $titre,
        );
    }

}
