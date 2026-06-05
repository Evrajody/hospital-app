<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): \Inertia\Response
    {
        $users = User::with('roles')
            ->orderBy('name')
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'telephone' => $user->telephone,
                'poste' => $user->poste,
                'is_active' => $user->is_active,
                'roles' => $user->roles->pluck('name')->toArray(),
                'created_at' => $user->created_at?->format('d/m/Y'),
            ]);

        $roles = Role::orderBy('name')->get()->map(fn($r) => [
            'id' => $r->id,
            'name' => $r->name,
        ]);

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    /**
     * Empêche un utilisateur non super-admin d'attribuer le rôle Super Administrateur.
     */
    private function denyAssignSuperAdmin(array $roles): ?JsonResponse
    {
        $wantsSuperAdmin = collect($roles)->contains(fn ($r) => strcasecmp($r, User::ROLE_SUPER_ADMIN_NAME) === 0);
        if ($wantsSuperAdmin && ! auth()->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => "Seul un super administrateur peut attribuer le rôle Super Administrateur.",
            ], 403);
        }
        return null;
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->withoutTrashed()],
            'password' => [
                'required',
                'string',
                \Illuminate\Validation\Rules\Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols(),
            ],
            'telephone' => ['nullable', 'string', 'max:30'],
            'poste' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        if ($resp = $this->denyAssignSuperAdmin($validated['roles'] ?? [])) {
            return $resp;
        }

        $attributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'telephone' => $validated['telephone'] ?? null,
            'poste' => $validated['poste'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ];

        // Si un utilisateur précédemment supprimé (soft delete) possède cet email,
        // on le restaure et on remet à jour ses informations plutôt que d'échouer
        // sur la contrainte d'unicité — ce qui préserve sa traçabilité (created_by/validated_by).
        $trashed = User::onlyTrashed()->where('email', $validated['email'])->first();
        if ($trashed) {
            $trashed->restore();
            $trashed->fill($attributes)->save();
            $user = $trashed;
            $user->syncRoles($validated['roles'] ?? []);
            ActivityLog::log('update', 'utilisateur', "Restauration de l'utilisateur {$user->name}", $user);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès',
            ]);
        }

        $user = User::create($attributes);

        if (!empty($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        ActivityLog::log('create', 'utilisateur', "Création de l'utilisateur {$user->name}", $user);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur créé avec succès',
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Un non super-admin ne peut pas modifier un compte super administrateur.
        if ($user->isSuperAdmin() && ! auth()->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => "Seul un super administrateur peut modifier un compte super administrateur.",
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)->withoutTrashed()],
            'password' => [
                'nullable',
                'string',
                \Illuminate\Validation\Rules\Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols(),
            ],
            'telephone' => ['nullable', 'string', 'max:30'],
            'poste' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        if ($resp = $this->denyAssignSuperAdmin($validated['roles'] ?? [])) {
            return $resp;
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'telephone' => $validated['telephone'] ?? null,
            'poste' => $validated['poste'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => $validated['password']]);
        }

        $user->syncRoles($validated['roles'] ?? []);

        ActivityLog::log('update', 'utilisateur', "Modification de l'utilisateur {$user->name}", $user);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur modifié avec succès',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer votre propre compte',
            ], 422);
        }

        if ($user->isSuperAdmin() && ! auth()->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => "Seul un super administrateur peut supprimer un compte super administrateur.",
            ], 403);
        }

        $nom = $user->name;
        $user->delete();

        ActivityLog::log('delete', 'utilisateur', "Suppression de l'utilisateur {$nom}", null, ['name' => $nom]);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur supprimé avec succès',
        ]);
    }

    public function toggleActive(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas désactiver votre propre compte',
            ], 422);
        }

        if ($user->isSuperAdmin() && ! auth()->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => "Seul un super administrateur peut désactiver un compte super administrateur.",
            ], 403);
        }

        $user->update(['is_active' => !$user->is_active]);

        $action = $user->is_active ? 'Activation' : 'Désactivation';
        ActivityLog::log('update', 'utilisateur', "{$action} de l'utilisateur {$user->name}", $user);

        return response()->json([
            'success' => true,
            'message' => $user->is_active ? 'Utilisateur activé' : 'Utilisateur désactivé',
        ]);
    }
}
