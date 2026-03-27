<?php

namespace Modules\Roles\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Roles\Services\PermissionResolver;

class Permissions extends Controller
{
    public function __construct(protected PermissionResolver $resolver)
    {
    }

    public function current(): JsonResponse
    {
        $assignment = $this->resolver->assignmentForUser((int) auth()->id(), (int) company_id());

        if (! $assignment) {
            return response()->json([
                'role' => null,
                'permissions' => [],
            ]);
        }

        return response()->json([
            'role' => [
                'id' => $assignment->role->id,
                'name' => $assignment->role->display_name,
            ],
            'permissions' => $this->resolver->permissionsForRole($assignment->role, (int) company_id())->values(),
        ]);
    }

    public function check(string $ability): JsonResponse
    {
        $result = $this->resolver->resolveAbility(auth()->user(), $ability);

        return response()->json([
            'ability' => $ability,
            'allowed' => $result !== false,
            'resolved' => $result,
        ], $result === false ? 403 : 200);
    }
}
