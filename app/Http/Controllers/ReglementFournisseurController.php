<?php

namespace App\Http\Controllers;

use App\Models\ReglementFournisseur;
use App\Models\FactureFournisseur;
use App\Models\Fournisseur;
use App\Models\CompteComptable;
use App\Models\CompteBancaire;
use App\Models\Banque;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ReglementFournisseurController extends Controller
{
    /**
     * Afficher la liste des règlements (Vue Inertia)
     */
    public function indexView(Request $request): InertiaResponse
    {
        $query = ReglementFournisseur::with(['facture', 'fournisseur', 'createur']);

        // Filtre par fournisseur
        if ($request->filled('fournisseur_id')) {
            $query->where('fournisseur_id', $request->fournisseur_id);
        }

        // Filtre par mode de paiement
        if ($request->filled('mode_paiement')) {
            $query->where('mode_paiement', $request->mode_paiement);
        }

        // Filtre par période
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $query->periode($request->date_debut, $request->date_fin);
        }

        // Recherche
        if ($request->filled('search')) {
            $query->recherche($request->search);
        }

        // Tri
        $sortField = $request->input('sort', 'date_reglement');
        $sortOrder = $request->input('order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 20);
        $reglementsPaginated = $query->paginate($perPage);

        // Formater les règlements
        $reglements = $reglementsPaginated->map(fn($r) => $r->toApiArray());

        // Fournisseurs pour les filtres
        $fournisseurs = Fournisseur::select('id', 'nom')    
            ->orderBy('nom')
            ->get()
            ->map(fn($f) => ['id' => $f->id, 'nom' => $f->nom]);

        // Statistiques
        $stats = ReglementFournisseur::getStatistiques();

        // Factures impayées ou partiellement payées (utilise le scope nonPayee)
        $facturesImpayees = FactureFournisseur::with('fournisseur')
            // ->nonPayee()
            ->orderBy('date', 'desc')
            ->get()
            ->map(fn($f) => [
                'id' => $f->id,
                'numero' => $f->numero_piece ?? $f->numero,
                'libelle' => $f->libelle,
                'date' => $f->date,
                'fournisseur' => $f->fournisseur ? ['id' => $f->fournisseur->id, 'nom' => $f->fournisseur->nom] : null,
                'montant_ttc' => $f->montant_ttc,
                'montant_paye' => $f->montant_paye,
                'reste_a_payer' => $f->reste_a_payer ?? ($f->montant_ttc - $f->montant_paye),
                'statut' => $f->statut,
            ]);

        return Inertia::render('ReglementsFournisseurs/Index', [
            'reglements' => $reglements,
            'fournisseurs' => $fournisseurs,
            'facturesImpayees' => $facturesImpayees,
            'stats' => $stats,
            'pagination' => [
                'current_page' => $reglementsPaginated->currentPage(),
                'per_page' => $reglementsPaginated->perPage(),
                'total' => $reglementsPaginated->total(),
            ],
            'user' => [
                'name' => auth()->user()?->name ?? 'Utilisateur',
                'email' => auth()->user()?->email ?? 'user@hospital.bj',
            ],
        ]);
    }

    /**
     * Lister les règlements (API)
     */
    public function index(Request $request): JsonResponse
    {
        $query = ReglementFournisseur::with(['facture', 'fournisseur']);

        // Filtre par facture
        if ($request->filled('facture_id')) {
            $query->where('facture_id', $request->facture_id);
        }

        // Filtre par fournisseur
        if ($request->filled('fournisseur_id')) {
            $query->where('fournisseur_id', $request->fournisseur_id);
        }

        // Filtre par statut
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        // Tri
        $sortField = $request->input('sort', 'date_reglement');
        $sortOrder = $request->input('order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 20);
        $reglements = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $reglements->map(fn($r) => $r->toApiArray()),
            'pagination' => [
                'current_page' => $reglements->currentPage(),
                'per_page' => $reglements->perPage(),
                'total' => $reglements->total(),
                'last_page' => $reglements->lastPage(),
            ],
        ]);
    }

    /**
     * Afficher un règlement (API)
     */
    public function show(int $id): JsonResponse
    {
        $reglement = ReglementFournisseur::with(['facture', 'fournisseur', 'compteTresorerie', 'createur'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $reglement->toApiArray(),
        ]);
    }

    /**
     * Créer un règlement (API)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->validationRules());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Vérifier que la facture peut être payée
        $facture = FactureFournisseur::findOrFail($request->facture_id);

        if (!$facture->peut_etre_payee && $facture->statut !== FactureFournisseur::STATUT_BROUILLON) {
            return response()->json([
                'success' => false,
                'message' => 'Cette facture ne peut pas recevoir de règlement',
            ], 422);
        }

        // Vérifier que le montant ne dépasse pas le reste à payer
        // Convertir en float pour une comparaison correcte
        $montantReglement = (float) $request->montant;
        $resteAPayer = (float) $facture->reste_a_payer;

        // Tolérance de 0.01 pour éviter les erreurs d'arrondi
        if ($montantReglement > ($resteAPayer + 0.01)) {
            return response()->json([
                'success' => false,
                'message' => "Le montant du règlement (" . number_format($montantReglement, 0, ',', ' ') . " XOF) dépasse le reste à payer (" . number_format($resteAPayer, 0, ',', ' ') . " XOF)",
                'errors' => ['montant' => ["Le montant ne peut pas dépasser " . number_format($resteAPayer, 0, ',', ' ') . " XOF"]],
            ], 422);
        }

        // Vérifier le solde du compte bancaire si mode chèque ou virement
        $compteBancaire = null;
        if (in_array($request->mode_paiement, ['cheque', 'virement']) && $request->compte_bancaire_id) {
            $compteBancaire = CompteBancaire::with('banque')->find($request->compte_bancaire_id);

            if ($compteBancaire && !$compteBancaire->soldeEstSuffisant($montantReglement) && !$request->force_insufficient_balance) {
                return response()->json([
                    'success' => false,
                    'insufficient_balance' => true,
                    'message' => 'Solde insuffisant sur le compte bancaire',
                    'solde_actuel' => (float) $compteBancaire->solde,
                    'montant_demande' => $montantReglement,
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            // Générer le numéro de règlement
            $numeroReglement = $request->numero_reglement ?: ReglementFournisseur::genererNumeroReglement();

            // Déterminer banque et numéro de compte
            $banqueNom = $request->banque;
            $numeroCompteBancaire = $request->numero_compte_bancaire;

            if ($compteBancaire) {
                $banqueNom = $compteBancaire->banque->nom;
                $numeroCompteBancaire = $compteBancaire->numero_compte;
            }

            // Créer le règlement (utiliser le montant converti en float)
            $reglement = ReglementFournisseur::create([
                'numero_reglement' => $numeroReglement,
                'date_reglement' => $request->date_reglement,
                'facture_id' => $request->facture_id,
                'fournisseur_id' => $facture->fournisseur_id,
                'montant' => $montantReglement,
                'mode_paiement' => $request->mode_paiement,
                'reference' => $request->reference,
                'beneficiaire' => $request->beneficiaire,
                'banque' => $banqueNom,
                'numero_compte_bancaire' => $numeroCompteBancaire,
                'compte_tresorerie_id' => $request->compte_tresorerie_id,
                'observations' => $request->observations,
                'statut' => ReglementFournisseur::STATUT_VALIDE,
                'created_by' => auth()->id(),
            ]);

            // Débiter le compte bancaire si applicable
            if ($compteBancaire) {
                $compteBancaire->debiter($montantReglement);
            }

            // Mettre à jour la facture
            $facture->enregistrerPaiement($montantReglement);

            DB::commit();

            $reglement->load(['facture', 'fournisseur']);

            return response()->json([
                'success' => true,
                'message' => 'Règlement enregistré avec succès',
                'data' => $reglement->toApiArray(),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement du règlement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour un règlement (API)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $reglement = ReglementFournisseur::findOrFail($id);

        if (!$reglement->est_modifiable) {
            return response()->json([
                'success' => false,
                'message' => 'Ce règlement ne peut plus être modifié',
            ], 422);
        }

        $validator = Validator::make($request->all(), $this->validationRules($id));

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Si le montant change, ajuster la facture
            $nouveauMontant = (float) $request->montant;
            $ancienMontant = (float) $reglement->montant;

            if ($request->filled('montant') && abs($nouveauMontant - $ancienMontant) > 0.01) {
                $facture = $reglement->facture;
                $difference = $nouveauMontant - $ancienMontant;
                $resteAPayer = (float) $facture->reste_a_payer;

                // Vérifier que le nouveau montant ne dépasse pas
                if ($difference > 0 && $difference > ($resteAPayer + 0.01)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Le nouveau montant dépasse le reste à payer',
                        'errors' => ['montant' => ['Montant trop élevé de ' . number_format($difference - $resteAPayer, 0, ',', ' ') . ' XOF']],
                    ], 422);
                }

                // Ajuster le montant payé sur la facture
                $facture->montant_paye = (float) $facture->montant_paye + $difference;
                $facture->reste_a_payer = (float) $facture->montant_net - $facture->montant_paye;

                // Mettre à jour le statut
                if ($facture->reste_a_payer <= 0) {
                    $facture->statut = FactureFournisseur::STATUT_PAYEE;
                    $facture->reste_a_payer = 0;
                } elseif ($facture->montant_paye > 0) {
                    $facture->statut = FactureFournisseur::STATUT_PARTIELLEMENT_PAYEE;
                }

                $facture->save();
            }

            $reglement->update([
                'date_reglement' => $request->date_reglement ?? $reglement->date_reglement,
                'montant' => $request->montant ?? $reglement->montant,
                'mode_paiement' => $request->mode_paiement ?? $reglement->mode_paiement,
                'reference' => $request->reference,
                'beneficiaire' => $request->beneficiaire,
                'banque' => $request->banque,
                'numero_compte_bancaire' => $request->numero_compte_bancaire,
                'compte_tresorerie_id' => $request->compte_tresorerie_id,
                'observations' => $request->observations,
            ]);

            DB::commit();

            $reglement->load(['facture', 'fournisseur']);

            return response()->json([
                'success' => true,
                'message' => 'Règlement modifié avec succès',
                'data' => $reglement->toApiArray(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du règlement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer (annuler) un règlement (API)
     */
    public function destroy(int $id): JsonResponse
    {
        $reglement = ReglementFournisseur::findOrFail($id);
        $facture = $reglement->facture;

        try {
            DB::beginTransaction();

            $montant = (float) $reglement->montant;

            // Supprimer le règlement
            $reglement->delete();

            // Reverser le paiement sur la facture
            if ($facture) {
                $facture->montant_paye = max(0, (float) $facture->montant_paye - $montant);
                $facture->reste_a_payer = (float) $facture->montant_net - (float) $facture->montant_paye;

                if ($facture->montant_paye <= 0.01) {
                    $facture->statut = FactureFournisseur::STATUT_VALIDEE;
                    $facture->montant_paye = 0;
                } elseif ($facture->reste_a_payer <= 0.01) {
                    $facture->statut = FactureFournisseur::STATUT_PAYEE;
                    $facture->reste_a_payer = 0;
                } else {
                    $facture->statut = FactureFournisseur::STATUT_PARTIELLEMENT_PAYEE;
                }

                $facture->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Règlement supprimé avec succès',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du règlement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Générer un numéro de règlement (API)
     */
    public function genererNumero(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'numero_reglement' => ReglementFournisseur::genererNumeroReglement(),
        ]);
    }

    /**
     * Statistiques des règlements (API)
     */
    public function stats(Request $request): JsonResponse
    {
        $fournisseurId = $request->filled('fournisseur_id') ? (int) $request->fournisseur_id : null;
        $stats = ReglementFournisseur::getStatistiques($fournisseurId);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Obtenir les règlements d'une facture (API)
     */
    public function parFacture(int $factureId): JsonResponse
    {
        $reglements = ReglementFournisseur::where('facture_id', $factureId)
            ->with(['createur'])
            ->orderBy('date_reglement', 'desc')
            ->get()
            ->map(fn($r) => $r->toApiArray());

        return response()->json([
            'success' => true,
            'data' => $reglements,
        ]);
    }

    /**
     * Générer le PDF du Mandat de Paiement
     */
    public function mandat(int $id)
    {
        $reglement = ReglementFournisseur::with(['facture', 'fournisseur'])
            ->findOrFail($id);

        $data = $this->buildPdfData($reglement);

        $pdf = Pdf::loadView('pdf.mandat-paiement', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("mandat-paiement-{$reglement->id}.pdf");
    }

    /**
     * Générer le PDF de la Fiche d'Imputation
     */
    public function imputation(int $id)
    {
        $reglement = ReglementFournisseur::with(['facture', 'fournisseur'])
            ->findOrFail($id);

        $data = $this->buildPdfData($reglement);

        // Logique comptable : déterminer les comptes
        $data['compteDebit'] = $reglement->fournisseur->compteComptable
            ? $reglement->fournisseur->compteComptable->numero_compte
            : '401' . str_pad($reglement->fournisseur->id, 3, '0', STR_PAD_LEFT);

        $data['compteCredit'] = match($reglement->mode_paiement) {
            'especes' => '571000',
            'cheque', 'virement', 'carte' => '521000',
            'mobile_money' => '521500',
            default => '521000',
        };

        $data['libelleCredit'] = match($reglement->mode_paiement) {
            'especes' => 'Caisse - Espèces',
            'cheque' => 'Banque - Compte courant',
            'virement' => 'Banque - Compte courant',
            'carte' => 'Banque - Compte courant',
            'mobile_money' => 'Banque - Mobile Money',
            default => 'Banque',
        };

        $pdf = Pdf::loadView('pdf.fiche-imputation', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("fiche-imputation-{$reglement->id}.pdf");
    }

    /**
     * Construire les données communes pour les PDFs
     */
    private function buildPdfData(ReglementFournisseur $reglement): array
    {
        $montant = (float) $reglement->montant;

        $modeLabel = match($reglement->mode_paiement) {
            'especes' => 'Espèces',
            'cheque' => 'Chèque',
            'virement' => 'Virement bancaire',
            'carte' => 'Carte bancaire',
            'mobile_money' => 'Mobile Money',
            default => $reglement->mode_paiement,
        };

        $montantFormate = number_format($montant, 0, ',', ' ') . ' FCFA';
        $montantEnLettres = $this->convertirMontantEnLettres($montant) . ' francs CFA';

        return [
            'reglement' => $reglement,
            'modeLabel' => $modeLabel,
            'montantFormate' => $montantFormate,
            'montantEnLettres' => $montantEnLettres,
        ];
    }

    /**
     * Convertir un montant en lettres (français)
     */
    private function convertirMontantEnLettres(float $montant): string
    {
        $montant = (int) $montant;

        if ($montant === 0) return 'zéro';

        $unites = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf',
                   'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];
        $dizaines = ['', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante', 'quatre-vingt', 'quatre-vingt'];

        $convertirBloc = function(int $n) use ($unites, $dizaines): string {
            if ($n === 0) return '';
            if ($n < 20) return $unites[$n];
            if ($n < 100) {
                $d = intdiv($n, 10);
                $u = $n % 10;
                if ($d === 7 || $d === 9) {
                    $u += 10;
                    $base = $dizaines[$d];
                    if ($u === 11 && $d === 7) return $base . ' et onze';
                    return $base . '-' . $unites[$u];
                }
                if ($u === 0) {
                    return $dizaines[$d] . ($d === 8 ? 's' : '');
                }
                if ($u === 1 && $d !== 8) return $dizaines[$d] . ' et un';
                return $dizaines[$d] . '-' . $unites[$u];
            }
            $c = intdiv($n, 100);
            $r = $n % 100;
            $result = ($c === 1 ? 'cent' : $unites[$c] . ' cent');
            if ($r === 0 && $c > 1) $result .= 's';
            if ($r > 0) {
                $convertirBloc2 = function(int $n2) use ($unites, $dizaines): string {
                    if ($n2 < 20) return $unites[$n2];
                    $d = intdiv($n2, 10);
                    $u = $n2 % 10;
                    if ($d === 7 || $d === 9) {
                        $u += 10;
                        $base = $dizaines[$d];
                        if ($u === 11 && $d === 7) return $base . ' et onze';
                        return $base . '-' . $unites[$u];
                    }
                    if ($u === 0) return $dizaines[$d] . ($d === 8 ? 's' : '');
                    if ($u === 1 && $d !== 8) return $dizaines[$d] . ' et un';
                    return $dizaines[$d] . '-' . $unites[$u];
                };
                $result .= ' ' . $convertirBloc2($r);
            }
            return $result;
        };

        $parties = [];

        // Milliards
        $milliards = intdiv($montant, 1000000000);
        $montant %= 1000000000;
        if ($milliards > 0) {
            $parties[] = ($milliards === 1 ? 'un milliard' : $convertirBloc($milliards) . ' milliards');
        }

        // Millions
        $millions = intdiv($montant, 1000000);
        $montant %= 1000000;
        if ($millions > 0) {
            $parties[] = ($millions === 1 ? 'un million' : $convertirBloc($millions) . ' millions');
        }

        // Milliers
        $milliers = intdiv($montant, 1000);
        $montant %= 1000;
        if ($milliers > 0) {
            $parties[] = ($milliers === 1 ? 'mille' : $convertirBloc($milliers) . ' mille');
        }

        // Reste
        if ($montant > 0) {
            $parties[] = $convertirBloc($montant);
        }

        return ucfirst(implode(' ', $parties));
    }

    /**
     * Règles de validation
     */
    private function validationRules(?int $id = null): array
    {
        return [
            'numero_reglement' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('reglements_fournisseurs')->ignore($id),
            ],
            'date_reglement' => ['required', 'date'],
            'facture_id' => ['required', 'integer', 'exists:factures_fournisseurs,id'],
            'montant' => ['required', 'numeric', 'min:1'],
            'mode_paiement' => ['required', 'string', Rule::in([
                'virement', 'cheque', 'especes', 'mobile_money', 'carte'
            ])],
            'reference' => ['nullable', 'string', 'max:100'],
            'beneficiaire' => ['nullable', 'string', 'max:255'],
            'banque' => ['nullable', 'string', 'max:100'],
            'numero_compte_bancaire' => ['nullable', 'string', 'max:50'],
            'compte_bancaire_id' => ['nullable', 'integer', 'exists:comptes_bancaires,id'],
            'compte_tresorerie_id' => ['nullable', 'integer', 'exists:plan_comptable_ohada,id'],
            'force_insufficient_balance' => ['nullable', 'boolean'],
            'observations' => ['nullable', 'string'],
        ];
    }
}
