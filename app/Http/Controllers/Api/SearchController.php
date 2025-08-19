<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BillingRate;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Search tickets for selector dropdown
     */
    public function tickets(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = $request->input('q', '');
        $limit = min($request->input('limit', 10), 100);
        $recent = $request->boolean('recent');
        $permissionLevel = $request->input('permission_level', 'own');

        // Build base query with necessary relationships
        $builder = Ticket::with(['account:id,name', 'assignedUser:id,name'])
            ->select('id', 'ticket_number', 'title', 'status', 'priority', 'account_id', 'assigned_user_id', 'created_at');

        // Apply permission-based filtering
        switch ($permissionLevel) {
            case 'all':
                // No additional filtering needed
                break;
            case 'account':
                // Filter by accounts user can access
                $accountIds = $user->accounts()->pluck('accounts.id');
                $builder->whereIn('account_id', $accountIds);
                break;
            case 'assigned':
                // Only tickets assigned to the user
                $builder->where('assigned_user_id', $user->id);
                break;
            case 'own':
                // Only tickets created by the user
                $builder->where('created_by', $user->id);
                break;
        }

        // Apply additional filters from request
        if ($request->has('account_id')) {
            $builder->where('account_id', $request->input('account_id'));
        }
        if ($request->has('status')) {
            $builder->where('status', $request->input('status'));
        }

        // Apply search or default ordering logic  
        if (!empty($query)) {
            // Search by ticket number or title
            $builder->where(function ($q) use ($query) {
                $q->where('ticket_number', 'like', "%{$query}%")
                  ->orWhere('title', 'like', "%{$query}%");
            });
            $builder->orderByRaw("
                CASE 
                    WHEN ticket_number LIKE '{$query}%' THEN 1
                    WHEN title LIKE '{$query}%' THEN 2
                    ELSE 3
                END, created_at DESC
            ");
        } else {
            // Default: return last 10 records by created_at
            $builder->orderBy('created_at', 'desc');
        }

        $tickets = $builder->limit($limit)->get();

        return response()->json([
            'data' => $tickets->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'title' => $ticket->title,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'account_id' => $ticket->account_id,
                    'account' => $ticket->account,
                    'assigned_user_id' => $ticket->assigned_user_id,
                    'assigned_user' => $ticket->assignedUser,
                    'created_at' => $ticket->created_at,
                ];
            })
        ]);
    }

    /**
     * Search accounts for selector dropdown
     */
    public function accounts(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = $request->input('q', '');
        $limit = min($request->input('limit', 10), 100);
        $recent = $request->boolean('recent');
        $permissionLevel = $request->input('permission_level', 'own');

        // Build base query
        $builder = Account::select('id', 'name', 'email', 'phone', 'is_active', 'created_at');

        // Apply permission-based filtering
        switch ($permissionLevel) {
            case 'all':
                // No additional filtering needed
                break;
            case 'account':
                // Filter by accounts user can access
                $accountIds = $user->accounts()->pluck('accounts.id');
                $builder->whereIn('id', $accountIds);
                break;
            case 'own':
                // Only accounts where user is a member
                $accountIds = $user->accounts()->pluck('accounts.id');
                $builder->whereIn('id', $accountIds);
                break;
        }

        // Apply additional filters
        if ($request->has('is_active')) {
            $builder->where('is_active', $request->boolean('is_active'));
        }

        // Apply search or default ordering logic
        if (!empty($query)) {
            $builder->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            });
            $builder->orderByRaw("
                CASE 
                    WHEN name LIKE '{$query}%' THEN 1
                    WHEN email LIKE '{$query}%' THEN 2
                    ELSE 3
                END, name ASC
            ");
        } else {
            // Default: return last 10 accounts by created_at
            $builder->orderBy('created_at', 'desc');
        }

        $accounts = $builder->limit($limit)->get();

        return response()->json([
            'data' => $accounts->map(function ($account) {
                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'email' => $account->email,
                    'phone' => $account->phone,
                    'is_active' => $account->is_active,
                    'created_at' => $account->created_at,
                ];
            })
        ]);
    }

    /**
     * Search users for selector dropdown
     */
    public function users(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = $request->input('q', '');
        $limit = min($request->input('limit', 10), 100);
        $recent = $request->boolean('recent');
        $permissionLevel = $request->input('permission_level', 'none');

        // Check permissions
        if (!$user->hasAnyPermission(['users.manage', 'users.manage.account', 'admin.manage'])) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        // Build base query
        $builder = User::select('id', 'name', 'email', 'user_type', 'created_at');

        // Apply permission-based filtering
        switch ($permissionLevel) {
            case 'all':
                // No additional filtering needed
                break;
            case 'account':
                // For now, show all users for account-level permissions
                // This may need to be refined based on actual account relationships
                break;
        }

        // Apply additional filters
        if ($request->has('user_type')) {
            $builder->where('user_type', $request->input('user_type'));
        }
        // Remove account_id filtering for now since accounts relationship may need adjustment

        // Apply search or default ordering logic
        if (!empty($query)) {
            $builder->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            });
            $builder->orderByRaw("
                CASE 
                    WHEN name LIKE '{$query}%' THEN 1
                    WHEN email LIKE '{$query}%' THEN 2
                    ELSE 3
                END, name ASC
            ");
        } else {
            // Default: return last 10 users by created_at
            $builder->orderBy('created_at', 'desc');
        }

        $users = $builder->limit($limit)->get();

        return response()->json([
            'data' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'user_type' => $user->user_type,
                    'created_at' => $user->created_at,
                ];
            })
        ]);
    }

    /**
     * Search agents (users with agent permissions) for selector dropdown
     */
    public function agents(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = $request->input('q', '');
        $limit = min($request->input('limit', 10), 100);
        $recent = $request->boolean('recent');
        $agentType = $request->input('agent_type'); // timer, ticket, time, billing
        $permissionLevel = $request->input('permission_level', 'none');

        // Check permissions
        if (!$user->hasAnyPermission(['users.manage', 'users.manage.account', 'admin.manage'])) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        // Build base query for agent users
        $builder = User::with(['roleTemplate:id,name,permissions'])
            ->select('id', 'name', 'email', 'user_type', 'role_template_id', 'created_at');

        // Filter for users who can act as agents
        $builder->where(function ($q) use ($agentType) {
            // Users with user_type = 'agent' or 'service_provider'
            $q->whereIn('user_type', ['agent', 'service_provider']);
            
            // Or users with specific agent permissions
            if ($agentType) {
                $agentPermission = match ($agentType) {
                    'timer' => 'timers.act_as_agent',
                    'ticket' => 'tickets.act_as_agent', 
                    'time' => 'time.act_as_agent',
                    'billing' => 'billing.act_as_agent',
                    default => 'timers.act_as_agent'
                };
                
                $q->orWhereHas('roleTemplate', function ($roleQuery) use ($agentPermission) {
                    $roleQuery->whereJsonContains('permissions', $agentPermission);
                });
            }
        });

        // Apply permission-based filtering
        switch ($permissionLevel) {
            case 'all':
                // No additional filtering needed
                break;
            case 'account':
                // For now, show all agents for account-level permissions
                // This may need to be refined based on actual account relationships
                break;
        }

        // Apply search or default ordering logic
        if (!empty($query)) {
            $builder->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            });
            $builder->orderByRaw("
                CASE 
                    WHEN name LIKE '{$query}%' THEN 1
                    WHEN email LIKE '{$query}%' THEN 2
                    ELSE 3
                END, name ASC
            ");
        } else {
            // Default: return last 10 agents by created_at
            $builder->orderBy('created_at', 'desc');
        }

        $agents = $builder->limit($limit)->get();

        return response()->json([
            'data' => $agents->map(function ($agent) {
                $permissions = [];
                if ($agent->roleTemplate && $agent->roleTemplate->permissions) {
                    $permissions = $agent->roleTemplate->permissions;
                }

                return [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'email' => $agent->email,
                    'user_type' => $agent->user_type,
                    'permissions' => $permissions,
                    'role_template' => $agent->roleTemplate,
                    'created_at' => $agent->created_at,
                ];
            })
        ]);
    }

    /**
     * Search billing rates for selector dropdown
     */
    public function billingRates(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = $request->input('q', '');
        $limit = min($request->input('limit', 30), 100); // Billing rates typically have fewer items
        $recent = $request->boolean('recent');

        // Check permissions
        if (!$user->hasAnyPermission(['billing.rates.view', 'billing.manage', 'admin.manage'])) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        // Build base query
        $builder = BillingRate::with(['account:id,name'])
            ->select('id', 'name', 'rate', 'account_id', 'description', 'is_active', 'created_at');

        // Apply filters
        if ($request->has('account_id')) {
            $accountId = $request->input('account_id');
            if ($accountId) {
                // Account-specific rates + global rates (null account_id)
                $builder->where(function ($q) use ($accountId) {
                    $q->where('account_id', $accountId)
                      ->orWhereNull('account_id');
                });
            } else {
                // Only global rates
                $builder->whereNull('account_id');
            }
        }

        // Only active rates
        $builder->where('is_active', true);

        // Apply search or default ordering logic
        if (!empty($query)) {
            $builder->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('rate', 'like', "%{$query}%");
            });
            $builder->orderByRaw("
                CASE 
                    WHEN name LIKE '{$query}%' THEN 1
                    WHEN description LIKE '{$query}%' THEN 2
                    ELSE 3
                END, account_id IS NULL, rate DESC
            ");
        } else {
            // Default: return last 10 billing rates by created_at
            $builder->orderBy('created_at', 'desc');
        }

        $billingRates = $builder->limit($limit)->get();

        return response()->json([
            'data' => $billingRates->map(function ($rate) {
                return [
                    'id' => $rate->id,
                    'name' => $rate->name,
                    'rate' => $rate->rate,
                    'account_id' => $rate->account_id,
                    'account' => $rate->account,
                    'description' => $rate->description,
                    'is_active' => $rate->is_active,
                    'is_default' => $rate->account_id === null, // Global rates are "default"
                    'created_at' => $rate->created_at,
                ];
            })
        ]);
    }
}