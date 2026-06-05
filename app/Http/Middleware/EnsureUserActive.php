<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserActive
{
    /**
     * Déconnecte immédiatement tout utilisateur dont le compte a été
     * supprimé (soft delete) ou désactivé pendant que sa session était active.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && ($user->trashed() || ! $user->is_active)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = 'Votre compte a été désactivé ou supprimé. Contactez l\'administrateur.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 401);
            }

            return redirect('/login')->with('error', $message);
        }

        return $next($request);
    }
}
