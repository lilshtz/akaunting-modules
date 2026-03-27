<?php

namespace Modules\Roles\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Roles\Services\PermissionResolver;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeModuleAccess
{
    public function __construct(protected PermissionResolver $resolver)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $allowed = $this->resolver->resolveRequest($request);

        if ($allowed === false) {
            abort(403, trans('roles::general.unauthorized'));
        }

        return $next($request);
    }
}
