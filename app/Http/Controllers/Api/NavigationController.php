<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NavigationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NavigationController extends Controller
{
    protected NavigationService $navigationService;

    public function __construct(NavigationService $navigationService)
    {
        $this->navigationService = $navigationService;
    }

    /**
     * Get navigation items for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $grouped = $request->boolean('grouped', false);

        if ($grouped) {
            $navigation = $this->navigationService->getGroupedNavigationForUser($user);
            $groupLabels = $this->navigationService->getGroupLabels();

            return response()->json([
                'navigation' => $navigation,
                'group_labels' => $groupLabels,
                'user_context' => [
                    'is_super_admin' => $user->isSuperAdmin(),
                    'permissions_count' => count($user->permissions ?? []),
                ],
            ]);
        }

        $navigation = $this->navigationService->getNavigationForUser($user);

        return response()->json([
            'navigation' => $navigation,
            'user_context' => [
                'is_super_admin' => $user->isSuperAdmin(),
                'permissions_count' => count($user->permissions ?? []),
            ],
        ]);
    }

    /**
     * Get breadcrumbs for current route
     */
    public function breadcrumbs(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentRoute = $request->input('route', 'dashboard');

        $breadcrumbs = $this->navigationService->getBreadcrumbsForRoute($user, $currentRoute);

        return response()->json([
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    /**
     * Check if user can access specific routes
     */
    public function canAccess(Request $request): JsonResponse
    {
        $user = $request->user();
        $routes = $request->input('routes', []);

        if (! is_array($routes)) {
            return response()->json(['error' => 'Routes must be an array'], 400);
        }

        $access = [];
        foreach ($routes as $route) {
            $access[$route] = $this->navigationService->userCanAccessRoute($user, $route);
        }

        return response()->json([
            'access' => $access,
        ]);
    }
}
