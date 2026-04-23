<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ProfileController extends Controller
{
    public function index(): InertiaResponse
    {
        $user = auth()->user();

        $data = [
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'telephone' => $user->telephone,
                'poste' => $user->poste,
                'role' => $user->role,
                'role_libelle' => $user->role_libelle,
                'created_at' => $user->created_at?->format('d/m/Y'),
                'signature_url' => $user->signature_path
                    ? Storage::url($user->signature_path)
                    : null,
            ],
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ];

        return Inertia::render('Profile/Index', $data);
    }

    public function update(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'telephone' => 'nullable|string|max:30',
            'poste' => 'nullable|string|max:100',
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour avec succès',
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->letters()->numbers()->symbols(),
            ],
        ]);

        $user = auth()->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Le mot de passe actuel est incorrect',
                'errors' => ['current_password' => ['Le mot de passe actuel est incorrect']],
            ], 422);
        }

        $user->update([
            'password' => $validated['password'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe modifié avec succès',
        ]);
    }

    public function uploadSignature(Request $request): JsonResponse
    {
        $request->validate([
            'signature' => 'required|file|mimes:png,jpg,jpeg|max:2048',
        ]);

        $user = auth()->user();

        if ($user->signature_path) {
            Storage::disk('public')->delete($user->signature_path);
        }

        $path = $request->file('signature')->store('signatures', 'public');

        $user->update(['signature_path' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Signature enregistrée avec succès',
            'signature_url' => Storage::url($path),
        ]);
    }

    public function deleteSignature(): JsonResponse
    {
        $user = auth()->user();

        if ($user->signature_path) {
            Storage::disk('public')->delete($user->signature_path);
            $user->update(['signature_path' => null]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Signature supprimée avec succès',
        ]);
    }

    public function etablissement(): InertiaResponse
    {
        return Inertia::render('Admin/Etablissement', [
            'etablissement' => Setting::getEtablissement(),
        ]);
    }

    public function updateEtablissement(Request $request): JsonResponse
    {
        $user = auth()->user();

        if ($user->role !== User::ROLE_ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'pays' => 'nullable|string|max:255',
            'adresse' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'directeur' => 'required|string|max:255',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set("etablissement_{$key}", $value);
        }

        ActivityLog::log('update', 'parametres', "Modification des paramètres établissement");

        return response()->json([
            'success' => true,
            'message' => 'Paramètres de l\'établissement mis à jour avec succès',
        ]);
    }
}
