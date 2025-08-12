<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Account;
use App\Models\RoleTemplate;
use App\Models\UserInvitation;
use App\Notifications\UserInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class UserInvitationController extends Controller
{
    /**
     * Display a listing of invitations (Admin/Manager access)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Check permissions
        if (!$user->hasAnyPermission(['admin.manage', 'teams.manage'])) {
            return response()->json(['error' => 'Insufficient permissions to view invitations.'], 403);
        }
        
        $query = UserInvitation::with(['invitedBy:id,name,email', 'account:id,name', 'roleTemplate:id,name', 'acceptedBy:id,name']);
        
        // Managers can only see invitations for accounts they manage
        if (!$user->hasPermission('admin.manage')) {
            $managedAccountIds = $user->accounts()
                ->whereHas('users', function ($userQuery) use ($user) {
                    $userQuery->where('users.id', $user->id);
                })
                ->pluck('accounts.id');
            $query->whereIn('account_id', $managedAccountIds);
        }
        
        // Apply filters
        $query->when($request->status, function ($q, $status) {
            $q->where('status', $status);
        })->when($request->account_id, function ($q, $accountId) {
            $q->where('account_id', $accountId);
        })->when($request->role_template_id, function ($q, $roleTemplateId) {
            $q->where('role_template_id', $roleTemplateId);
        });
        
        $invitations = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return response()->json([
            'data' => $invitations->items(),
            'meta' => [
                'current_page' => $invitations->currentPage(),
                'per_page' => $invitations->perPage(),
                'total' => $invitations->total(),
            ]
        ]);
    }

    /**
     * Send a new user invitation
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Check permissions
        if (!$user->hasAnyPermission(['admin.manage', 'teams.manage'])) {
            return response()->json(['error' => 'Insufficient permissions to send invitations.'], 403);
        }
        
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'invited_name' => 'nullable|string|max:255',
            'account_id' => 'required|exists:accounts,id',
            'role_template_id' => 'required|exists:role_templates,id',
            'message' => 'nullable|string|max:2000',
            'expires_days' => 'nullable|integer|min:1|max:30'
        ]);
        
        // Check if user already exists
        if (User::where('email', $validated['email'])->exists()) {
            return response()->json(['error' => 'User with this email already exists in the system.'], 422);
        }
        
        // Check if there's already a pending invitation for this email/account combo
        $existingInvitation = UserInvitation::where('email', $validated['email'])
            ->where('account_id', $validated['account_id'])
            ->where('status', 'pending')
            ->first();
            
        if ($existingInvitation && !$existingInvitation->isExpired()) {
            return response()->json(['error' => 'A pending invitation already exists for this email and account.'], 422);
        }
        
        // Verify manager has access to this account (if not admin)
        if (!$user->hasPermission('admin.manage')) {
            if (!$user->accounts()->where('accounts.id', $validated['account_id'])->exists()) {
                return response()->json(['error' => 'You do not have access to invite users to this account.'], 403);
            }
        }
        
        // Create the invitation
        $invitation = UserInvitation::create([
            'email' => $validated['email'],
            'token' => UserInvitation::generateToken(),
            'invited_by_user_id' => $user->id,
            'account_id' => $validated['account_id'],
            'role_template_id' => $validated['role_template_id'],
            'invited_name' => $validated['invited_name'],
            'message' => $validated['message'],
            'status' => 'pending',
            'expires_at' => now()->addDays($validated['expires_days'] ?? 7),
        ]);
        
        $invitation->load(['invitedBy', 'account', 'roleTemplate']);
        
        // Send email notification
        try {
            \Illuminate\Support\Facades\Notification::route('mail', $invitation->email)
                ->notify(new UserInvitationNotification($invitation));
        } catch (\Exception $e) {
            // Log the error but don't fail the invitation creation
            \Log::error('Failed to send invitation email', [
                'invitation_id' => $invitation->id,
                'error' => $e->getMessage()
            ]);
        }
        
        return response()->json([
            'data' => $invitation,
            'message' => 'Invitation sent successfully.'
        ], 201);
    }

    /**
     * Display a specific invitation
     */
    public function show(Request $request, UserInvitation $invitation): JsonResponse
    {
        $user = $request->user();
        
        // Check permissions - admin/manager access or if it's their own invitation
        if (!$user->hasAnyPermission(['admin.manage', 'teams.manage']) &&
            $invitation->invited_by_user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }
        
        $invitation->load(['invitedBy:id,name,email', 'account:id,name', 'roleTemplate:id,name,permissions', 'acceptedBy:id,name']);
        
        return response()->json([
            'data' => $invitation
        ]);
    }

    /**
     * Cancel/resend an invitation
     */
    public function update(Request $request, UserInvitation $invitation): JsonResponse
    {
        $user = $request->user();
        
        // Check permissions
        if (!$user->hasAnyPermission(['admin.manage', 'teams.manage']) &&
            $invitation->invited_by_user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }
        
        $validated = $request->validate([
            'action' => 'required|in:cancel,resend',
            'message' => 'nullable|string|max:2000',
            'expires_days' => 'nullable|integer|min:1|max:30'
        ]);
        
        if ($validated['action'] === 'cancel') {
            if ($invitation->status !== 'pending') {
                return response()->json(['error' => 'Only pending invitations can be cancelled.'], 422);
            }
            
            $invitation->update(['status' => 'cancelled']);
            
            return response()->json([
                'data' => $invitation,
                'message' => 'Invitation cancelled successfully.'
            ]);
        }
        
        if ($validated['action'] === 'resend') {
            if ($invitation->status !== 'pending') {
                return response()->json(['error' => 'Only pending invitations can be resent.'], 422);
            }
            
            // Update expiration and regenerate token
            $invitation->update([
                'token' => UserInvitation::generateToken(),
                'expires_at' => now()->addDays($validated['expires_days'] ?? 7),
                'message' => $validated['message'] ?? $invitation->message,
            ]);
            
            // Resend email
            try {
                \Illuminate\Support\Facades\Notification::route('mail', $invitation->email)
                    ->notify(new UserInvitationNotification($invitation));
            } catch (\Exception $e) {
                \Log::error('Failed to resend invitation email', [
                    'invitation_id' => $invitation->id,
                    'error' => $e->getMessage()
                ]);
                return response()->json(['error' => 'Failed to send invitation email.'], 500);
            }
            
            return response()->json([
                'data' => $invitation,
                'message' => 'Invitation resent successfully.'
            ]);
        }
    }

    /**
     * Delete an invitation
     */
    public function destroy(Request $request, UserInvitation $invitation): JsonResponse
    {
        $user = $request->user();
        
        // Check permissions - only admins can delete invitations
        if (!$user->hasPermission('admin.manage')) {
            return response()->json(['error' => 'Only administrators can delete invitations.'], 403);
        }
        
        $invitation->delete();
        
        return response()->json([
            'message' => 'Invitation deleted successfully.'
        ]);
    }

    /**
     * Accept an invitation (public endpoint)
     */
    public function accept(Request $request, string $token): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);
        
        $invitation = UserInvitation::where('token', $token)
            ->where('status', 'pending')
            ->first();
        
        if (!$invitation) {
            return response()->json(['error' => 'Invalid or expired invitation.'], 404);
        }
        
        if ($invitation->isExpired()) {
            $invitation->update(['status' => 'expired']);
            return response()->json(['error' => 'This invitation has expired.'], 410);
        }
        
        // Check if user already exists
        if (User::where('email', $invitation->email)->exists()) {
            return response()->json(['error' => 'User with this email already exists.'], 422);
        }
        
        DB::beginTransaction();
        
        try {
            // Create the user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $invitation->email,
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(),
                'current_account_id' => $invitation->account_id,
                'is_active' => true,
            ]);
            
            // Assign the user to the account
            $user->accounts()->attach($invitation->account_id);
            
            // Assign the role template
            $user->update([
                'role_template_id' => $invitation->role_template_id
            ]);
            
            // Mark invitation as accepted
            $invitation->markAsAccepted($user);
            
            DB::commit();
            
            return response()->json([
                'message' => 'Account created successfully! You can now log in.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Failed to accept invitation', [
                'invitation_id' => $invitation->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['error' => 'Failed to create account. Please try again.'], 500);
        }
    }

    /**
     * Get invitation by token (public endpoint for invitation form)
     */
    public function showByToken(string $token): JsonResponse
    {
        $invitation = UserInvitation::where('token', $token)
            ->where('status', 'pending')
            ->with(['invitedBy:id,name', 'account:id,name', 'roleTemplate:id,name'])
            ->first();
        
        if (!$invitation) {
            return response()->json(['error' => 'Invalid or expired invitation.'], 404);
        }
        
        if ($invitation->isExpired()) {
            $invitation->update(['status' => 'expired']);
            return response()->json(['error' => 'This invitation has expired.'], 410);
        }
        
        // Don't expose sensitive data
        return response()->json([
            'data' => [
                'email' => $invitation->email,
                'invited_name' => $invitation->invited_name,
                'account' => $invitation->account,
                'role_template' => $invitation->roleTemplate,
                'invited_by' => $invitation->invitedBy,
                'message' => $invitation->message,
                'expires_at' => $invitation->expires_at,
            ]
        ]);
    }
}
