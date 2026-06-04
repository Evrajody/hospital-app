<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CompteComptable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PlanComptableController extends Controller
{
    /**
     * Afficher la liste des comptes (Vue Inertia)
     */
    public function index(Request $request): InertiaResponse
    {
        $query = CompteComptable::query();

        // Filtres
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('numero_compte', 'ILIKE', "%{$search}%")
                  ->orWhere('libelle', 'ILIKE', "%{$search}%");
            });
        }

        if ($classe = $request->get('classe')) {
            $query->where('classe', $classe);
        }

        // Filtre par source (standard ou personnalisé)
        if ($source = $request->get('source')) {
            if ($source === 'custom') {
                $query->where('is_custom', true);
            } elseif ($source === 'standard') {
                $query->where('is_custom', false);
            }
        }

        // Tri
        $sortBy = $request->get('sort_by', 'numero_compte');
        $sortOrder = $request->get('sort_order', 'asc') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['numero_compte', 'libelle', 'classe'];
        $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'numero_compte';
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 20);
        $paginated = $query->paginate($perPage);

        $comptes = $paginated->map(fn($c) => [
            'id' => $c->id,
            'numero' => $c->numero_compte,
            'libelle' => $c->libelle,
            'classe' => $c->classe,
            'niveau' => $c->niveau,
            'is_custom' => $c->is_custom ?? false,
            'parent_id' => $c->parent_id,
        ]);

        // Statistiques par classe
        $stats = [
            'total' => CompteComptable::count(),
            'custom' => CompteComptable::where('is_custom', true)->count(),
            'charges' => CompteComptable::where('classe', 6)->count(),
            'produits' => CompteComptable::where('classe', 7)->count(),
            'immobilisations' => CompteComptable::where('classe', 2)->count(),
            'tiers' => CompteComptable::where('classe', 4)->count(),
            'tresorerie' => CompteComptable::where('classe', 5)->count(),
            'capitaux' => CompteComptable::where('classe', 1)->count(),
            'stocks' => CompteComptable::where('classe', 3)->count(),
        ];

        // Comptes parents disponibles pour la création
        $comptesParents = CompteComptable::orderBy('numero_compte')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'numero' => $c->numero_compte,
                'libelle' => $c->libelle,
                'classe' => $c->classe,
            ]);

        return Inertia::render('PlanComptable/Index', [
            'comptes' => $comptes,
            'comptesParents' => $comptesParents,
            'stats' => $stats,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
            'filters' => [
                'search' => $request->get('search', ''),
                'classe' => $request->get('classe', ''),
                'source' => $request->get('source', ''),
            ],
            'user' => [
                'name' => auth()->user()?->name ?? 'Utilisateur',
                'email' => auth()->user()?->email ?? '',
            ],
        ]);
    }

    /**
     * Construire la requête filtrée (mêmes filtres que l'index, sans pagination) pour l'export.
     */
    private function buildExportQuery(Request $request)
    {
        $query = CompteComptable::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('numero_compte', 'ILIKE', "%{$search}%")
                  ->orWhere('libelle', 'ILIKE', "%{$search}%");
            });
        }

        if ($classe = $request->get('classe')) {
            $query->where('classe', $classe);
        }

        if ($source = $request->get('source')) {
            if ($source === 'custom') {
                $query->where('is_custom', true);
            } elseif ($source === 'standard') {
                $query->where('is_custom', false);
            }
        }

        $sortBy = $request->get('sort_by', 'numero_compte');
        $sortOrder = $request->get('sort_order', 'asc') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['numero_compte', 'libelle', 'classe'];
        $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'numero_compte';

        return $query->orderBy($sortBy, $sortOrder);
    }

    /**
     * Sous-titre décrivant les filtres actifs de l'export.
     */
    private function exportSousTitre(Request $request): string
    {
        $parts = [];
        if ($s = $request->get('search')) {
            $parts[] = 'Recherche : "' . $s . '"';
        }
        if ($c = $request->get('classe')) {
            $parts[] = 'Classe ' . $c;
        }
        if ($src = $request->get('source')) {
            $parts[] = $src === 'custom' ? 'Comptes personnalisés' : ($src === 'standard' ? 'Comptes standard' : '');
        }
        return implode(' — ', array_filter($parts));
    }

    /**
     * Export PDF du plan comptable (stream pour impression, ou téléchargement si ?download=1).
     */
    public function exportPdf(Request $request)
    {
        $comptes = $this->buildExportQuery($request)->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.plan-comptable', [
            'comptes' => $comptes,
            'titre' => 'PLAN COMPTABLE OHADA',
            'sousTitre' => $this->exportSousTitre($request),
            'generatedBy' => auth()->user()?->name ?? 'Utilisateur',
            'generatedAt' => now()->format('d/m/Y à H:i'),
        ])->setPaper('a4', 'portrait');

        return $request->boolean('download')
            ? $pdf->download('plan-comptable.pdf')
            : $pdf->stream('plan-comptable.pdf');
    }

    /**
     * Export Excel du plan comptable.
     */
    public function exportExcel(Request $request)
    {
        $comptes = $this->buildExportQuery($request)->get();

        $rows = $comptes->map(fn($c) => [
            $c->numero_compte,
            $c->libelle,
            $c->classe,
            ($c->is_custom ?? false) ? 'Personnalisé' : 'Standard',
        ])->toArray();

        $titre = 'Plan Comptable OHADA';
        if ($st = $this->exportSousTitre($request)) {
            $titre .= ' — ' . $st;
        }

        return \App\Support\ExcelExporter::download(
            ['N° Compte', 'Libellé', 'Classe', 'Source'],
            $rows,
            'plan-comptable',
            $titre,
        );
    }

    /**
     * Recherche de comptes (API)
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->get('q', '');
        $classe = $request->get('classe');
        $limit = $request->get('limit', 20);

        $query = CompteComptable::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('numero_compte', 'ILIKE', "%{$search}%")
                  ->orWhere('libelle', 'ILIKE', "%{$search}%");
            });
        }

        if ($classe) {
            $query->where('classe', $classe);
        }

        $comptes = $query->orderBy('numero_compte')
            ->limit($limit)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'numero' => $c->numero_compte,
                'libelle' => $c->libelle,
                'label' => $c->numero_compte . ' - ' . $c->libelle,
            ]);

        return response()->json([
            'success' => true,
            'data' => $comptes,
        ]);
    }

    /**
     * Détails d'un compte
     */
    public function show(CompteComptable $compte): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $compte->id,
                'numero' => $compte->numero_compte,
                'libelle' => $compte->libelle,
                'classe' => $compte->classe,
                'niveau' => $compte->niveau,
                'type' => $compte->type_compte,
                'is_custom' => $compte->is_custom,
            ],
        ]);
    }

    /**
     * Créer un compte personnalisé (API)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'numero_compte' => [
                'required',
                'string',
                'max:10',
                'regex:/^[a-zA-Z0-9.]+$/',
                Rule::unique('plan_comptable_ohada', 'numero_compte'),
            ],
            'libelle' => 'required|string|max:500',
            'parent_id' => 'nullable|exists:plan_comptable_ohada,id',
        ]);

        // Déterminer la classe à partir du premier chiffre du numéro
        $classe = (int) substr($validated['numero_compte'], 0, 1);

        // Déterminer le niveau à partir de la longueur du numéro
        $length = strlen($validated['numero_compte']);
        $niveau = match($length) {
            2 => 1,
            3 => 2,
            4 => 3,
            5 => 4,
            default => min($length - 1, 5)
        };

        // Vérifier la cohérence avec le compte parent si fourni
        if ($validated['parent_id']) {
            $parent = CompteComptable::find($validated['parent_id']);
            if ($parent && !str_starts_with($validated['numero_compte'], $parent->numero_compte)) {
                return response()->json([
                    'success' => false,
                    'message' => "Le numéro de compte doit commencer par {$parent->numero_compte}",
                ], 422);
            }
        }

        $compte = CompteComptable::create([
            'numero_compte' => $validated['numero_compte'],
            'libelle' => $validated['libelle'],
            'classe' => $classe,
            'niveau' => $niveau,
            'is_custom' => true,
            'parent_id' => $validated['parent_id'],
            'created_by' => auth()->id(),
        ]);

        ActivityLog::log('create', 'plan_comptable', "Création du compte {$compte->numero_compte} - {$compte->libelle}", $compte);

        return response()->json([
            'success' => true,
            'message' => 'Compte créé avec succès',
            'data' => [
                'id' => $compte->id,
                'numero' => $compte->numero_compte,
                'libelle' => $compte->libelle,
            ],
        ]);
    }

    /**
     * Modifier un compte (API)
     */
    public function update(Request $request, CompteComptable $compte): JsonResponse
    {
        $validated = $request->validate([
            'numero_compte' => [
                'required',
                'string',
                'max:10',
                'regex:/^[a-zA-Z0-9.]+$/',
                Rule::unique('plan_comptable_ohada', 'numero_compte')->ignore($compte->id),
            ],
            'libelle' => 'required|string|max:500',
            'parent_id' => 'nullable|exists:plan_comptable_ohada,id',
        ]);

        // Déterminer la classe à partir du premier chiffre du numéro
        $classe = (int) substr($validated['numero_compte'], 0, 1);

        // Déterminer le niveau à partir de la longueur du numéro
        $length = strlen($validated['numero_compte']);
        $niveau = match($length) {
            2 => 1,
            3 => 2,
            4 => 3,
            5 => 4,
            default => min($length - 1, 5)
        };

        // Vérifier la cohérence avec le compte parent si fourni
        if ($validated['parent_id']) {
            $parent = CompteComptable::find($validated['parent_id']);
            if ($parent && !str_starts_with($validated['numero_compte'], $parent->numero_compte)) {
                return response()->json([
                    'success' => false,
                    'message' => "Le numéro de compte doit commencer par {$parent->numero_compte}",
                ], 422);
            }
        }

        $compte->update([
            'numero_compte' => $validated['numero_compte'],
            'libelle' => $validated['libelle'],
            'classe' => $classe,
            'niveau' => $niveau,
            'parent_id' => $validated['parent_id'],
        ]);

        ActivityLog::log('update', 'plan_comptable', "Modification du compte {$compte->numero_compte} - {$compte->libelle}", $compte);

        return response()->json([
            'success' => true,
            'message' => 'Compte modifié avec succès',
            'data' => [
                'id' => $compte->id,
                'numero' => $compte->numero_compte,
                'libelle' => $compte->libelle,
            ],
        ]);
    }

    /**
     * Supprimer un compte personnalisé (API)
     */
    public function destroy(CompteComptable $compte): JsonResponse
    {
        // Vérifier si le compte a des enfants
        if ($compte->comptesEnfants()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce compte possède des sous-comptes et ne peut pas être supprimé',
            ], 422);
        }

        $numero = $compte->numero_compte;
        $libelle = $compte->libelle;
        $compte->delete();

        ActivityLog::log('delete', 'plan_comptable', "Suppression du compte {$numero} - {$libelle}", null, ['numero_compte' => $numero, 'libelle' => $libelle]);

        return response()->json([
            'success' => true,
            'message' => 'Compte supprimé avec succès',
        ]);
    }
}
