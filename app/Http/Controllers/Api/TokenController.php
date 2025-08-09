<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TokenAbilityService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TokenController extends Controller
{
    /**
     * Display a listing of the user's tokens.
     */
    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens->map(function ($token) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at,
                'expires_at' => $token->expires_at,
                'created_at' => $token->created_at,
            ];
        });

        return response()->json([
            'data' => $tokens,
        ]);
    }

    /**
     * Store a newly created token.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'array',
            'abilities.*' => 'string',
            'expires_at' => 'nullable|date|after:now',
            'password' => 'required|string',
        ]);

        // Verify password for security
        if (!Hash::check($request->password, $request->user()->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.'],
            ]);
        }

        // Validate abilities
        $abilities = TokenAbilityService::validateAbilities($request->abilities ?? []);
        
        $token = $request->user()->createToken(
            $request->name,
            $abilities,
            $request->expires_at ? now()->parse($request->expires_at) : null
        );

        return response()->json([
            'data' => [
                'token' => $token->plainTextToken,
                'name' => $request->name,
                'abilities' => $abilities,
                'expires_at' => $token->accessToken->expires_at,
            ],
        ], 201);
    }

    /**
     * Display the specified token.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $token = $request->user()->tokens()->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at,
                'expires_at' => $token->expires_at,
                'created_at' => $token->created_at,
            ],
        ]);
    }

    /**
     * Update the specified token.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'abilities' => 'sometimes|array',
            'abilities.*' => 'string',
        ]);

        $token = $request->user()->tokens()->findOrFail($id);

        if ($request->has('name')) {
            $token->name = $request->name;
        }

        if ($request->has('abilities')) {
            $abilities = TokenAbilityService::validateAbilities($request->abilities);
            $token->abilities = $abilities;
        }

        $token->save();

        return response()->json([
            'data' => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at,
                'expires_at' => $token->expires_at,
                'updated_at' => $token->updated_at,
            ],
        ]);
    }

    /**
     * Remove the specified token from storage.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $token = $request->user()->tokens()->findOrFail($id);
        $token->delete();

        return response()->json([
            'message' => 'Token deleted successfully',
        ]);
    }

    /**
     * Get available token abilities and scopes.
     */
    public function abilities(): JsonResponse
    {
        return response()->json([
            'data' => [
                'abilities' => TokenAbilityService::getAllAbilities(),
                'scopes' => TokenAbilityService::getAllScopes(),
                'scope_abilities' => collect(TokenAbilityService::getAllScopes())
                    ->mapWithKeys(function ($scope) {
                        return [$scope => TokenAbilityService::getAbilitiesForScope($scope)];
                    }),
            ],
        ]);
    }

    /**
     * Create a token with predefined scope.
     */
    public function createWithScope(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'scope' => 'required|string',
            'expires_at' => 'nullable|date|after:now',
            'password' => 'required|string',
        ]);

        // Verify password for security
        if (!Hash::check($request->password, $request->user()->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.'],
            ]);
        }

        // Validate scope
        if (!TokenAbilityService::scopeExists($request->scope)) {
            throw ValidationException::withMessages([
                'scope' => ['The selected scope is invalid.'],
            ]);
        }

        $abilities = TokenAbilityService::getAbilitiesForScope($request->scope);
        
        $token = $request->user()->createToken(
            $request->name,
            $abilities,
            $request->expires_at ? now()->parse($request->expires_at) : null
        );

        return response()->json([
            'data' => [
                'token' => $token->plainTextToken,
                'name' => $request->name,
                'scope' => $request->scope,
                'abilities' => $abilities,
                'expires_at' => $token->accessToken->expires_at,
            ],
        ], 201);
    }

    /**
     * Revoke all tokens for the authenticated user.
     */
    public function revokeAll(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        // Verify password for security
        if (!Hash::check($request->password, $request->user()->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.'],
            ]);
        }

        $count = $request->user()->tokens()->count();
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => "Successfully revoked {$count} tokens",
        ]);
    }
}