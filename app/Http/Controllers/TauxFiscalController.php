<?php

namespace App\Http\Controllers;

use App\Models\TauxFiscal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TauxFiscalController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $tauxTva = TauxFiscal::tva()->orderBy('taux')->get()
            ->map(fn ($t) => $t->toApiArray());

        $tauxAib = TauxFiscal::aib()->orderBy('taux')->get()
            ->map(fn ($t) => $t->toApiArray());

        return Inertia::render('Parametres/TauxFiscaux/Index', [
            'tauxTva' => $tauxTva,
            'tauxAib' => $tauxAib,
            'stats' => [
                'total_tva' => TauxFiscal::tva()->count(),
                'total_aib' => TauxFiscal::aib()->count(),
                'actifs' => TauxFiscal::actif()->count(),
            ],
            'user' => [
                'name' => auth()->user()?->name ?? 'Utilisateur',
                'email' => auth()->user()?->email ?? 'user@hospital.bj',
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string', 'in:tva,aib'],
            'libelle' => ['required', 'string', 'min:2', 'max:100'],
            'taux' => ['required', 'numeric', 'min:0', 'max:100'],
            'actif' => ['boolean'],
            'par_defaut' => ['boolean'],
        ]);

        try {
            DB::beginTransaction();

            // Si par_defaut, retirer le par_defaut des autres du même type
            if ($request->par_defaut) {
                TauxFiscal::where('type', $request->type)
                    ->update(['par_defaut' => false]);
            }

            $taux = TauxFiscal::create([
                'type' => $request->type,
                'libelle' => $request->libelle,
                'taux' => $request->taux,
                'actif' => $request->actif ?? true,
                'par_defaut' => $request->par_defaut ?? false,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Taux fiscal créé avec succès',
                'data' => $taux->toApiArray(),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $taux = TauxFiscal::findOrFail($id);

        $request->validate([
            'libelle' => ['required', 'string', 'min:2', 'max:100'],
            'taux' => ['required', 'numeric', 'min:0', 'max:100'],
            'actif' => ['boolean'],
            'par_defaut' => ['boolean'],
        ]);

        try {
            DB::beginTransaction();

            if ($request->par_defaut) {
                TauxFiscal::where('type', $taux->type)
                    ->where('id', '!=', $taux->id)
                    ->update(['par_defaut' => false]);
            }

            $taux->update([
                'libelle' => $request->libelle,
                'taux' => $request->taux,
                'actif' => $request->actif ?? $taux->actif,
                'par_defaut' => $request->par_defaut ?? $taux->par_defaut,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Taux fiscal modifié avec succès',
                'data' => $taux->fresh()->toApiArray(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $taux = TauxFiscal::findOrFail($id);
        $taux->delete();

        return response()->json([
            'success' => true,
            'message' => 'Taux fiscal supprimé avec succès',
        ]);
    }

    public function toggleActif(int $id): JsonResponse
    {
        $taux = TauxFiscal::findOrFail($id);
        $taux->update(['actif' => !$taux->actif]);

        return response()->json([
            'success' => true,
            'message' => $taux->actif ? 'Taux activé' : 'Taux désactivé',
            'data' => $taux->fresh()->toApiArray(),
        ]);
    }
}
