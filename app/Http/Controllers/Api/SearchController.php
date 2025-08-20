<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BillingRate;
use App\Models\RoleTemplate;
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
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        // Validate sort field for tickets
        $allowedSortFields = ['created_at', 'updated_at', 'title', 'ticket_number', 'status', 'priority'];
        if (! in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }

        // Validate sort direction
        $sortDirection = in_array(strtolower($sortDirection), ['asc', 'desc']) ? strtolower($sortDirection) : 'desc';

        // Build base query with necessary relationships
        $builder = Ticket::with(['account:id,name', 'assignedUser:id,name'])
            ->select('id', 'ticket_number', 'title', 'status', 'priority', 'account_id', 'agent_id', 'created_at');

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
                $builder->where('agent_id', $user->id);
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
        if (! empty($query)) {
            // Search by ticket number or title (case-insensitive)
            $builder->where(function ($q) use ($query) {
                $q->where('ticket_number', 'ilike', "%{$query}%")
                    ->orWhere('title', 'ilike', "%{$query}%");
            });
            $builder->orderByRaw("
                CASE 
                    WHEN ticket_number ILIKE '{$query}%' THEN 1
                    WHEN title ILIKE '{$query}%' THEN 2
                    ELSE 3
                END, created_at DESC
            ");
        } else {
            // Default: sort by specified field and direction
            $builder->orderBy($sortField, $sortDirection);
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
                    'agent_id' => $ticket->agent_id,
                    'assigned_user' => $ticket->assignedUser,
                    'created_at' => $ticket->created_at,
                ];
            }),
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
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        // Validate sort field for accounts
        $allowedSortFields = ['created_at', 'updated_at', 'name', 'email'];
        if (! in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }

        // Validate sort direction
        $sortDirection = in_array(strtolower($sortDirection), ['asc', 'desc']) ? strtolower($sortDirection) : 'desc';

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
        if (! empty($query)) {
            $builder->where(function ($q) use ($query) {
                $q->where('name', 'ilike', "%{$query}%")
                    ->orWhere('email', 'ilike', "%{$query}%");
            });
            $builder->orderByRaw("
                CASE 
                    WHEN name ILIKE '{$query}%' THEN 1
                    WHEN email ILIKE '{$query}%' THEN 2
                    ELSE 3
                END, name ASC
            ");
        } else {
            // Default: sort by specified field and direction
            $builder->orderBy($sortField, $sortDirection);
        }

        $accounts = $builder->limit($limit)->get();

        $taxService = app(\App\Services\TaxService::class);
        
        return response()->json([
            'data' => $accounts->map(function ($account) use ($taxService) {
                $taxSettings = $taxService->getAccountTaxSettings($account->id);
                
                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'email' => $account->email,
                    'phone' => $account->phone,
                    'is_active' => $account->is_active,
                    'created_at' => $account->created_at,
                    'default_tax_rate' => $taxSettings['tax_rate'],
                    'default_tax_application_mode' => $taxSettings['tax_application_mode'],
                    'tax_exempt' => $taxSettings['tax_exempt'],
                ];
            }),
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
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        // Validate sort field for users
        $allowedSortFields = ['created_at', 'updated_at', 'name', 'email', 'user_type'];
        if (! in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }

        // Validate sort direction
        $sortDirection = in_array(strtolower($sortDirection), ['asc', 'desc']) ? strtolower($sortDirection) : 'desc';

        // Check permissions
        if (! $user->hasAnyPermission(['users.manage', 'users.manage.account', 'admin.manage'])) {
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
        if (! empty($query)) {
            $builder->where(function ($q) use ($query) {
                $q->where('name', 'ilike', "%{$query}%")
                    ->orWhere('email', 'ilike', "%{$query}%");
            });
            $builder->orderByRaw("
                CASE 
                    WHEN name ILIKE '{$query}%' THEN 1
                    WHEN email ILIKE '{$query}%' THEN 2
                    ELSE 3
                END, name ASC
            ");
        } else {
            // Default: sort by specified field and direction
            $builder->orderBy($sortField, $sortDirection);
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
            }),
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
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        // Validate sort field for agents (same as users)
        $allowedSortFields = ['created_at', 'updated_at', 'name', 'email', 'user_type'];
        if (! in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }

        // Validate sort direction
        $sortDirection = in_array(strtolower($sortDirection), ['asc', 'desc']) ? strtolower($sortDirection) : 'desc';

        // Check permissions
        if (! $user->hasAnyPermission(['users.manage', 'users.manage.account', 'admin.manage'])) {
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
        if (! empty($query)) {
            $builder->where(function ($q) use ($query) {
                $q->where('name', 'ilike', "%{$query}%")
                    ->orWhere('email', 'ilike', "%{$query}%");
            });
            $builder->orderByRaw("
                CASE 
                    WHEN name ILIKE '{$query}%' THEN 1
                    WHEN email ILIKE '{$query}%' THEN 2
                    ELSE 3
                END, name ASC
            ");
        } else {
            // Default: sort by specified field and direction
            $builder->orderBy($sortField, $sortDirection);
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
            }),
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
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        // Validate sort field for billing rates
        $allowedSortFields = ['created_at', 'updated_at', 'name', 'rate', 'description'];
        if (! in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }

        // Validate sort direction
        $sortDirection = in_array(strtolower($sortDirection), ['asc', 'desc']) ? strtolower($sortDirection) : 'desc';

        // Check permissions
        if (! $user->hasAnyPermission(['billing.rates.view', 'billing.manage', 'admin.manage'])) {
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
        if (! empty($query)) {
            $builder->where(function ($q) use ($query) {
                $q->where('name', 'ilike', "%{$query}%")
                    ->orWhere('description', 'ilike', "%{$query}%")
                    ->orWhere('rate', 'like', "%{$query}%"); // Keep 'like' for rate since it's numeric
            });
            $builder->orderByRaw("
                CASE 
                    WHEN name ILIKE '{$query}%' THEN 1
                    WHEN description ILIKE '{$query}%' THEN 2
                    ELSE 3
                END, account_id IS NULL, rate DESC
            ");
        } else {
            // Default: sort by specified field and direction
            $builder->orderBy($sortField, $sortDirection);
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
            }),
        ]);
    }

    /**
     * Search role templates for selector dropdown
     */
    public function roleTemplates(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = $request->input('q', '');
        $limit = min($request->input('limit', 30), 100); // Role templates typically have fewer items
        $recent = $request->boolean('recent');
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        // Validate sort field for role templates
        $allowedSortFields = ['created_at', 'updated_at', 'name', 'context'];
        if (! in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }

        // Validate sort direction
        $sortDirection = in_array(strtolower($sortDirection), ['asc', 'desc']) ? strtolower($sortDirection) : 'desc';

        // Check permissions
        if (! $user->hasAnyPermission(['users.manage', 'admin.manage'])) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        // Build base query
        $builder = RoleTemplate::select('id', 'name', 'display_name', 'context', 'description', 'is_system_role', 'is_active', 'created_at')
            ->where('is_active', true);

        // Apply search or default ordering logic
        if (! empty($query)) {
            $builder->where(function ($q) use ($query) {
                $q->where('name', 'ilike', "%{$query}%")
                    ->orWhere('description', 'ilike', "%{$query}%")
                    ->orWhere('context', 'ilike', "%{$query}%");
            });
            $builder->orderByRaw("
                CASE 
                    WHEN name ILIKE '{$query}%' THEN 1
                    WHEN description ILIKE '{$query}%' THEN 2
                    WHEN context ILIKE '{$query}%' THEN 3
                    ELSE 4
                END, name ASC
            ");
        } else {
            // Default: sort by specified field and direction
            $builder->orderBy($sortField, $sortDirection);
        }

        $roleTemplates = $builder->limit($limit)->get();

        return response()->json([
            'data' => $roleTemplates->map(function ($roleTemplate) {
                return [
                    'id' => $roleTemplate->id,
                    'name' => $roleTemplate->name,
                    'display_name' => $roleTemplate->display_name,
                    'context' => $roleTemplate->context,
                    'description' => $roleTemplate->description,
                    'is_system_role' => $roleTemplate->is_system_role,
                    'is_active' => $roleTemplate->is_active,
                    'created_at' => $roleTemplate->created_at,
                ];
            }),
        ]);
    }
}
