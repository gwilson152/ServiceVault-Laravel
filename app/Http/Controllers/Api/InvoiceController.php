<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\TimeEntry;
use App\Models\TicketAddon;
use App\Models\BillingSetting;
use App\Http\Resources\InvoiceResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices with filters and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Build base query with user's accessible accounts
        $query = Invoice::with(['account:id,name', 'user:id,name', 'lineItems', 'payments'])
                       ->orderBy('created_at', 'desc');
        
        // Apply user scope - employees see their account invoices, managers see managed accounts
        if (!$user->hasAnyPermission(['admin.read', 'billing.admin'])) {
            $accessibleAccountIds = $user->accounts()->pluck('accounts.id');
            $query->whereIn('account_id', $accessibleAccountIds);
        }
        
        // Apply filters
        $query->when($request->status, function ($q, $status) {
            if ($status === 'overdue') {
                $q->overdue();
            } else {
                $q->where('status', $status);
            }
        })->when($request->account_id, function ($q, $accountId) {
            $q->where('account_id', $accountId);
        })->when($request->date_from, function ($q, $dateFrom) {
            $q->whereDate('invoice_date', '>=', $dateFrom);
        })->when($request->date_to, function ($q, $dateTo) {
            $q->whereDate('invoice_date', '<=', $dateTo);
        });
        
        // Get paginated results
        $invoices = $query->paginate($request->input('per_page', 20));
        
        // Calculate summary statistics
        $stats = $this->calculateInvoiceStats($query->clone());
        
        return response()->json([
            'data' => InvoiceResource::collection($invoices),
            'meta' => array_merge($invoices->toArray(), ['stats' => $stats])
        ]);
    }

    /**
     * Store a newly created invoice
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after:invoice_date',
            'time_entry_ids' => 'nullable|array',
            'time_entry_ids.*' => 'exists:time_entries,id',
            'ticket_addon_ids' => 'nullable|array', 
            'ticket_addon_ids.*' => 'exists:ticket_addons,id',
            'manual_line_items' => 'nullable|array',
            'manual_line_items.*.description' => 'required|string',
            'manual_line_items.*.quantity' => 'required|numeric|min:0.01',
            'manual_line_items.*.unit_price' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'auto_send' => 'boolean'
        ]);
        
        // Verify user has access to account
        if (!$user->accounts()->where('accounts.id', $validated['account_id'])->exists() && 
            !$user->hasAnyPermission(['admin.write', 'billing.admin'])) {
            return response()->json(['error' => 'You do not have access to this account.'], 403);
        }
        
        DB::beginTransaction();
        try {
            // Create invoice
            $invoice = Invoice::create([
                'account_id' => $validated['account_id'],
                'user_id' => $user->id,
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'status' => 'draft',
                'tax_rate' => $validated['tax_rate'] ?? 0,
                'notes' => $validated['notes'],
            ]);
            
            // Add time entries as line items
            if (!empty($validated['time_entry_ids'])) {
                $timeEntries = TimeEntry::whereIn('id', $validated['time_entry_ids'])
                                       ->where('status', 'approved')
                                       ->where('billable', true)
                                       ->get();
                                       
                foreach ($timeEntries as $entry) {
                    InvoiceLineItem::create([
                        'invoice_id' => $invoice->id,
                        'time_entry_id' => $entry->id,
                        'line_type' => 'time_entry',
                        'description' => $entry->description,
                        'quantity' => $entry->duration / 60, // Convert minutes to hours
                        'unit_price' => $entry->rate_at_time ?? 0,
                        'total_amount' => $entry->calculated_cost ?? 0,
                    ]);
                    
                    // Mark time entry as invoiced
                    $entry->update(['invoice_id' => $invoice->id]);
                }
            }
            
            // Add ticket addons as line items
            if (!empty($validated['ticket_addon_ids'])) {
                $addons = TicketAddon::whereIn('id', $validated['ticket_addon_ids'])
                                    ->where('status', 'approved')
                                    ->where('billable', true)
                                    ->get();
                                    
                foreach ($addons as $addon) {
                    InvoiceLineItem::create([
                        'invoice_id' => $invoice->id,
                        'ticket_addon_id' => $addon->id,
                        'line_type' => 'addon',
                        'description' => $addon->name . ($addon->description ? ' - ' . $addon->description : ''),
                        'quantity' => $addon->quantity,
                        'unit_price' => $addon->unit_price,
                        'discount_amount' => $addon->discount_amount ?? 0,
                        'total_amount' => $addon->total_amount,
                    ]);
                    
                    // Mark addon as invoiced
                    $addon->update(['invoice_id' => $invoice->id]);
                }
            }
            
            // Add manual line items
            if (!empty($validated['manual_line_items'])) {
                foreach ($validated['manual_line_items'] as $item) {
                    InvoiceLineItem::create([
                        'invoice_id' => $invoice->id,
                        'line_type' => 'manual',
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_amount' => $item['quantity'] * $item['unit_price'],
                    ]);
                }
            }
            
            // Calculate totals
            $invoice->calculateTotals();
            $invoice->save();
            
            // Auto-send if requested
            if ($validated['auto_send'] ?? false) {
                $invoice->update(['status' => 'sent']);
                // TODO: Queue email job
            }
            
            DB::commit();
            
            $invoice->load(['account', 'user', 'lineItems', 'payments']);
            
            return response()->json([
                'data' => new InvoiceResource($invoice),
                'message' => 'Invoice created successfully.'
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create invoice: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified invoice
     */
    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $user = $request->user();
        
        // Check access
        if (!$user->accounts()->where('accounts.id', $invoice->account_id)->exists() &&
            !$user->hasAnyPermission(['admin.read', 'billing.admin'])) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }
        
        $invoice->load(['account', 'user', 'lineItems.timeEntry', 'lineItems.ticketAddon', 'payments']);
        
        return response()->json([
            'data' => new InvoiceResource($invoice)
        ]);
    }

    /**
     * Update the specified invoice
     */
    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        $user = $request->user();
        
        // Check permissions
        if (!$user->accounts()->where('accounts.id', $invoice->account_id)->exists() &&
            !$user->hasAnyPermission(['admin.write', 'billing.admin'])) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }
        
        // Can only edit draft invoices
        if ($invoice->status !== 'draft') {
            return response()->json(['error' => 'Only draft invoices can be edited.'], 422);
        }
        
        $validated = $request->validate([
            'invoice_date' => 'sometimes|date',
            'due_date' => 'sometimes|date|after:invoice_date',
            'tax_rate' => 'sometimes|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:draft,sent,cancelled'
        ]);
        
        $invoice->update($validated);
        $invoice->calculateTotals();
        $invoice->save();
        
        $invoice->load(['account', 'user', 'lineItems', 'payments']);
        
        return response()->json([
            'data' => new InvoiceResource($invoice),
            'message' => 'Invoice updated successfully.'
        ]);
    }

    /**
     * Remove the specified invoice
     */
    public function destroy(Request $request, Invoice $invoice): JsonResponse
    {
        $user = $request->user();
        
        // Check permissions
        if (!$user->hasAnyPermission(['admin.write', 'billing.admin'])) {
            return response()->json(['error' => 'Insufficient permissions.'], 403);
        }
        
        // Can only delete draft or cancelled invoices
        if (!in_array($invoice->status, ['draft', 'cancelled'])) {
            return response()->json(['error' => 'Only draft or cancelled invoices can be deleted.'], 422);
        }
        
        // Remove invoice associations from time entries and addons
        TimeEntry::where('invoice_id', $invoice->id)->update(['invoice_id' => null]);
        TicketAddon::where('invoice_id', $invoice->id)->update(['invoice_id' => null]);
        
        $invoice->delete();
        
        return response()->json([
            'message' => 'Invoice deleted successfully.'
        ]);
    }
    
    /**
     * Send invoice via email
     */
    public function send(Request $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->status !== 'draft') {
            return response()->json(['error' => 'Only draft invoices can be sent.'], 422);
        }
        
        $invoice->update(['status' => 'sent']);
        
        // TODO: Queue email job
        
        return response()->json([
            'data' => new InvoiceResource($invoice),
            'message' => 'Invoice sent successfully.'
        ]);
    }
    
    /**
     * Mark invoice as paid
     */
    public function markPaid(Request $request, Invoice $invoice): JsonResponse
    {
        $validated = $request->validate([
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'payment_reference' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);
        
        DB::beginTransaction();
        try {
            // Create payment record
            $payment = $invoice->payments()->create([
                'account_id' => $invoice->account_id,
                'amount' => $validated['payment_amount'],
                'net_amount' => $validated['payment_amount'],
                'payment_method' => $validated['payment_method'],
                'payment_reference' => $validated['payment_reference'],
                'payment_date' => $validated['payment_date'],
                'status' => 'completed',
                'notes' => $validated['notes'],
                'processed_at' => now(),
            ]);
            
            // Check if invoice is fully paid
            $totalPaid = $invoice->payments()->where('status', 'completed')->sum('amount');
            if ($totalPaid >= $invoice->total) {
                $invoice->update(['status' => 'paid']);
            }
            
            DB::commit();
            
            $invoice->load(['account', 'user', 'lineItems', 'payments']);
            
            return response()->json([
                'data' => new InvoiceResource($invoice),
                'message' => 'Payment recorded successfully.'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to record payment: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Generate PDF invoice
     */
    public function pdf(Invoice $invoice)
    {
        // TODO: Implement PDF generation
        return response()->json(['message' => 'PDF generation not implemented yet.']);
    }
    
    /**
     * Calculate invoice statistics
     */
    private function calculateInvoiceStats($query): array
    {
        $baseQuery = $query->whereDate('created_at', '>=', now()->subDays(30));
        
        return [
            'total_invoices' => (clone $baseQuery)->count(),
            'draft_invoices' => (clone $baseQuery)->where('status', 'draft')->count(),
            'sent_invoices' => (clone $baseQuery)->where('status', 'sent')->count(),
            'paid_invoices' => (clone $baseQuery)->where('status', 'paid')->count(),
            'overdue_invoices' => (clone $baseQuery)->overdue()->count(),
            'total_billed' => (clone $baseQuery)->sum('total') ?? 0,
            'total_paid' => (clone $baseQuery)->paid()->sum('total') ?? 0,
            'outstanding_amount' => (clone $baseQuery)->whereNotIn('status', ['paid', 'cancelled'])->sum('total') ?? 0,
        ];
    }
}
