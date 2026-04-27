<?php

namespace App\Http\Middleware;

use App\Models\TauxFiscal;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => fn () => $request->user() ? [
                'user' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'telephone' => $request->user()->telephone,
                    'poste' => $request->user()->poste,
                    'roles' => $request->user()->getRoleNames()->toArray(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name')->toArray(),
                ],
            ] : null,
            'tva' => fn () => $request->user() ? TauxFiscal::getTvaConfig() : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
