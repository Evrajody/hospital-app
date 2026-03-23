<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ActivityLogController extends Controller
{
    /**
     * Afficher le journal d'activité
     */
    public function index(Request $request): InertiaResponse
    {
        $query = ActivityLog::query()->orderBy('created_at', 'desc');

        // Filtre par utilisateur
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filtre par module
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        // Filtre par action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filtre par période
        if ($request->filled('date_debut')) {
            $query->whereDate('created_at', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('created_at', '<=', $request->date_fin);
        }

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'ILIKE', "%{$search}%")
                  ->orWhere('user_name', 'ILIKE', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->input('per_page', 50);
        $logsPaginated = $query->paginate($perPage);

        $logs = $logsPaginated->getCollection()->map(fn($l) => $l->toApiArray());

        // Utilisateurs pour le filtre
        $users = User::orderBy('name')
            ->get()
            ->map(fn($u) => ['id' => $u->id, 'name' => $u->name]);

        // Modules disponibles
        $modules = ActivityLog::select('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module')
            ->map(fn($m) => [
                'value' => $m,
                'label' => (new ActivityLog(['module' => $m]))->module_libelle,
            ]);

        return Inertia::render('Admin/JournalActivite', [
            'logs' => $logs,
            'users' => $users,
            'modules' => $modules,
            'pagination' => [
                'current_page' => $logsPaginated->currentPage(),
                'per_page' => $logsPaginated->perPage(),
                'total' => $logsPaginated->total(),
                'last_page' => $logsPaginated->lastPage(),
            ],
            'filters' => [
                'search' => $request->input('search', ''),
                'user_id' => $request->input('user_id', ''),
                'module' => $request->input('module', ''),
                'action' => $request->input('action', ''),
                'date_debut' => $request->input('date_debut', ''),
                'date_fin' => $request->input('date_fin', ''),
            ],
        ]);
    }
}
