<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * Get users that can be assigned to tickets
     */
    public function assignableUsers(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Check if user has permission to assign tickets
        if (!$user->hasAnyPermission(['tickets.assign', 'admin.write'])) {
            return response()->json([
                'message' => 'Insufficient permissions to view assignable users'
            ], 403);
        }
        
        // Get users who have ticket-related permissions
        $assignableUsers = User::select('id', 'name', 'email')
            ->whereHas('roleTemplates', function ($query) {
                $query->whereHas('permissions', function ($q) {
                    $q->where('name', 'LIKE', 'tickets.%')
                      ->orWhere('name', 'LIKE', 'admin.%')
                      ->orWhere('name', 'LIKE', 'time.%');
                });
            })
            ->orWhere('is_super_admin', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return response()->json([
            'data' => $assignableUsers,
            'message' => 'Assignable users retrieved successfully'
        ]);
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
