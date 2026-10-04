<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Props shared by every page: the signed-in user and what they can do, so the frontend
     * only shows the actions the policies and gates allow (the server still enforces them).
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                ] : null,
                'can' => [
                    'manageCatalog' => (bool) $user?->can('manage-catalog'),
                    'placeOrders' => (bool) $user?->can('place-orders'),
                ],
            ],
        ];
    }
}
