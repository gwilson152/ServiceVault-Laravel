<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\TicketAddon;
use App\Models\TimeEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingReportController extends Controller
{
    /**
     * Get dashboard billing statistics
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        // Base query for user's accessible data
        $invoiceQuery = Invoice::query();

        // Apply permission-based filtering
        if (! $user->hasAnyPermission(['billing.admin', 'admin.read'])) {
            // Filter to user's accounts
            $userAccountIds = $user->accounts()->pluck('accounts.id')->toArray();
            $invoiceQuery->whereIn('account_id', $userAccountIds);
        }

        // Calculate stats for the last 30 days
        $thirtyDaysAgo = now()->subDays(30);

        $stats = [
            // Invoice statistics
            'total_invoices' => (clone $invoiceQuery)->count(),
            'recent_invoices' => (clone $invoiceQuery)->where('created_at', '>=', $thirtyDaysAgo)->count(),
            'draft_invoices' => (clone $invoiceQuery)->where('status', 'draft')->count(),
            'sent_invoices' => (clone $invoiceQuery)->where('status', 'sent')->count(),
            'paid_invoices' => (clone $invoiceQuery)->where('status', 'paid')->count(),
            'overdue_invoices' => (clone $invoiceQuery)->where('status', 'sent')->where('due_date', '<', now())->count(),

            // Revenue statistics
            'total_revenue' => (clone $invoiceQuery)->where('status', 'paid')->sum('total') ?? 0,
            'recent_revenue' => (clone $invoiceQuery)->where('status', 'paid')->where('created_at', '>=', $thirtyDaysAgo)->sum('total') ?? 0,
            'pending_revenue' => (clone $invoiceQuery)->whereIn('status', ['draft', 'sent'])->sum('total') ?? 0,
            'overdue_amount' => (clone $invoiceQuery)->where('status', 'sent')->where('due_date', '<', now())->sum('total') ?? 0,

            // Time tracking statistics (for unbilled revenue estimation)
            'unbilled_time_entries' => TimeEntry::where('status', 'approved')
                ->where('billable', true)
                ->whereNull('invoice_id')
                ->count(),
            'unbilled_addons' => TicketAddon::where('status', 'approved')
                ->where('billable', true)
                ->whereNull('invoice_id')
                ->count(),
            'pending_approval_items' => TimeEntry::where('status', 'pending')
                ->where('billable', true)
                ->whereNull('invoice_id')
                ->count() +
                TicketAddon::where('status', 'pending')
                    ->where('billable', true)
                    ->whereNull('invoice_id')
                    ->count(),
        ];

        return response()->json([
            'data' => $stats,
            'message' => 'Dashboard statistics retrieved successfully',
        ]);
    }

    /**
     * Get revenue report
     */
    public function revenue(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => 'string|in:week,month,quarter,year',
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
        ]);

        $period = $validated['period'] ?? 'month';
        $startDate = $validated['start_date'] ?? now()->startOfMonth();
        $endDate = $validated['end_date'] ?? now()->endOfMonth();

        $user = $request->user();
        $query = Invoice::where('status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate]);

        // Apply permission-based filtering
        if (! $user->hasAnyPermission(['billing.admin', 'admin.read'])) {
            $userAccountIds = $user->accounts()->pluck('accounts.id')->toArray();
            $query->whereIn('account_id', $userAccountIds);
        }

        $revenue = $query->selectRaw('DATE(created_at) as date, SUM(total) as amount')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'data' => $revenue,
            'message' => 'Revenue report retrieved successfully',
        ]);
    }

    /**
     * Get outstanding invoices report
     */
    public function outstanding(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Invoice::with(['account:id,name'])
            ->whereIn('status', ['draft', 'sent']);

        // Apply permission-based filtering
        if (! $user->hasAnyPermission(['billing.admin', 'admin.read'])) {
            $userAccountIds = $user->accounts()->pluck('accounts.id')->toArray();
            $query->whereIn('account_id', $userAccountIds);
        }

        $outstanding = $query->orderBy('due_date', 'asc')->get();

        return response()->json([
            'data' => $outstanding,
            'message' => 'Outstanding invoices retrieved successfully',
        ]);
    }

    /**
     * Get payments report
     */
    public function payments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
            'limit' => 'integer|min:1|max:100',
        ]);

        $startDate = $validated['start_date'] ?? now()->subMonth();
        $endDate = $validated['end_date'] ?? now();
        $limit = $validated['limit'] ?? 50;

        $user = $request->user();
        $query = Payment::with(['invoice.account:id,name'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        // Apply permission-based filtering through invoice relationship
        if (! $user->hasAnyPermission(['billing.admin', 'admin.read'])) {
            $userAccountIds = $user->accounts()->pluck('accounts.id')->toArray();
            $query->whereHas('invoice', function ($q) use ($userAccountIds) {
                $q->whereIn('account_id', $userAccountIds);
            });
        }

        $payments = $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $payments,
            'message' => 'Payments report retrieved successfully',
        ]);
    }
}
