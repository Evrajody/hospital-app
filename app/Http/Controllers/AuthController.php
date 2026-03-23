<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use App\Models\User;

class AuthController extends Controller
{
    public function loginPage()
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if ($user && !$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Votre compte est désactivé. Contactez l\'administrateur.'],
            ]);
        }

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants sont incorrects.'],
            ]);
        }

        $request->session()->regenerate();

        ActivityLog::log('login', 'auth', "Connexion de " . auth()->user()->name);

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request)
    {
        ActivityLog::log('logout', 'auth', "Déconnexion de " . auth()->user()->name);

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}