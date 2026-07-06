<?php

namespace Modules\Auth\Http\Controllers;

use App\Models\User;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = [
            'user' => [
                'id'           => $user->id,
                'name'         => $user->name,
                'email'        => $user->email,
                'account_type' => $user->account_type?->value,
                'trust_level'  => $user->trust_level,
            ],
            'organization' => TenantContext::get()?->only(['id', 'name', 'slug', 'status']),
            'roles'        => $user->getRoleNames(),
        ];

        if ($user->hasRole('System_Admin')) {
            $data['permissions'] = $user->getAllPermissions()
                ->pluck('name')
                ->sort()
                ->values();
        }

        return response()->json($data);
    }
}
