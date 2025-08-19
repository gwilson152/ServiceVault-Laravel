<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimeEntry;
use App\Models\Ticket;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PortalController extends Controller
{
    /**
     * Get portal dashboard statistics
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        
        // Verify portal access
        if (!$user->hasAnyPermission(['portal.access', 'accounts.view'])) {
            return response()->json(['message' => 'Access denied. Portal access required.'], 403);
        }

        // Get customer's account
        $customerAccount = $user->currentAccount ?? $user->account;
        
        if (!$customerAccount) {
            return response()->json([
                'message' => 'No account access',
                'data' => [
                    'open_tickets' => 0,
                    'closed_tickets' => 0,
                    'hours_this_month' => 0,
                    'total_spent' => 0
                ]
            ]);
        }

        $thisMonth = Carbon::now()->startOfMonth();
        
        $stats = [
            'open_tickets' => Ticket::where('account_id', $customerAccount->id)
                ->whereIn('status', ['open', 'in_progress', 'waiting_customer', 'on_hold'])
                ->count(),
            'closed_tickets' => Ticket::where('account_id', $customerAccount->id)
                ->whereIn('status', ['closed', 'resolved'])
                ->count(),
            'hours_this_month' => TimeEntry::where('account_id', $customerAccount->id)
                ->where('date', '>=', $thisMonth)
                ->sum('duration') / 3600,
            'total_spent' => $this->calculateTotalSpent($customerAccount)
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get recent tickets for portal dashboard
     */
    public function recentTickets(Request $request)
    {
        $user = $request->user();
        
        // Verify portal access
        if (!$user->hasAnyPermission(['portal.access', 'accounts.view'])) {
            return response()->json(['message' => 'Access denied. Portal access required.'], 403);
        }

        // Get customer's account
        $customerAccount = $user->currentAccount ?? $user->account;
        
        if (!$customerAccount) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $recentTickets = Ticket::where('account_id', $customerAccount->id)
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'title' => $ticket->title,
                    'description' => $ticket->description,
                    'status' => $ticket->status,
                    'created_at' => $ticket->created_at,
                    'updated_at' => $ticket->updated_at
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $recentTickets
        ]);
    }

    /**
     * Get recent activity for portal dashboard
     */
    public function recentActivity(Request $request)
    {
        $user = $request->user();
        
        // Verify portal access
        if (!$user->hasAnyPermission(['portal.access', 'accounts.view'])) {
            return response()->json(['message' => 'Access denied. Portal access required.'], 403);
        }

        // Get customer's account
        $customerAccount = $user->currentAccount ?? $user->account;
        
        if (!$customerAccount) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $recentActivity = collect();

        // Get recent time entries
        $recentTimeEntries = TimeEntry::where('account_id', $customerAccount->id)
            ->with(['user:id,name', 'ticket:id,title,ticket_number'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => 'time_entry_' . $entry->id,
                    'type' => 'time_entry',
                    'description' => ($entry->user->name ?? 'Unknown') . ' logged ' . 
                                   round($entry->duration / 3600, 2) . ' hours on ' . 
                                   ($entry->ticket->title ?? 'General Work'),
                    'created_at' => $entry->created_at
                ];
            });

        // Get recent ticket updates
        $recentTicketUpdates = Ticket::where('account_id', $customerAccount->id)
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($ticket) {
                return [
                    'id' => 'ticket_' . $ticket->id,
                    'type' => 'ticket_update',
                    'description' => 'Ticket "' . $ticket->title . '" status updated to ' . 
                                   ucfirst(str_replace('_', ' ', $ticket->status)),
                    'created_at' => $ticket->updated_at
                ];
            });

        // Combine and sort activities
        $allActivity = $recentTimeEntries->concat($recentTicketUpdates)
            ->sortByDesc('created_at')
            ->take(10)
            ->values();

        return response()->json([
            'success' => true,
            'data' => $allActivity
        ]);
    }

    /**
     * Calculate total spent for account
     */
    private function calculateTotalSpent($account): float
    {
        // Calculate from paid invoices if available
        $totalFromInvoices = Invoice::where('account_id', $account->id)
            ->where('status', 'paid')
            ->sum('total_amount');

        if ($totalFromInvoices > 0) {
            return $totalFromInvoices;
        }

        // Fallback: calculate from billable time entries
        $billableHours = TimeEntry::where('account_id', $account->id)
            ->where('billable', true)
            ->sum('duration') / 3600;

        // Use a default rate if no billing rate is set
        $defaultRate = 100; // $100/hour default
        return $billableHours * $defaultRate;
    }
}
