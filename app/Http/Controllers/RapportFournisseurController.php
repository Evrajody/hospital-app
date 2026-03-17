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
                    $montantFacture = (float) $f->montant_facture;
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
        $dateRef = $request->input('date');
        $compteId = $request->input('compte_id');

        $baseFilter = function ($q) use ($dateRef) {
            $q->whereNotIn('statut', [FactureFournisseur::STATUT_ANNULEE])
              ->where('reste_a_payer', '>', 0);
            if ($dateRef) {
                $q->where('date', '<=', $dateRef);
            }
        };

        if ($mode === 'tous' || $mode === 'par_compte') {
            $query = Fournisseur::with('compteComptable')
                ->whereHas('factures', $baseFilter)
                ->orderBy('nom');

            // par_compte : filtrer par le compte sélectionné
            if ($mode === 'par_compte' && $compteId) {
                $query->where('compte_comptable_id', $compteId);
            }

            $fournisseurs = $query->get();

            $data = [];
            $numero = 1;

            foreach ($fournisseurs as $f) {
                $factures = $f->factures()
                    ->whereNotIn('statut', [FactureFournisseur::STATUT_ANNULEE])
                    ->where('reste_a_payer', '>', 0)
                    ->when($dateRef, fn($q) => $q->where('date', '<=', $dateRef))
                    ->get();

                $code = $f->compteComptable?->numero_compte ?? '-';

                $data[] = [
                    'numero' => $numero++,
                    'compte_comptable_id' => $f->compte_comptable_id,
                    'numero_compte' => $code,
                    'libelle_compte' => $f->compteComptable?->libelle ?? '',
                    'raison_sociale' => $f->nom,
                    'montant_du' => (float) $factures->sum('montant_net'),
                    'montant_reglements' => (float) $factures->sum('montant_paye'),
                    'restant_du' => (float) $factures->sum('reste_a_payer'),
                ];
            }

            $grandTotal = [
                'montant_du' => collect($data)->sum('montant_du'),
                'montant_reglements' => collect($data)->sum('montant_reglements'),
                'restant_du' => collect($data)->sum('restant_du'),
            ];

            // Pour par_compte, inclure les infos du compte sélectionné
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
                'date' => $dateRef,
                'grandTotal' => $grandTotal,
                'compte' => $compteInfo,
            ];
        }

        // mode === 'par_fournisseur' : un seul fournisseur sélectionné
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
                ->where('reste_a_payer', '>', 0)
                ->when($dateRef, fn($q) => $q->where('date', '<=', $dateRef))
                ->orderBy('date')
                ->get();

            $code = $f->compteComptable?->numero_compte ?? '-';
            $lignes = [];
            $totaux = array_fill_keys(array_keys($grandTotaux), 0);

            foreach ($factures as $fact) {
                $row = [
                    'numero_piece' => $fact->numero_piece,
                    'date' => $fact->date?->format('d/m/Y'),
                    'reference_facture' => $fact->reference_facture,
                    'montant_facture' => (float) $fact->montant_facture,
                    'avoir' => (float) $fact->avoir,
                    'montant_mo' => (float) $fact->montant_mo,
                    'taux_aib' => (float) $fact->taux,
                    'montant_aib' => (float) $fact->montant_reduction,
                    'montant_du' => (float) $fact->montant_net,
                    'total_reglement' => (float) $fact->montant_paye,
                    'solde' => (float) $fact->reste_a_payer,
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
            'date' => $dateRef,
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
                    'montant_facture' => (float) $fact->montant_facture,
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
                'fournisseur' => $fournisseurLabel,
                'libelle' => $f->libelle,
                'montant_facture' => (float) $f->montant_facture,
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
                $montant = (float) $f->montant_facture;
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
}
