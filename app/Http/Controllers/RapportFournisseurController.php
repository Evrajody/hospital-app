<?php

namespace App\Http\Controllers;

use App\Models\ApprovisionnementBanque;
use App\Models\Banque;
use App\Models\CompteBancaire;
use App\Models\CompteComptable;
use App\Models\Fournisseur;
use App\Models\FactureFournisseur;
use App\Models\ReglementClient;
use App\Models\ReglementFournisseur;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RapportFournisseurController extends Controller
{
    // ==========================================
    // HELPERS
    // ==========================================

    private function getFournisseursList(): array
    {
        return Fournisseur::with('compteComptable')
            ->orderBy('nom')
            ->get()
            ->map(fn($f) => [
                'id' => $f->id,
                'code' => $f->compteComptable?->numero_compte ?? '-',
                'nom' => $f->nom,
            ])
            ->toArray();
    }

    private function getBanquesList(): array
    {
        return Banque::withCount('comptes')
            ->orderBy('nom')
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'nom' => $b->nom,
            ])
            ->toArray();
    }

    private function getComptesList(): array
    {
        $compteIds = Fournisseur::whereNotNull('compte_comptable_id')
            ->distinct()
            ->pluck('compte_comptable_id');

        return CompteComptable::whereIn('id', $compteIds)
            ->orderBy('numero_compte')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'numero_compte' => $c->numero_compte,
                'libelle' => $c->libelle,
            ])
            ->toArray();
    }

    // ==========================================
    // DATA BUILDERS
    // ==========================================

    private function buildMouvementFacturesData(Request $request): array
    {
        $fournisseurId = $request->input('fournisseur_id');
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');

        $lignes = [];
        $fournisseurInfo = null;

        if ($fournisseurId) {
            $fournisseur = Fournisseur::with('compteComptable')->find($fournisseurId);

            if ($fournisseur) {
                $fournisseurInfo = [
                    'id' => $fournisseur->id,
                    'code' => $fournisseur->compteComptable?->numero_compte ?? '-',
                    'nom' => $fournisseur->nom,
                ];

                $query = FactureFournisseur::where('fournisseur_id', $fournisseurId)
                    ->whereNotIn('statut', [FactureFournisseur::STATUT_ANNULEE])
                    ->orderBy('date');

                if ($dateDebut && $dateFin) {
                    $query->whereBetween('date', [$dateDebut, $dateFin]);
                }

                $factures = $query->get();

                $totalMontantFacture = 0;
                $totalAvoir = 0;
                $totalMontantMo = 0;
                $totalMontantAib = 0;
                $totalMontantDu = 0;
                $totalReglement = 0;
                $totalSolde = 0;

                foreach ($factures as $f) {
                    $montantFacture = (float) ($f->montant_ttc ?: $f->montant_facture);
                    $avoir = (float) $f->avoir;
                    $montantMo = (float) $f->montant_mo;
                    $tauxAib = (float) $f->taux;
                    $montantAib = (float) $f->montant_reduction;
                    $montantDu = (float) $f->montant_net;
                    $totalReg = (float) $f->montant_paye;
                    $solde = (float) $f->reste_a_payer;

                    $lignes[] = [
                        'numero_piece' => $f->numero_piece,
                        'date' => $f->date?->format('d/m/Y'),
                        'reference_facture' => $f->reference_facture,
                        'montant_facture' => $montantFacture,
                        'avoir' => $avoir,
                        'montant_mo' => $montantMo,
                        'taux_aib' => $tauxAib,
                        'montant_aib' => $montantAib,
                        'montant_du' => $montantDu,
                        'total_reglement' => $totalReg,
                        'solde' => $solde,
                    ];

                    $totalMontantFacture += $montantFacture;
                    $totalAvoir += $avoir;
                    $totalMontantMo += $montantMo;
                    $totalMontantAib += $montantAib;
                    $totalMontantDu += $montantDu;
                    $totalReglement += $totalReg;
                    $totalSolde += $solde;
                }
            }
        }

        return [
            'fournisseur' => $fournisseurInfo,
            'lignes' => $lignes,
            'totaux' => [
                'montant_facture' => $totalMontantFacture ?? 0,
                'avoir' => $totalAvoir ?? 0,
                'montant_mo' => $totalMontantMo ?? 0,
                'montant_aib' => $totalMontantAib ?? 0,
                'montant_du' => $totalMontantDu ?? 0,
                'total_reglement' => $totalReglement ?? 0,
                'solde' => $totalSolde ?? 0,
            ],
            'periode' => ['debut' => $dateDebut, 'fin' => $dateFin],
            'selectedFournisseurId' => $fournisseurId ? (int) $fournisseurId : null,
        ];
    }

    private function buildSituationFournisseursData(Request $request): array
    {
        $mode = $request->input('mode', 'tous');
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');
        $compteId = $request->input('compte_id');

        // Calculer le montant payé pour une facture à une date donnée
        $getReglementsPeriode = function (FactureFournisseur $facture) use ($dateFin) {
            $query = $facture->reglements()
                ->where('statut', '!=', ReglementFournisseur::STATUT_ANNULE);
            if ($dateFin) {
                $query->where('date_reglement', '<=', $dateFin);
            }
            return (float) $query->sum('montant');
        };

        // Filtre de base : factures émises dans la période
        $baseFilter = function ($q) use ($dateDebut, $dateFin) {
            $q->whereNotIn('statut', [FactureFournisseur::STATUT_ANNULEE]);
            if ($dateDebut) {
                $q->where('date', '>=', $dateDebut);
            }
            if ($dateFin) {
                $q->where('date', '<=', $dateFin);
            }
        };

        if ($mode === 'tous' || $mode === 'par_compte') {
            $query = Fournisseur::with('compteComptable')
                ->whereHas('factures', $baseFilter)
                ->orderBy('nom');

            if ($mode === 'par_compte' && $compteId) {
                $query->where('compte_comptable_id', $compteId);
            }

            $fournisseurs = $query->get();

            $data = [];
            $numero = 1;

            foreach ($fournisseurs as $f) {
                $factures = $f->factures()
                    ->whereNotIn('statut', [FactureFournisseur::STATUT_ANNULEE])
                    ->when($dateDebut, fn($q) => $q->where('date', '>=', $dateDebut))
                    ->when($dateFin, fn($q) => $q->where('date', '<=', $dateFin))
                    ->get();

                $montantDu = 0;
                $montantReglements = 0;

                foreach ($factures as $fact) {
                    $reglePeriode = $getReglementsPeriode($fact);
                    $restePeriode = (float) $fact->montant_net - $reglePeriode;
                    if ($restePeriode > 0.01) {
                        $montantDu += (float) $fact->montant_net;
                        $montantReglements += $reglePeriode;
                    }
                }

                $restantDu = $montantDu - $montantReglements;
                if ($restantDu <= 0.01) continue;

                $code = $f->compteComptable?->numero_compte ?? '-';

                $data[] = [
                    'numero' => $numero++,
                    'compte_comptable_id' => $f->compte_comptable_id,
                    'numero_compte' => $code,
                    'libelle_compte' => $f->compteComptable?->libelle ?? '',
                    'raison_sociale' => $f->nom,
                    'montant_du' => $montantDu,
                    'montant_reglements' => $montantReglements,
                    'restant_du' => $restantDu,
                ];
            }

            $grandTotal = [
                'montant_du' => collect($data)->sum('montant_du'),
                'montant_reglements' => collect($data)->sum('montant_reglements'),
                'restant_du' => collect($data)->sum('restant_du'),
            ];

            $compteInfo = null;
            if ($mode === 'par_compte' && $compteId) {
                $compte = CompteComptable::find($compteId);
                if ($compte) {
                    $compteInfo = [
                        'numero_compte' => $compte->numero_compte,
                        'libelle' => $compte->libelle,
                    ];
                }
            }

            return [
                'mode' => $mode,
                'data' => $data,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'grandTotal' => $grandTotal,
                'compte' => $compteInfo,
            ];
        }

        // mode === 'par_fournisseur'
        $fournisseurId = $request->input('fournisseur_id');
        $query = Fournisseur::with('compteComptable')
            ->whereHas('factures', $baseFilter)
            ->orderBy('nom');

        if ($fournisseurId) {
            $query->where('id', $fournisseurId);
        }

        $fournisseurs = $query->get();

        $data = [];
        $grandTotaux = [
            'montant_facture' => 0, 'avoir' => 0, 'montant_mo' => 0,
            'montant_aib' => 0, 'montant_du' => 0, 'total_reglement' => 0, 'solde' => 0,
        ];

        foreach ($fournisseurs as $f) {
            $factures = $f->factures()
                ->whereNotIn('statut', [FactureFournisseur::STATUT_ANNULEE])
                ->when($dateDebut, fn($q) => $q->where('date', '>=', $dateDebut))
                ->when($dateFin, fn($q) => $q->where('date', '<=', $dateFin))
                ->orderBy('date')
                ->get();

            $code = $f->compteComptable?->numero_compte ?? '-';
            $lignes = [];
            $totaux = array_fill_keys(array_keys($grandTotaux), 0);

            foreach ($factures as $fact) {
                $reglePeriode = $getReglementsPeriode($fact);
                $soldePeriode = (float) $fact->montant_net - $reglePeriode;

                if ($soldePeriode <= 0.01) continue;

                $row = [
                    'numero_piece' => $fact->numero_piece,
                    'date' => $fact->date?->format('d/m/Y'),
                    'reference_facture' => $fact->reference_facture,
                    'montant_facture' => (float) ($fact->montant_ttc ?: $fact->montant_facture),
                    'avoir' => (float) $fact->avoir,
                    'montant_mo' => (float) $fact->montant_mo,
                    'taux_aib' => (float) $fact->taux,
                    'montant_aib' => (float) $fact->montant_reduction,
                    'montant_du' => (float) $fact->montant_net,
                    'total_reglement' => $reglePeriode,
                    'solde' => $soldePeriode,
                ];
                $lignes[] = $row;

                $totaux['montant_facture'] += $row['montant_facture'];
                $totaux['avoir'] += $row['avoir'];
                $totaux['montant_mo'] += $row['montant_mo'];
                $totaux['montant_aib'] += $row['montant_aib'];
                $totaux['montant_du'] += $row['montant_du'];
                $totaux['total_reglement'] += $row['total_reglement'];
                $totaux['solde'] += $row['solde'];
            }

            if (empty($lignes)) continue;

            $data[] = [
                'fournisseur' => "[{$code}] {$f->nom}",
                'lignes' => $lignes,
                'totaux' => $totaux,
            ];

            foreach (array_keys($grandTotaux) as $key) {
                $grandTotaux[$key] += $totaux[$key];
            }
        }

        return [
            'mode' => $mode,
            'data' => $data,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'grandTotaux' => $grandTotaux,
        ];
    }

    private function buildFacturesRegleesData(Request $request): array
    {
        $mode = $request->input('mode', 'date');
        $date = $request->input('date');
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');
        $fournisseurId = $request->input('fournisseur_id');

        $emptyResult = [
            'mode' => $mode, 'titre' => '', 'date' => $date,
            'date_debut' => $dateDebut, 'date_fin' => $dateFin,
            'resume' => [], 'detail' => [],
            'grandTotaux' => [
                'montant_facture' => 0, 'avoir' => 0, 'montant_mo' => 0,
                'montant_aib' => 0, 'reg_periode' => 0, 'mt_total_reg' => 0,
            ],
        ];

        // Build reglement query based on mode
        $reglementQuery = ReglementFournisseur::where('statut', '!=', ReglementFournisseur::STATUT_ANNULE);

        if ($fournisseurId) {
            $reglementQuery->where('fournisseur_id', $fournisseurId);
        }

        if ($mode === 'date' && $date) {
            $reglementQuery->whereDate('date_reglement', $date);
            $titre = 'Etat des factures réglées du ' . Carbon::parse($date)->format('d/m/Y');
        } elseif ($mode === 'periode' && $dateDebut && $dateFin) {
            $reglementQuery->whereBetween('date_reglement', [$dateDebut, $dateFin]);
            $titre = 'Etat des factures réglées du ' . Carbon::parse($dateDebut)->format('d/m/Y') . ' au ' . Carbon::parse($dateFin)->format('d/m/Y');
        } else {
            return $emptyResult;
        }

        $reglements = $reglementQuery->get();

        if ($reglements->isEmpty()) {
            $emptyResult['titre'] = $titre ?? '';
            return $emptyResult;
        }

        // Group reglements by facture_id
        $reglementsByFacture = $reglements->groupBy('facture_id');
        $factureIds = $reglementsByFacture->keys()->toArray();

        // Load factures with fournisseur
        $factures = FactureFournisseur::whereIn('id', $factureIds)
            ->whereNotIn('statut', [FactureFournisseur::STATUT_ANNULEE])
            ->with('fournisseur.compteComptable')
            ->orderBy('fournisseur_id')
            ->orderBy('date')
            ->get();

        // Group by fournisseur
        $grouped = $factures->groupBy('fournisseur_id');

        $resume = [];
        $detail = [];
        $grandTotaux = [
            'montant_facture' => 0, 'avoir' => 0, 'montant_mo' => 0,
            'montant_aib' => 0, 'reg_periode' => 0, 'mt_total_reg' => 0,
        ];

        foreach ($grouped as $fournisseurId => $fFactures) {
            $fournisseur = $fFactures->first()->fournisseur;
            $code = $fournisseur?->compteComptable?->numero_compte ?? '-';
            $fournisseurLabel = "[{$code}] " . ($fournisseur?->nom ?? 'Inconnu');

            $lignes = [];
            $totauxFournisseur = array_fill_keys(array_keys($grandTotaux), 0);

            foreach ($fFactures as $fact) {
                $factReglements = $reglementsByFacture[$fact->id] ?? collect();
                $regPeriode = (float) $factReglements->sum('montant');
                $dateReg = $factReglements->sortByDesc('date_reglement')->first()?->date_reglement;

                $row = [
                    'numero_piece' => $fact->numero_piece,
                    'date' => $fact->date?->format('d/m/Y'),
                    'date_reglement' => $dateReg?->format('d/m/Y'),
                    'montant_facture' => (float) ($fact->montant_ttc ?: $fact->montant_facture),
                    'avoir' => (float) $fact->avoir,
                    'montant_mo' => (float) $fact->montant_mo,
                    'taux_aib' => (float) $fact->taux,
                    'montant_aib' => (float) $fact->montant_reduction,
                    'reg_periode' => $regPeriode,
                    'mt_total_reg' => (float) $fact->montant_paye,
                ];
                $lignes[] = $row;

                $totauxFournisseur['montant_facture'] += $row['montant_facture'];
                $totauxFournisseur['avoir'] += $row['avoir'];
                $totauxFournisseur['montant_mo'] += $row['montant_mo'];
                $totauxFournisseur['montant_aib'] += $row['montant_aib'];
                $totauxFournisseur['reg_periode'] += $row['reg_periode'];
                $totauxFournisseur['mt_total_reg'] += $row['mt_total_reg'];
            }

            $detail[] = [
                'fournisseur' => $fournisseurLabel,
                'lignes' => $lignes,
                'totaux' => $totauxFournisseur,
            ];

            $resume[] = [
                'fournisseur' => $fournisseurLabel,
                'total_montant_facture' => $totauxFournisseur['montant_facture'],
                'total_avoir' => $totauxFournisseur['avoir'],
                'total_montant_mo' => $totauxFournisseur['montant_mo'],
                'total_aib' => $totauxFournisseur['montant_aib'],
                'total_reg_periode' => $totauxFournisseur['reg_periode'],
                'total_mt_reg' => $totauxFournisseur['mt_total_reg'],
            ];

            foreach (array_keys($grandTotaux) as $key) {
                $grandTotaux[$key] += $totauxFournisseur[$key];
            }
        }

        return [
            'mode' => $mode,
            'titre' => $titre,
            'date' => $date,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'resume' => $resume,
            'detail' => $detail,
            'grandTotaux' => $grandTotaux,
        ];
    }

    private function buildDeclarationAibData(Request $request): array
    {
        $mode = $request->input('mode', 'mois_annee');
        $mois = $request->input('mois');
        $annee = $request->input('annee');
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');

        if ($mode === 'mois_annee' && $mois && $annee) {
            $dateDebut = Carbon::create($annee, $mois, 1)->startOfMonth()->format('Y-m-d');
            $dateFin = Carbon::create($annee, $mois, 1)->endOfMonth()->format('Y-m-d');
            $moisNoms = ['', 'JANVIER', 'FÉVRIER', 'MARS', 'AVRIL', 'MAI', 'JUIN', 'JUILLET', 'AOÛT', 'SEPTEMBRE', 'OCTOBRE', 'NOVEMBRE', 'DÉCEMBRE'];
            $titreDeclaration = 'DECLARATION AIB MOIS DE ' . ($moisNoms[(int) $mois] ?? '') . ' ' . $annee;
            $titreEtat = 'ETAT AIB MOIS DE ' . ($moisNoms[(int) $mois] ?? '') . ' ' . $annee;
        } elseif ($mode === 'periode' && $dateDebut && $dateFin) {
            $titreDeclaration = 'DECLARATION AIB DU ' . Carbon::parse($dateDebut)->format('d/m/Y') . ' AU ' . Carbon::parse($dateFin)->format('d/m/Y');
            $titreEtat = 'ETAT AIB DU ' . Carbon::parse($dateDebut)->format('d/m/Y') . ' AU ' . Carbon::parse($dateFin)->format('d/m/Y');
        } else {
            return [
                'mode' => $mode, 'titreDeclaration' => '', 'titreEtat' => '',
                'lignes' => [], 'lignesTfu' => [], 'totaux' => ['montant_mo' => 0, 'montant_aib' => 0],
                'parTaux' => [], 'montantTotal' => 0, 'montantEnLettres' => '',
                'dateDebut' => null, 'dateFin' => null, 'mois' => $mois, 'annee' => $annee,
            ];
        }

        // Règlements où l'AIB a été effectivement déduit dans la période
        $reglements = ReglementFournisseur::where('deduire_aib', true)
            ->where('statut', '!=', ReglementFournisseur::STATUT_ANNULE)
            ->where(function ($q) use ($dateDebut, $dateFin) {
                $q->whereBetween('date_aib', [$dateDebut, $dateFin])
                  ->orWhere(function ($q2) use ($dateDebut, $dateFin) {
                      $q2->whereNull('date_aib')
                          ->whereBetween('date_reglement', [$dateDebut, $dateFin]);
                  });
            })
            ->with('facture.fournisseur.compteComptable')
            ->orderBy('date_aib')
            ->get();

        // Grouper par facture (1 facture = 1 déclaration AIB)
        $reglementsByFacture = $reglements->groupBy('facture_id');

        // Lignes pour la Déclaration AIB
        $lignes = [];
        $totalMontantMo = 0;
        $totalMontantAib = 0;

        // Lignes pour Point avec TFU
        $lignesTfu = [];
        $numero = 1;

        // Grouper par taux pour le bordereau
        $parTaux = [];

        foreach ($reglementsByFacture as $factureId => $factureReglements) {
            $firstReglement = $factureReglements->sortBy('date_aib')->first();
            $f = $firstReglement->facture;

            if (!$f || $f->statut === FactureFournisseur::STATUT_ANNULEE) {
                continue;
            }

            $fournisseur = $f->fournisseur;
            $code = $fournisseur?->compteComptable?->numero_compte ?? '';
            $fournisseurLabel = $code ? "[{$code}] {$fournisseur->nom}" : ($fournisseur->nom ?? 'Inconnu');

            $montantMo = (float) $f->montant_mo;
            $tauxAib = (float) $f->taux;
            // Utiliser montant_aib_deduit du règlement, fallback sur facture pour données existantes
            $montantAib = (float) $firstReglement->montant_aib_deduit > 0
                ? (float) $firstReglement->montant_aib_deduit
                : (float) $f->montant_reduction;

            $dateAib = $firstReglement->date_aib ?? $firstReglement->date_reglement;

            $lignes[] = [
                'numero_piece' => $f->numero_piece,
                'date' => $dateAib?->format('d/m/Y'),
                'ifu' => $fournisseur?->ifu ?? '',
                'fournisseur' => $fournisseurLabel,
                'libelle' => $f->libelle,
                'montant_facture' => (float) ($f->montant_ttc ?: $f->montant_facture),
                'montant_mo' => $montantMo,
                'taux_aib' => $tauxAib,
                'montant_aib' => $montantAib,
            ];

            $lignesTfu[] = [
                'numero' => $numero++,
                'ifu' => $fournisseur?->ifu ?? '',
                'fournisseur' => $fournisseur?->nom ?? 'Inconnu',
                'adresse' => $f->libelle,
                'mt_prestation' => $montantMo,
                'taux_aib' => $tauxAib,
                'montant_aib' => $montantAib,
            ];

            $totalMontantMo += $montantMo;
            $totalMontantAib += $montantAib;

            // Grouper par taux
            $tauxKey = number_format($tauxAib, 1);
            if (!isset($parTaux[$tauxKey])) {
                $parTaux[$tauxKey] = ['taux' => $tauxAib, 'base' => 0, 'montant' => 0];
            }
            $parTaux[$tauxKey]['base'] += $montantMo;
            $parTaux[$tauxKey]['montant'] += $montantAib;
        }

        ksort($parTaux);

        return [
            'mode' => $mode,
            'titreDeclaration' => $titreDeclaration,
            'titreEtat' => $titreEtat,
            'lignes' => $lignes,
            'lignesTfu' => $lignesTfu,
            'totaux' => ['montant_mo' => $totalMontantMo, 'montant_aib' => $totalMontantAib],
            'parTaux' => array_values($parTaux),
            'montantTotal' => $totalMontantAib,
            'montantEnLettres' => $this->montantEnLettres((int) round($totalMontantAib)),
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'mois' => $mois,
            'annee' => $annee,
        ];
    }

    private function buildPointPeriodiqueData(Request $request): array
    {
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');

        if (!$dateDebut || !$dateFin) {
            return ['titre' => '', 'groupes' => [], 'dateDebut' => null, 'dateFin' => null];
        }

        $titre = 'ETAT DES PC DU ' . Carbon::parse($dateDebut)->format('d/m/Y') . ' au ' . Carbon::parse($dateFin)->format('d/m/Y');

        $factures = FactureFournisseur::whereNotIn('statut', [FactureFournisseur::STATUT_ANNULEE])
            ->whereBetween('date', [$dateDebut, $dateFin])
            ->orderBy('date')
            ->orderBy('numero_piece')
            ->get();

        // Grouper par date
        $grouped = $factures->groupBy(fn($f) => $f->date->format('Y-m-d'));

        $groupes = [];
        foreach ($grouped as $dateKey => $facturesJour) {
            $dateObj = Carbon::parse($dateKey);
            $lignes = [];
            $totalJour = 0;

            foreach ($facturesJour as $f) {
                $montant = (float) ($f->montant_ttc ?: $f->montant_facture);
                $lignes[] = [
                    'numero_piece' => $f->numero_piece,
                    'libelle' => $f->libelle,
                    'montant' => $montant,
                ];
                $totalJour += $montant;
            }

            $groupes[] = [
                'date' => $dateObj->format('d/m/Y'),
                'date_longue' => mb_strtoupper($dateObj->locale('fr')->translatedFormat('l j F Y')),
                'lignes' => $lignes,
                'total' => $totalJour,
            ];
        }

        return [
            'titre' => $titre,
            'groupes' => $groupes,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
        ];
    }

    private function buildSituationBanquesData(Request $request): array
    {
        $mode = $request->input('mode', 'toutes');
        $banqueId = $request->input('banque_id');
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');

        $empty = ['titre' => '', 'mode' => $mode, 'lignes' => [], 'sections' => [],
            'totalDebit' => 0, 'totalCredit' => 0, 'totalSolde' => 0,
            'dateAvant' => '', 'dateDebut' => null, 'dateFin' => null];

        if (!$dateDebut || !$dateFin) {
            return $empty;
        }

        $titre = 'SITUATION DES BANQUES DU ' . Carbon::parse($dateDebut)->format('d/m/Y') . ' AU ' . Carbon::parse($dateFin)->format('d/m/Y');
        $dateAvant = Carbon::parse($dateDebut)->subDay()->format('d/m/Y');

        // Get CompteBancaire records (linked to real Banque + CompteComptable OHADA)
        $cbQuery = CompteBancaire::with(['banque', 'compteOhada'])
            ->whereNotNull('compte_ohada_id');

        if ($mode === 'par_banque' && $banqueId) {
            $cbQuery->where('banque_id', $banqueId);
        }

        $compteBancaires = $cbQuery->orderBy('banque_id')->get();

        $lignes = [];
        $sections = [];
        $grandTotalDebit = 0;
        $grandTotalCredit = 0;
        $grandTotalSolde = 0;

        foreach ($compteBancaires as $cb) {
            $compteOhada = $cb->compteOhada;
            if (!$compteOhada) continue;

            $cbIds = collect([$cb->id]);

            // === PERIOD totals ===
            // Crédit = money out = supplier payments (via CompteComptable)
            $creditPeriode = (float) ReglementFournisseur::where('compte_tresorerie_id', $compteOhada->id)
                ->where('statut', '!=', ReglementFournisseur::STATUT_ANNULE)
                ->whereBetween('date_reglement', [$dateDebut, $dateFin])
                ->sum('montant');

            // Débit = money in (via CompteBancaire)
            $debitPeriode = 0;
            $debitPeriode += (float) ApprovisionnementBanque::where('compte_bancaire_id', $cb->id)
                ->whereBetween('date_depot', [$dateDebut, $dateFin])
                ->sum('montant');
            $debitPeriode += (float) ReglementClient::where('compte_bancaire_id', $cb->id)
                ->whereBetween('date_reglement', [$dateDebut, $dateFin])
                ->sum('montant');

            $soldePeriode = $debitPeriode - $creditPeriode;

            if ($debitPeriode == 0 && $creditPeriode == 0) {
                continue;
            }

            // Summary row
            $lignes[] = [
                'numero_compte' => $compteOhada->numero_compte,
                'intitule' => $cb->banque->nom ?? $compteOhada->libelle,
                'total_debit' => $debitPeriode,
                'total_credit' => $creditPeriode,
                'solde' => $soldePeriode,
            ];

            $grandTotalDebit += $debitPeriode;
            $grandTotalCredit += $creditPeriode;
            $grandTotalSolde += $soldePeriode;

            // Detail section (for "par_banque" mode)
            if ($mode === 'par_banque') {
                // === INITIAL BALANCE (before period) ===
                $creditAvant = (float) ReglementFournisseur::where('compte_tresorerie_id', $compteOhada->id)
                    ->where('statut', '!=', ReglementFournisseur::STATUT_ANNULE)
                    ->where('date_reglement', '<', $dateDebut)
                    ->sum('montant');

                $debitAvant = 0;
                $debitAvant += (float) ApprovisionnementBanque::where('compte_bancaire_id', $cb->id)
                    ->where('date_depot', '<', $dateDebut)
                    ->sum('montant');
                $debitAvant += (float) ReglementClient::where('compte_bancaire_id', $cb->id)
                    ->where('date_reglement', '<', $dateDebut)
                    ->sum('montant');

                $soldeInitial = $debitAvant - $creditAvant;

                // === TRANSACTIONS in period ===
                $transactions = [];

                // ReglementFournisseur (CREDIT - money out)
                $reglements = ReglementFournisseur::where('compte_tresorerie_id', $compteOhada->id)
                    ->where('statut', '!=', ReglementFournisseur::STATUT_ANNULE)
                    ->whereBetween('date_reglement', [$dateDebut, $dateFin])
                    ->with('fournisseur')
                    ->orderBy('date_reglement')
                    ->get();

                foreach ($reglements as $reg) {
                    $beneficiaire = $reg->beneficiaire ?? $reg->fournisseur?->nom ?? 'Inconnu';
                    $modeLabel = match($reg->mode_paiement) {
                        'virement' => 'Virement Bancaire',
                        'cheque' => 'Chèque',
                        'especes' => 'Espèces',
                        'mobile_money' => 'Mobile Money',
                        'carte' => 'Carte Bancaire',
                        default => ucfirst($reg->mode_paiement ?? ''),
                    };
                    $ref = $reg->reference ? "N°{$reg->reference}/" : '';

                    $transactions[] = [
                        'date_sort' => $reg->date_reglement->format('Y-m-d') . '_r' . $reg->id,
                        'date_fmt' => $reg->date_reglement->format('d/m/Y'),
                        'libelle' => "S/{$modeLabel} {$ref}{$beneficiaire}",
                        'debit' => 0,
                        'credit' => (float) $reg->montant,
                    ];
                }

                // ApprovisionnementBanque (DEBIT - money in)
                $appros = ApprovisionnementBanque::where('compte_bancaire_id', $cb->id)
                    ->whereBetween('date_depot', [$dateDebut, $dateFin])
                    ->orderBy('date_depot')
                    ->get();

                foreach ($appros as $appro) {
                    $transactions[] = [
                        'date_sort' => $appro->date_depot->format('Y-m-d') . '_a' . $appro->id,
                        'date_fmt' => $appro->date_depot->format('d/m/Y'),
                        'libelle' => 'Approvisionnement banque',
                        'debit' => (float) $appro->montant,
                        'credit' => 0,
                    ];
                }

                // ReglementClient (DEBIT - money in)
                $regClients = ReglementClient::where('compte_bancaire_id', $cb->id)
                    ->whereBetween('date_reglement', [$dateDebut, $dateFin])
                    ->with('client')
                    ->orderBy('date_reglement')
                    ->get();

                foreach ($regClients as $rc) {
                    $clientNom = $rc->client?->nom ?? $rc->institution ?? 'Client';
                    $ref = $rc->reference_cheque ? " N°{$rc->reference_cheque}" : '';

                    $transactions[] = [
                        'date_sort' => $rc->date_reglement->format('Y-m-d') . '_c' . $rc->id,
                        'date_fmt' => $rc->date_reglement->format('d/m/Y'),
                        'libelle' => "Encaissement{$ref}/{$clientNom}",
                        'debit' => (float) $rc->montant,
                        'credit' => 0,
                    ];
                }

                // Sort by date
                usort($transactions, fn($a, $b) => $a['date_sort'] <=> $b['date_sort']);

                // Compute running solde
                $runningSolde = $soldeInitial;
                foreach ($transactions as &$tx) {
                    $runningSolde += $tx['debit'] - $tx['credit'];
                    $tx['solde'] = $runningSolde;
                    unset($tx['date_sort']);
                }
                unset($tx);

                $sections[] = [
                    'numero_compte' => $compteOhada->numero_compte,
                    'intitule' => $cb->banque->nom ?? $compteOhada->libelle,
                    'solde_initial' => $soldeInitial,
                    'transactions' => $transactions,
                    'total_debit' => $debitPeriode,
                    'total_credit' => $creditPeriode,
                    'solde_periode' => $soldePeriode,
                ];
            }
        }

        return [
            'titre' => $titre,
            'mode' => $mode,
            'dateAvant' => $dateAvant,
            'lignes' => $lignes,
            'sections' => $sections,
            'totalDebit' => $grandTotalDebit,
            'totalCredit' => $grandTotalCredit,
            'totalSolde' => $grandTotalSolde,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
        ];
    }

    private function buildFacturesSoldesData(Request $request): array
    {
        $fournisseurId = $request->input('fournisseur_id');
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');

        $query = FactureFournisseur::with(['fournisseur.compteComptable'])
            ->where('statut', '!=', FactureFournisseur::STATUT_ANNULEE);

        if ($fournisseurId) {
            $query->where('fournisseur_id', $fournisseurId);
        }

        if ($dateDebut) {
            $query->where('date_facture_bc', '>=', $dateDebut);
        }
        if ($dateFin) {
            $query->where('date_facture_bc', '<=', $dateFin);
        }

        $factures = $query->orderBy('date_facture_bc')->get();

        $data = $factures->map(function ($f) {
            $code = $f->fournisseur?->compteComptable?->numero_compte;
            $nom = $f->fournisseur?->nom ?? '-';
            $fournisseurLabel = $code ? "[{$code}] {$nom}" : $nom;

            return [
                'id' => $f->id,
                'numero' => $f->numero_piece,
                'date_facture' => $f->date_facture_bc?->format('Y-m-d'),
                'fournisseur_label' => $fournisseurLabel,
                'montant_ttc' => (float) $f->montant_net,
                'montant_paye' => (float) $f->montant_paye,
                'reste_a_payer' => (float) $f->reste_a_payer,
            ];
        });

        $totaux = [
            'montant_ttc' => $data->sum('montant_ttc'),
            'montant_paye' => $data->sum('montant_paye'),
            'reste_a_payer' => $data->sum('reste_a_payer'),
        ];

        return [
            'factures' => $data->toArray(),
            'totaux' => $totaux,
        ];
    }

    private function montantEnLettres(int $nombre): string
    {
        if ($nombre === 0) return 'ZÉRO FRANC';

        $unites = ['', 'UN', 'DEUX', 'TROIS', 'QUATRE', 'CINQ', 'SIX', 'SEPT', 'HUIT', 'NEUF',
            'DIX', 'ONZE', 'DOUZE', 'TREIZE', 'QUATORZE', 'QUINZE', 'SEIZE', 'DIX-SEPT', 'DIX-HUIT', 'DIX-NEUF'];
        $dizaines = ['', 'DIX', 'VINGT', 'TRENTE', 'QUARANTE', 'CINQUANTE', 'SOIXANTE', 'SOIXANTE', 'QUATRE-VINGT', 'QUATRE-VINGT'];

        $convertir = function (int $n) use (&$convertir, $unites, $dizaines): string {
            if ($n === 0) return '';
            if ($n < 20) return $unites[$n];
            if ($n < 100) {
                $d = intdiv($n, 10);
                $u = $n % 10;
                if ($d === 7 || $d === 9) {
                    return $dizaines[$d] . '-' . $unites[$u + 10];
                }
                if ($u === 1 && $d !== 8) return $dizaines[$d] . ' ET UN';
                if ($u === 0 && $d === 8) return 'QUATRE-VINGTS';
                return $dizaines[$d] . ($u > 0 ? '-' . $unites[$u] : '');
            }
            if ($n < 1000) {
                $c = intdiv($n, 100);
                $reste = $n % 100;
                $prefix = $c === 1 ? 'CENT' : $unites[$c] . ' CENT';
                if ($reste === 0 && $c > 1) return $prefix . 'S';
                return $prefix . ($reste > 0 ? ' ' . $convertir($reste) : '');
            }
            if ($n < 1000000) {
                $milliers = intdiv($n, 1000);
                $reste = $n % 1000;
                $prefix = $milliers === 1 ? 'MILLE' : $convertir($milliers) . ' MILLE';
                return $prefix . ($reste > 0 ? ' ' . $convertir($reste) : '');
            }
            if ($n < 1000000000) {
                $millions = intdiv($n, 1000000);
                $reste = $n % 1000000;
                $prefix = $convertir($millions) . ($millions === 1 ? ' MILLION' : ' MILLIONS');
                return $prefix . ($reste > 0 ? ' ' . $convertir($reste) : '');
            }
            return (string) $n;
        };

        return $convertir($nombre) . ' FRANCS';
    }

    // ==========================================
    // INDEX PAGE
    // ==========================================

    public function index()
    {
        return Inertia::render('Rapports/Fournisseurs/Index', [
            'fournisseurs' => $this->getFournisseursList(),
            'comptes' => $this->getComptesList(),
        ]);
    }

    public function indexBanques()
    {
        return Inertia::render('Rapports/Banques/Index', [
            'banques' => $this->getBanquesList(),
        ]);
    }

    // ==========================================
    // JSON API ENDPOINTS
    // ==========================================

    public function mouvementFactures(Request $request): JsonResponse
    {
        return response()->json($this->buildMouvementFacturesData($request));
    }

    public function situationFournisseurs(Request $request): JsonResponse
    {
        return response()->json($this->buildSituationFournisseursData($request));
    }

    public function facturesReglees(Request $request): JsonResponse
    {
        return response()->json($this->buildFacturesRegleesData($request));
    }

    public function declarationAib(Request $request): JsonResponse
    {
        return response()->json($this->buildDeclarationAibData($request));
    }

    public function pointPeriodique(Request $request): JsonResponse
    {
        return response()->json($this->buildPointPeriodiqueData($request));
    }

    public function situationBanques(Request $request): JsonResponse
    {
        return response()->json($this->buildSituationBanquesData($request));
    }

    public function facturesSoldes(Request $request): JsonResponse
    {
        return response()->json($this->buildFacturesSoldesData($request));
    }

    // ==========================================
    // PDF EXPORT ENDPOINTS
    // ==========================================

    public function mouvementFacturesPdf(Request $request)
    {
        $result = $this->buildMouvementFacturesData($request);
        $result['titre'] = 'État des Mouvements Factures';
        $result['generatedAt'] = now()->format('d/m/Y à H:i');
        $result['generatedBy'] = auth()->user()?->name ?? 'Utilisateur';

        $pdf = Pdf::loadView('pdf.rapports-fournisseurs.mouvement-factures', $result);
        $pdf->setPaper('a4', 'landscape');

        return $request->query('action') === 'stream'
            ? $pdf->stream('mouvement-factures.pdf')
            : $pdf->download('mouvement-factures.pdf');
    }

    public function situationFournisseursPdf(Request $request)
    {
        $result = $this->buildSituationFournisseursData($request);
        $result['titre'] = $result['mode'] === 'par_fournisseur'
            ? 'Situation des fournisseurs (factures non réglées)'
            : 'Situation des fournisseurs (point des dettes)';
        $result['generatedAt'] = now()->format('d/m/Y à H:i');
        $result['generatedBy'] = auth()->user()?->name ?? 'Utilisateur';

        $orientation = $result['mode'] === 'par_fournisseur' ? 'landscape' : 'portrait';
        $pdf = Pdf::loadView('pdf.rapports-fournisseurs.situation-fournisseurs', $result);
        $pdf->setPaper('a4', $orientation);

        return $request->query('action') === 'stream'
            ? $pdf->stream('situation-fournisseurs.pdf')
            : $pdf->download('situation-fournisseurs.pdf');
    }

    public function facturesRegleesPdf(Request $request)
    {
        $result = $this->buildFacturesRegleesData($request);
        $result['generatedAt'] = now()->format('d/m/Y à H:i');
        $result['generatedBy'] = auth()->user()?->name ?? 'Utilisateur';

        $type = $request->input('type', 'resume');
        $view = $type === 'detail'
            ? 'pdf.rapports-fournisseurs.factures-reglees-detail'
            : 'pdf.rapports-fournisseurs.factures-reglees-resume';

        $filename = $type === 'detail' ? 'factures-reglees-detail.pdf' : 'factures-reglees-resume.pdf';

        $pdf = Pdf::loadView($view, $result);
        $pdf->setPaper('a4', 'landscape');

        return $request->query('action') === 'stream'
            ? $pdf->stream($filename)
            : $pdf->download($filename);
    }

    public function declarationAibPdf(Request $request)
    {
        $result = $this->buildDeclarationAibData($request);
        $result['generatedAt'] = now()->format('d/m/Y à H:i');
        $result['generatedBy'] = auth()->user()?->name ?? 'Utilisateur';
        $result['generatedAtLong'] = now()->locale('fr')->translatedFormat('l d F Y');

        $type = $request->input('type', 'declaration');
        $views = [
            'declaration' => 'pdf.rapports-fournisseurs.declaration-aib',
            'bordereau' => 'pdf.rapports-fournisseurs.bordereau-versement-aib',
            'tfu' => 'pdf.rapports-fournisseurs.etat-aib-tfu',
        ];
        $filenames = [
            'declaration' => 'declaration-aib.pdf',
            'bordereau' => 'bordereau-versement-aib.pdf',
            'tfu' => 'etat-aib-tfu.pdf',
        ];

        $view = $views[$type] ?? $views['declaration'];
        $filename = $filenames[$type] ?? $filenames['declaration'];
        $orientation = $type === 'bordereau' ? 'portrait' : 'landscape';

        $pdf = Pdf::loadView($view, $result);
        $pdf->setPaper('a4', $orientation);

        return $request->query('action') === 'stream'
            ? $pdf->stream($filename)
            : $pdf->download($filename);
    }

    public function pointPeriodiquePdf(Request $request)
    {
        $result = $this->buildPointPeriodiqueData($request);
        $result['generatedAt'] = now()->format('d/m/Y à H:i');
        $result['generatedBy'] = auth()->user()?->name ?? 'Utilisateur';

        $pdf = Pdf::loadView('pdf.rapports-fournisseurs.point-periodique-pc', $result);
        $pdf->setPaper('a4', 'portrait');

        return $request->query('action') === 'stream'
            ? $pdf->stream('point-periodique-pc.pdf')
            : $pdf->download('point-periodique-pc.pdf');
    }

    public function situationBanquesPdf(Request $request)
    {
        $result = $this->buildSituationBanquesData($request);
        $result['generatedAt'] = now()->format('d/m/Y à H:i');
        $result['generatedBy'] = auth()->user()?->name ?? 'Utilisateur';
        $result['generatedAtLong'] = now()->locale('fr')->translatedFormat('l d F Y');
        $result['etablissement'] = \App\Models\Setting::getEtablissement();

        $view = $result['mode'] === 'par_banque'
            ? 'pdf.rapports-fournisseurs.situation-banques-detail'
            : 'pdf.rapports-fournisseurs.situation-banques';

        $pdf = Pdf::loadView($view, $result);
        $pdf->setPaper('a4', 'portrait');

        return $request->query('action') === 'stream'
            ? $pdf->stream('situation-banques.pdf')
            : $pdf->download('situation-banques.pdf');
    }

    // ==========================================
    // STANDALONE PAGES
    // ==========================================

    public function mouvementFacturesPage(Request $request)
    {
        $result = $this->buildMouvementFacturesData($request);
        $result['fournisseurs'] = $this->getFournisseursList();
        return Inertia::render('Rapports/Fournisseurs/MouvementPeriodique', $result);
    }

    // ==========================================
    // RECAP CHARGES & INVESTISSEMENTS
    // ==========================================

    public function recapCharges(Request $request): JsonResponse
    {
        return response()->json($this->buildRecapData($request, 'charges'));
    }

    public function recapChargesPdf(Request $request)
    {
        $result = $this->buildRecapData($request, 'charges');
        $result['generatedAt'] = now()->format('d/m/Y à H:i');
        $result['generatedBy'] = auth()->user()?->name ?? 'Utilisateur';

        $pdf = Pdf::loadView('pdf.rapports-fournisseurs.recap-depenses', $result);
        $pdf->setPaper('a4', 'portrait');

        return $request->query('action') === 'stream'
            ? $pdf->stream('recap-charges.pdf')
            : $pdf->download('recap-charges.pdf');
    }

    public function recapInvestissements(Request $request): JsonResponse
    {
        return response()->json($this->buildRecapData($request, 'investissements'));
    }

    public function recapInvestissementsPdf(Request $request)
    {
        $result = $this->buildRecapData($request, 'investissements');
        $result['generatedAt'] = now()->format('d/m/Y à H:i');
        $result['generatedBy'] = auth()->user()?->name ?? 'Utilisateur';

        $pdf = Pdf::loadView('pdf.rapports-fournisseurs.recap-depenses', $result);
        $pdf->setPaper('a4', 'portrait');

        return $request->query('action') === 'stream'
            ? $pdf->stream('recap-investissements.pdf')
            : $pdf->download('recap-investissements.pdf');
    }

    private function buildRecapData(Request $request, string $type): array
    {
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');
        $mode = $request->input('mode', 'toutes');
        $compteId = $request->input('compte_id');

        // Construire la liste des comptes disponibles (toujours retournée)
        $comptesDisponibles = FactureFournisseur::whereNotIn('statut', [FactureFournisseur::STATUT_ANNULEE])
            ->whereNotNull('compte_id')
            ->whereHas('compte', function ($q) use ($type) {
                if ($type === 'charges') {
                    $q->where('numero_compte', 'LIKE', '6%')
                      ->orWhere('numero_compte', 'LIKE', '42%');
                } else {
                    $q->where('numero_compte', 'LIKE', '2%');
                }
            })
            ->with('compte')
            ->get()
            ->pluck('compte')
            ->unique('id')
            ->sortBy('numero_compte')
            ->map(fn($c) => [
                'id' => $c->id,
                'numero_compte' => $c->numero_compte,
                'libelle' => $c->libelle,
            ])
            ->values()
            ->toArray();

        if (!$dateDebut || !$dateFin) {
            return [
                'titre' => '',
                'lignes' => [],
                'totalDepenses' => 0,
                'comptesDisponibles' => $comptesDisponibles,
                'dateDebut' => null,
                'dateFin' => null,
            ];
        }

        $labelType = $type === 'charges' ? 'DEPENSES' : 'INVESTISSEMENTS';
        $titre = "POINT DES {$labelType} DU "
            . Carbon::parse($dateDebut)->format('d/m/Y')
            . ' au '
            . Carbon::parse($dateFin)->format('d/m/Y');

        // Requête de base : factures non annulées dans la période avec un compte d'imputation
        $query = FactureFournisseur::whereNotIn('statut', [FactureFournisseur::STATUT_ANNULEE])
            ->whereBetween('date', [$dateDebut, $dateFin])
            ->whereNotNull('compte_id')
            ->whereHas('compte', function ($q) use ($type) {
                if ($type === 'charges') {
                    $q->where('numero_compte', 'LIKE', '6%')
                      ->orWhere('numero_compte', 'LIKE', '42%');
                } else {
                    $q->where('numero_compte', 'LIKE', '2%');
                }
            });

        // Filtre par compte spécifique
        if ($mode === 'par_compte' && $compteId) {
            $query->where('compte_id', $compteId);
        }

        $factures = $query->with('compte')->get();

        // Grouper par compte et totaliser
        $parCompte = [];
        foreach ($factures as $f) {
            $compte = $f->compte;
            if (!$compte) continue;

            $key = $compte->numero_compte;
            if (!isset($parCompte[$key])) {
                $parCompte[$key] = [
                    'numero_compte' => $compte->numero_compte,
                    'libelle' => $compte->libelle,
                    'montant' => 0,
                ];
            }
            $parCompte[$key]['montant'] += (float) ($f->montant_ttc ?: $f->montant_facture);
        }

        ksort($parCompte);
        $lignes = array_values($parCompte);
        $totalDepenses = array_sum(array_column($lignes, 'montant'));

        return [
            'titre' => $titre,
            'labelTotal' => $type === 'charges' ? 'TOTAL DEPENSES' : 'TOTAL INVESTISSEMENTS',
            'lignes' => $lignes,
            'totalDepenses' => $totalDepenses,
            'comptesDisponibles' => $comptesDisponibles,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
        ];
    }

    // ==========================================
    // BORDEREAU DE TRANSMISSION
    // ==========================================

    public function bordereauTransmission(Request $request): JsonResponse
    {
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');

        $query = ReglementFournisseur::where('statut', '!=', ReglementFournisseur::STATUT_ANNULE);

        if ($dateDebut && $dateFin) {
            $query->whereBetween('date_reglement', [$dateDebut, $dateFin]);
        }

        $reglements = $query
            ->with(['facture.fournisseur.compteComptable'])
            ->orderBy('date_reglement')
            ->get()
            ->map(function ($r) {
                $fournisseur = $r->facture?->fournisseur;
                $code = $fournisseur?->compteComptable?->numero_compte ?? '';
                $fournisseurLabel = $code ? "[{$code}] {$fournisseur->nom}" : ($fournisseur->nom ?? 'Inconnu');

                $modeLabel = match($r->mode_paiement) {
                    'cheque' => 'Chèque N°' . ($r->reference ?: ''),
                    'virement' => 'Virement N°' . ($r->reference ?: ''),
                    'especes' => 'Espèces',
                    'mobile_money' => 'Mobile Money',
                    default => $r->mode_paiement,
                };

                return [
                    'id' => $r->id,
                    'fournisseur_label' => $fournisseurLabel,
                    'numero_piece' => $r->facture?->numero_piece ?? '',
                    'montant' => (float) $r->montant,
                    'reference' => $modeLabel,
                    'date_reglement_formatted' => $r->date_reglement?->format('d/m/Y'),
                    'institution' => $r->banque ?? '',
                    'beneficiaire' => $r->beneficiaire ?? '',
                    'mode_paiement' => $r->mode_paiement,
                ];
            });

        return response()->json(['reglements' => $reglements]);
    }

    public function bordereauTransmissionPdf(Request $request)
    {
        $ids = array_filter(explode(',', $request->input('ids', '')));
        if (empty($ids)) abort(400, 'Aucun règlement sélectionné');

        $reglements = ReglementFournisseur::whereIn('id', $ids)
            ->with(['facture.fournisseur.compteComptable'])
            ->orderBy('date_reglement')
            ->get();

        $lignes = $reglements->map(function ($r) {
            $fournisseur = $r->facture?->fournisseur;
            $code = $fournisseur?->compteComptable?->numero_compte ?? '';
            $fournisseurLabel = $code ? "[{$code}] {$fournisseur->nom}" : ($fournisseur->nom ?? 'Inconnu');

            return [
                'date' => $r->date_reglement?->format('d/m/Y'),
                'fournisseur' => $fournisseurLabel,
                'numero_piece' => $r->facture?->numero_piece ?? '',
                'mode_paiement' => match($r->mode_paiement) {
                    'cheque' => 'Chèque',
                    'virement' => 'Virement',
                    'especes' => 'Espèces',
                    'mobile_money' => 'Mobile Money',
                    default => $r->mode_paiement,
                },
                'institution' => $r->banque ?? '',
                'montant' => (float) $r->montant,
                'beneficiaire' => $r->beneficiaire ?? '',
            ];
        })->toArray();

        $data = [
            'titre' => 'BORDEREAU DE TRANSMISSION',
            'lignes' => $lignes,
            'generatedAt' => now()->format('d/m/Y à H:i'),
            'generatedBy' => auth()->user()?->name ?? 'Utilisateur',
        ];

        $pdf = Pdf::loadView('pdf.rapports-fournisseurs.bordereau-transmission', $data);
        $pdf->setPaper('a4', 'landscape');

        return $request->query('action') === 'stream'
            ? $pdf->stream('bordereau-transmission.pdf')
            : $pdf->download('bordereau-transmission.pdf');
    }

    public function mandatsMultiplesPdf(Request $request)
    {
        $ids = array_filter(explode(',', $request->input('ids', '')));
        if (empty($ids)) abort(400, 'Aucun règlement sélectionné');

        $reglements = ReglementFournisseur::whereIn('id', $ids)
            ->with(['facture.fournisseur', 'fournisseur'])
            ->orderBy('date_reglement')
            ->get();

        $etablissement = \App\Models\Setting::getEtablissement();
        $user = auth()->user();

        $mandats = $reglements->map(function ($reglement) use ($etablissement, $user) {
            $facture = $reglement->facture;
            $montantEnLettres = $this->montantEnLettres((int) round((float) $facture->montant_paye));
            $resteAPayerLettres = strtoupper($this->montantEnLettres((int) round((float) $facture->reste_a_payer)) . ' FRANCS');

            $modeLabel = match($reglement->mode_paiement) {
                'especes' => 'Espèces',
                'cheque' => 'Chèque',
                'virement' => 'Virement bancaire',
                'carte' => 'Carte bancaire',
                'mobile_money' => 'Mobile Money',
                default => $reglement->mode_paiement,
            };

            // Snapshot nom/directeur, live pour le reste
            $etabForMandat = array_merge($etablissement, [
                'nom' => $reglement->etablissement_nom ?: $etablissement['nom'],
                'directeur' => $reglement->etablissement_directeur ?: $etablissement['directeur'],
            ]);

            return [
                'reglement' => $reglement,
                'facture' => $facture,
                'modeLabel' => $modeLabel,
                'montantEnLettres' => $montantEnLettres,
                'resteAPayerLettres' => $resteAPayerLettres,
                'etablissement' => $etabForMandat,
                'user' => $user,
            ];
        })->toArray();

        $pdf = Pdf::loadView('pdf.rapports-fournisseurs.mandats-multiples', [
            'mandats' => $mandats,
        ]);
        $pdf->setPaper('a4', 'portrait');

        return $request->query('action') === 'stream'
            ? $pdf->stream('bordereaux-reglement.pdf')
            : $pdf->download('bordereaux-reglement.pdf');
    }

    // ==========================================
    // EXPORTS EXCEL
    // ==========================================

    public function declarationTvaExcel(Request $request)
    {
        $data = $this->buildDeclarationTvaData($request);
        $rows = array_map(fn($l) => [
            $l['date'],
            $l['numero_piece'],
            $l['libelle'],
            $l['montant_ttc'],
            $l['taux_tva'],
            $l['montant_tva'],
        ], $data['lignes']);
        $rows[] = ['', '', 'TOTAL', $data['totaux']['ttc'], '', $data['totaux']['tva']];

        return \App\Support\ExcelExporter::download(
            ['Date', 'N° PC', 'Libellé', 'Montant TTC', 'Taux TVA (%)', 'Montant TVA'],
            $rows,
            'declaration-tva',
            'Déclaration TVA du ' . ($data['dateDebut'] ? Carbon::parse($data['dateDebut'])->format('d/m/Y') : '-') . ' au ' . ($data['dateFin'] ? Carbon::parse($data['dateFin'])->format('d/m/Y') : '-'),
        );
    }

    public function declarationAibExcel(Request $request)
    {
        $data = $this->buildDeclarationAibData($request);
        $rows = array_map(fn($l) => [
            $l['numero_piece'],
            $l['date'],
            $l['ifu'] ?? '',
            $l['fournisseur'],
            $l['libelle'],
            $l['montant_facture'],
            $l['montant_mo'],
            $l['taux_aib'],
            $l['montant_aib'],
        ], $data['lignes']);

        return \App\Support\ExcelExporter::download(
            ['N° PC', 'Date AIB', 'IFU', 'Fournisseur', 'Libellé', 'Mt TTC', 'Mt M.O.', 'Taux AIB (%)', 'Montant AIB'],
            $rows,
            'declaration-aib',
            $data['titreDeclaration'] ?? 'Déclaration AIB',
        );
    }

    public function banquesParCompteExcel(Request $request)
    {
        $data = $this->buildEtatBanquesParCompteData($request);
        $rows = array_map(fn($l) => [
            $l['banque'] ?? '',
            $l['numero_compte'],
            $l['approvisionnements'],
            $l['debits'],
            $l['solde'],
        ], $data['data']);

        return \App\Support\ExcelExporter::download(
            ['Banque', 'N° Compte', 'Approvisionnements', 'Débits', 'Solde actuel'],
            $rows,
            'etat-banques-par-compte',
            'État banques par compte',
        );
    }

    public function mouvementFacturesExcel(Request $request)
    {
        $data = $this->buildMouvementFacturesData($request);
        $rows = array_map(fn($l) => [
            $l['numero_piece'],
            $l['date'],
            $l['reference_facture'] ?? '',
            $l['montant_facture'],
            $l['avoir'],
            $l['montant_mo'],
            $l['taux_aib'],
            $l['montant_aib'],
            $l['montant_du'],
            $l['total_reglement'],
            $l['solde'],
        ], $data['lignes']);

        $t = $data['totaux'];
        $rows[] = ['', 'TOTAL', '', $t['montant_facture'], $t['avoir'], $t['montant_mo'], '', $t['montant_aib'], $t['montant_du'], $t['total_reglement'], $t['solde']];

        $titre = 'Mouvement des factures';
        if ($data['fournisseur']) {
            $titre .= ' — [' . $data['fournisseur']['code'] . '] ' . $data['fournisseur']['nom'];
        }
        if ($data['periode']['debut'] && $data['periode']['fin']) {
            $titre .= ' du ' . Carbon::parse($data['periode']['debut'])->format('d/m/Y')
                . ' au ' . Carbon::parse($data['periode']['fin'])->format('d/m/Y');
        }

        return \App\Support\ExcelExporter::download(
            ['N° PC', 'Date', 'Référence', 'Mt Facture', 'Avoir', 'Mt M.O.', 'Taux AIB', 'Mt AIB', 'Mt Dû', 'Mt Réglé', 'Solde'],
            $rows,
            'mouvement-factures',
            $titre,
        );
    }

    public function situationFournisseursExcel(Request $request)
    {
        $data = $this->buildSituationFournisseursData($request);
        $mode = $data['mode'] ?? 'tous';
        $titre = 'Situation fournisseurs';
        if (($data['date_debut'] ?? null) && ($data['date_fin'] ?? null)) {
            $titre .= ' du ' . Carbon::parse($data['date_debut'])->format('d/m/Y')
                . ' au ' . Carbon::parse($data['date_fin'])->format('d/m/Y');
        }

        if (in_array($mode, ['tous', 'par_compte'])) {
            $rows = array_map(fn($l) => [
                $l['numero'] ?? '',
                $l['numero_compte'],
                $l['libelle_compte'] ?? '',
                $l['raison_sociale'],
                $l['montant_du'],
                $l['montant_reglements'],
                $l['restant_du'],
            ], $data['data']);
            $g = $data['grandTotal'];
            $rows[] = ['', '', '', 'TOTAL', $g['montant_du'], $g['montant_reglements'], $g['restant_du']];

            return \App\Support\ExcelExporter::download(
                ['N°', 'Compte', 'Libellé compte', 'Raison sociale', 'Montant dû', 'Règlements', 'Restant dû'],
                $rows,
                'situation-fournisseurs',
                $titre,
            );
        }

        // Mode par_fournisseur : aplatir avec en-têtes par fournisseur
        $rows = [];
        foreach ($data['data'] as $bloc) {
            $rows[] = [$bloc['fournisseur'], '', '', '', '', '', '', '', '', '', ''];
            foreach ($bloc['lignes'] as $l) {
                $rows[] = [
                    '',
                    $l['numero_piece'],
                    $l['date'],
                    $l['reference_facture'] ?? '',
                    $l['montant_facture'],
                    $l['avoir'],
                    $l['montant_mo'],
                    $l['taux_aib'],
                    $l['montant_aib'],
                    $l['total_reglement'],
                    $l['solde'],
                ];
            }
            $t = $bloc['totaux'];
            $rows[] = ['', '', '', 'Sous-total', $t['montant_facture'], $t['avoir'], $t['montant_mo'], '', $t['montant_aib'], $t['total_reglement'], $t['solde']];
        }
        $g = $data['grandTotaux'] ?? [];
        if (!empty($g)) {
            $rows[] = ['', '', '', 'TOTAL GÉNÉRAL', $g['montant_facture'] ?? 0, $g['avoir'] ?? 0, $g['montant_mo'] ?? 0, '', $g['montant_aib'] ?? 0, $g['total_reglement'] ?? 0, $g['solde'] ?? 0];
        }

        return \App\Support\ExcelExporter::download(
            ['Fournisseur', 'N° PC', 'Date', 'Référence', 'Mt Facture', 'Avoir', 'Mt M.O.', 'Taux AIB', 'Mt AIB', 'Mt Réglé', 'Solde'],
            $rows,
            'situation-fournisseurs-detail',
            $titre,
        );
    }

    public function facturesRegleesExcel(Request $request)
    {
        $data = $this->buildFacturesRegleesData($request);
        $rows = [];

        foreach ($data['detail'] as $bloc) {
            $rows[] = [$bloc['fournisseur'], '', '', '', '', '', '', '', '', ''];
            foreach ($bloc['lignes'] as $l) {
                $rows[] = [
                    '',
                    $l['numero_piece'],
                    $l['date'],
                    $l['date_reglement'],
                    $l['montant_facture'],
                    $l['avoir'],
                    $l['montant_mo'],
                    $l['taux_aib'],
                    $l['montant_aib'],
                    $l['reg_periode'],
                    $l['mt_total_reg'],
                ];
            }
            $t = $bloc['totaux'];
            $rows[] = ['', '', '', 'Sous-total', $t['montant_facture'], $t['avoir'], $t['montant_mo'], '', $t['montant_aib'], $t['reg_periode'], $t['mt_total_reg']];
        }
        $g = $data['grandTotaux'];
        $rows[] = ['', '', '', 'TOTAL GÉNÉRAL', $g['montant_facture'], $g['avoir'], $g['montant_mo'], '', $g['montant_aib'], $g['reg_periode'], $g['mt_total_reg']];

        return \App\Support\ExcelExporter::download(
            ['Fournisseur', 'N° PC', 'Date Fact.', 'Date Règl.', 'Mt Facture', 'Avoir', 'Mt M.O.', 'Taux AIB', 'Mt AIB', 'Règl. période', 'Mt Total Règ.'],
            $rows,
            'factures-reglees',
            $data['titre'] ?: 'Etat des factures réglées',
        );
    }

    public function pointPeriodiqueExcel(Request $request)
    {
        $data = $this->buildPointPeriodiqueData($request);
        $rows = [];
        $totalGeneral = 0;

        foreach ($data['groupes'] as $g) {
            $rows[] = [$g['date_longue'], '', ''];
            foreach ($g['lignes'] as $l) {
                $rows[] = ['', $l['numero_piece'], $l['libelle'], $l['montant']];
                $totalGeneral += $l['montant'];
            }
            $rows[] = ['', '', 'Total ' . $g['date'], $g['total']];
        }
        $rows[] = ['', '', 'TOTAL GÉNÉRAL', $totalGeneral];

        return \App\Support\ExcelExporter::download(
            ['Date', 'N° PC', 'Libellé', 'Montant'],
            $rows,
            'point-periodique-pc',
            $data['titre'] ?: 'Point périodique des PC',
        );
    }

    public function situationBanquesExcel(Request $request)
    {
        $data = $this->buildSituationBanquesData($request);
        $rows = [];

        if (!empty($data['sections'])) {
            // Mode par_banque : un bloc par compte avec transactions détaillées
            foreach ($data['sections'] as $section) {
                $rows[] = [$section['numero_compte'] . ' — ' . $section['intitule'], '', '', '', ''];
                $rows[] = ['Solde initial', '', '', '', $section['solde_initial']];
                foreach ($section['transactions'] as $tx) {
                    $rows[] = [$tx['date_fmt'], $tx['libelle'], $tx['debit'] ?: '', $tx['credit'] ?: '', $tx['solde']];
                }
                $rows[] = ['', 'Total période', $section['total_debit'], $section['total_credit'], $section['solde_periode']];
                $rows[] = ['', '', '', '', ''];
            }
            $rows[] = ['TOTAL GÉNÉRAL', '', $data['totalDebit'], $data['totalCredit'], $data['totalSolde']];

            return \App\Support\ExcelExporter::download(
                ['Date', 'Libellé', 'Débit', 'Crédit', 'Solde'],
                $rows,
                'situation-banques-detail',
                $data['titre'] ?: 'Situation des banques',
            );
        }

        // Mode résumé : 1 ligne par compte
        $rows = array_map(fn($l) => [
            $l['numero_compte'],
            $l['intitule'],
            $l['total_debit'],
            $l['total_credit'],
            $l['solde'],
        ], $data['lignes']);
        $rows[] = ['', 'TOTAL', $data['totalDebit'], $data['totalCredit'], $data['totalSolde']];

        return \App\Support\ExcelExporter::download(
            ['N° Compte', 'Intitulé', 'Total Débit', 'Total Crédit', 'Solde'],
            $rows,
            'situation-banques',
            $data['titre'] ?: 'Situation des banques',
        );
    }

    public function bordereauTransmissionExcel(Request $request)
    {
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');
        $ids = array_filter(explode(',', (string) $request->input('ids', '')));

        $query = ReglementFournisseur::where('statut', '!=', ReglementFournisseur::STATUT_ANNULE);
        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        } elseif ($dateDebut && $dateFin) {
            $query->whereBetween('date_reglement', [$dateDebut, $dateFin]);
        }

        $reglements = $query->with(['facture.fournisseur.compteComptable'])->orderBy('date_reglement')->get();
        $rows = [];
        $total = 0;

        foreach ($reglements as $r) {
            $fournisseur = $r->facture?->fournisseur;
            $code = $fournisseur?->compteComptable?->numero_compte ?? '';
            $fournisseurLabel = $code ? "[{$code}] {$fournisseur->nom}" : ($fournisseur->nom ?? 'Inconnu');
            $modeLabel = match($r->mode_paiement) {
                'cheque' => 'Chèque',
                'virement' => 'Virement',
                'especes' => 'Espèces',
                'mobile_money' => 'Mobile Money',
                default => $r->mode_paiement,
            };
            $rows[] = [
                $r->date_reglement?->format('d/m/Y'),
                $fournisseurLabel,
                $r->facture?->numero_piece ?? '',
                $modeLabel . ($r->reference ? ' N°' . $r->reference : ''),
                $r->banque ?? '',
                $r->beneficiaire ?? '',
                (float) $r->montant,
            ];
            $total += (float) $r->montant;
        }
        $rows[] = ['', '', '', '', '', 'TOTAL', $total];

        return \App\Support\ExcelExporter::download(
            ['Date', 'Fournisseur', 'N° PC', 'Mode', 'Banque', 'Bénéficiaire', 'Montant'],
            $rows,
            'bordereau-transmission',
            'Bordereau de transmission',
        );
    }

    public function recapChargesExcel(Request $request)
    {
        return $this->buildRecapExcel($request, 'charges');
    }

    public function recapInvestissementsExcel(Request $request)
    {
        return $this->buildRecapExcel($request, 'immo');
    }

    private function buildRecapExcel(Request $request, string $type)
    {
        $data = $this->buildRecapData($request, $type);
        $rows = array_map(fn($l) => [
            $l['numero_compte'],
            $l['libelle'],
            $l['montant'],
        ], $data['lignes']);
        $rows[] = ['', $data['labelTotal'] ?? 'TOTAL', $data['totalDepenses']];

        return \App\Support\ExcelExporter::download(
            ['N° Compte', 'Libellé', 'Montant'],
            $rows,
            $type === 'charges' ? 'recap-charges' : 'recap-investissements',
            $data['titre'] ?: ($type === 'charges' ? 'Récapitulatif des charges' : 'Récapitulatif des investissements'),
        );
    }

    public function facturesSoldesExcel(Request $request)
    {
        $data = $this->buildFacturesSoldesData($request);
        $rows = array_map(fn($f) => [
            $f['numero'],
            $f['date_facture'],
            $f['fournisseur_label'],
            $f['montant_ttc'],
            $f['montant_paye'],
            $f['reste_a_payer'],
        ], $data['factures']);
        $t = $data['totaux'];
        $rows[] = ['', '', 'TOTAL', $t['montant_ttc'], $t['montant_paye'], $t['reste_a_payer']];

        return \App\Support\ExcelExporter::download(
            ['N° PC', 'Date', 'Fournisseur', 'Mt TTC', 'Mt Payé', 'Reste à payer'],
            $rows,
            'factures-soldes',
            'Factures et soldes',
        );
    }

    // ==========================================
    // DÉCLARATION TVA
    // ==========================================

    private function buildDeclarationTvaData(Request $request): array
    {
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');

        if (!$dateDebut || !$dateFin) {
            return [
                'lignes' => [],
                'totaux' => ['ttc' => 0, 'tva' => 0, 'ht' => 0],
                'dateDebut' => null,
                'dateFin' => null,
            ];
        }

        $factures = FactureFournisseur::whereNotIn('statut', [FactureFournisseur::STATUT_ANNULEE])
            ->where('assujetti_tva', true)
            ->whereBetween('date', [$dateDebut, $dateFin])
            ->orderBy('date')
            ->orderBy('numero_piece')
            ->get();

        $lignes = [];
        $totaux = ['ttc' => 0, 'tva' => 0, 'ht' => 0];

        foreach ($factures as $f) {
            $ttc = (float) ($f->montant_ttc ?: $f->montant_facture);
            $tauxTva = (float) $f->taux_tva;
            $tva = (float) $f->montant_tva;
            $ht = (float) ($f->montant_ht ?: $f->montant_facture);

            $lignes[] = [
                'date' => $f->date?->format('d/m/Y'),
                'numero_piece' => $f->numero_piece,
                'libelle' => $f->libelle,
                'montant_ttc' => $ttc,
                'taux_tva' => $tauxTva,
                'montant_tva' => $tva,
                'montant_ht' => $ht,
            ];

            $totaux['ttc'] += $ttc;
            $totaux['tva'] += $tva;
            $totaux['ht'] += $ht;
        }

        return [
            'lignes' => $lignes,
            'totaux' => $totaux,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
        ];
    }

    public function declarationTva(Request $request): JsonResponse
    {
        return response()->json($this->buildDeclarationTvaData($request));
    }

    public function declarationTvaPdf(Request $request)
    {
        $data = $this->buildDeclarationTvaData($request);
        $pdf = Pdf::loadView('pdf.rapports-fournisseurs.declaration-tva', array_merge($data, [
            'titre' => 'DÉCLARATION TVA du ' . ($data['dateDebut'] ? Carbon::parse($data['dateDebut'])->format('d/m/Y') : '-') . ' au ' . ($data['dateFin'] ? Carbon::parse($data['dateFin'])->format('d/m/Y') : '-'),
            'etablissement' => \App\Models\Setting::getEtablissement(),
        ]));
        $pdf->setPaper('a4', 'landscape');

        return $request->query('action') === 'stream'
            ? $pdf->stream('declaration-tva.pdf')
            : $pdf->download('declaration-tva.pdf');
    }

    // ==========================================
    // ÉTAT BANQUES PAR COMPTE
    // ==========================================

    private function buildEtatBanquesParCompteData(Request $request): array
    {
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin') ?: now()->toDateString();

        $comptes = CompteBancaire::with('banque')->orderBy('numero_compte')->get();
        $data = [];

        foreach ($comptes as $c) {
            $approvisionnements = (float) ApprovisionnementBanque::where('compte_bancaire_id', $c->id)
                ->when($dateDebut, fn($q) => $q->where('date', '>=', $dateDebut))
                ->when($dateFin, fn($q) => $q->where('date', '<=', $dateFin))
                ->sum('montant');

            $debits = (float) ReglementFournisseur::where('compte_bancaire_id', $c->id)
                ->where('statut', '!=', ReglementFournisseur::STATUT_ANNULE)
                ->when($dateDebut, fn($q) => $q->where('date_reglement', '>=', $dateDebut))
                ->when($dateFin, fn($q) => $q->where('date_reglement', '<=', $dateFin))
                ->sum('montant');

            $data[] = [
                'compte_id' => $c->id,
                'numero_compte' => $c->numero_compte,
                'banque' => $c->banque?->nom,
                'approvisionnements' => $approvisionnements,
                'debits' => $debits,
                'solde' => (float) $c->solde,
            ];
        }

        return [
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'data' => $data,
        ];
    }

    public function etatBanquesParCompte(Request $request): JsonResponse
    {
        return response()->json($this->buildEtatBanquesParCompteData($request));
    }

    public function etatBanquesParComptePdf(Request $request)
    {
        $data = $this->buildEtatBanquesParCompteData($request);
        $periode = '';
        if ($data['date_debut'] || $data['date_fin']) {
            $periode = ' du ' . ($data['date_debut'] ? Carbon::parse($data['date_debut'])->format('d/m/Y') : '-')
                . ' au ' . ($data['date_fin'] ? Carbon::parse($data['date_fin'])->format('d/m/Y') : '-');
        }
        $pdf = Pdf::loadView('pdf.rapports-fournisseurs.banques-par-compte', array_merge($data, [
            'titre' => 'ÉTAT DES BANQUES PAR COMPTE' . $periode,
            'etablissement' => \App\Models\Setting::getEtablissement(),
        ]));
        $pdf->setPaper('a4', 'landscape');

        return $request->query('action') === 'stream'
            ? $pdf->stream('banques-par-compte.pdf')
            : $pdf->download('banques-par-compte.pdf');
    }
}
