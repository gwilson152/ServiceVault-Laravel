<?php

namespace App\Http\Middleware;

use App\Models\Account;
use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission  The permission to check
     * @param  string|null  $accountParam  Route parameter name for account (optional)
     */
    public function handle(Request $request, Closure $next, string $permission, ?string $accountParam = null): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Authentication required');
        }

        // Check system-level permission if no account specified
        if (! $accountParam) {
            if (! $this->permissionService->hasSystemPermission($user, $permission)) {
                abort(403, 'Insufficient permissions');
            }

            return $next($request);
        }

        // Check account-specific permission
        $accountId = $request->route($accountParam);
        if (! $accountId) {
            abort(400, "Account parameter '{$accountParam}' not found in route");
        }

        $account = Account::find($accountId);
        if (! $account) {
            abort(404, 'Account not found');
        }

        if (! $this->permissionService->hasPermissionForAccount($user, $permission, $account)) {
            abort(403, 'Insufficient permissions for this account');
        }

        // Add account to request for use in controller
        $request->merge(['_account' => $account]);

        return $next($request);
    }
}
