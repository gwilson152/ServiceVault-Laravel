<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\TicketAddon;
use App\Models\TimeEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        if (! $user->hasAnyPermission(['admin.read', 'billing.admin'])) {
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
            'meta' => array_merge($invoices->toArray(), ['stats' => $stats]),
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
            'due_date' => 'nullable|date|after:invoice_date',
            'time_entry_ids' => 'nullable|array',
            'time_entry_ids.*' => 'exists:time_entries,id',
            'ticket_addon_ids' => 'nullable|array',
            'ticket_addon_ids.*' => 'exists:ticket_addons,id',
            'manual_line_items' => 'nullable|array',
            'manual_line_items.*.description' => 'required|string',
            'manual_line_items.*.quantity' => 'required|numeric|min:0.01',
            'manual_line_items.*.unit_price' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'tax_application_mode' => 'nullable|in:all_items,non_service_items,custom',
            'override_tax' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'auto_send' => 'boolean',
        ]);

        // Verify user has access to account
        if (! $user->accounts()->where('accounts.id', $validated['account_id'])->exists() &&
            ! $user->hasAnyPermission(['admin.write', 'billing.admin'])) {
            return response()->json(['error' => 'You do not have access to this account.'], 403);
        }

        // Get tax settings using TaxService hierarchy
        $taxService = app(\App\Services\TaxService::class);
        $account = \App\Models\Account::find($validated['account_id']);
        
        $taxRate = $taxService->getEffectiveTaxRate($validated['account_id'], $validated['tax_rate'] ?? null);
        $taxApplicationMode = $taxService->getEffectiveTaxApplicationMode($validated['account_id'], $validated['tax_application_mode'] ?? null);
        
        // If tax preferences were specified and differ from current account settings, update account settings
        if (isset($validated['tax_rate']) || isset($validated['tax_application_mode'])) {
            $accountTaxSettings = [];
            if (isset($validated['tax_rate'])) {
                $accountTaxSettings['default_rate'] = $validated['tax_rate'];
            }
            if (isset($validated['tax_application_mode'])) {
                $accountTaxSettings['default_application_mode'] = $validated['tax_application_mode'];
            }
            
            if (!empty($accountTaxSettings)) {
                $taxService->setAccountTaxSettings($validated['account_id'], $accountTaxSettings);
            }
        }

        DB::beginTransaction();
        try {
            // Create invoice with inherited/updated tax preferences
            $invoice = Invoice::create([
                'account_id' => $validated['account_id'],
                'user_id' => $user->id,
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'status' => 'draft',
                'tax_rate' => $taxRate,
                'tax_application_mode' => $taxApplicationMode,
                'override_tax' => $validated['override_tax'] ?? false,
                'notes' => $validated['notes'],
            ]);

            // Add time entries as line items
            if (! empty($validated['time_entry_ids'])) {
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
            if (! empty($validated['ticket_addon_ids'])) {
                $addons = TicketAddon::whereIn('id', $validated['ticket_addon_ids'])
                    ->where('status', 'approved')
                    ->where('billable', true)
                    ->get();

                foreach ($addons as $addon) {
                    InvoiceLineItem::create([
                        'invoice_id' => $invoice->id,
                        'ticket_addon_id' => $addon->id,
                        'line_type' => 'addon',
                        'description' => $addon->name.($addon->description ? ' - '.$addon->description : ''),
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
            if (! empty($validated['manual_line_items'])) {
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
                'message' => 'Invoice created successfully.',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Failed to create invoice: '.$e->getMessage()], 500);
        }
    }

    /**
     * Display the specified invoice
     */
    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $user = $request->user();

        // Check access
        if (! $user->accounts()->where('accounts.id', $invoice->account_id)->exists() &&
            ! $user->hasAnyPermission(['admin.read', 'billing.admin'])) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        $invoice->load(['account', 'user', 'lineItems.timeEntry', 'lineItems.ticketAddon', 'payments']);

        return response()->json([
            'data' => new InvoiceResource($invoice),
        ]);
    }

    /**
     * Update the specified invoice
     */
    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        $user = $request->user();

        // Check permissions
        if (! $user->accounts()->where('accounts.id', $invoice->account_id)->exists() &&
            ! $user->hasAnyPermission(['admin.write', 'billing.admin'])) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        // Can only edit draft invoices, except for status changes
        if ($invoice->status !== 'draft' && !$request->has('status')) {
            return response()->json(['error' => 'Only draft invoices can be edited.'], 422);
        }
        
        // Allow status changes: draft -> sent/cancelled, sent -> draft
        if ($request->has('status')) {
            $newStatus = $validated['status'] ?? $request->input('status');
            
            // Define allowed status transitions
            $allowedTransitions = [
                'draft' => ['sent', 'cancelled'],
                'sent' => ['draft'],  // Allow reverting sent invoices back to draft
            ];
            
            $currentStatus = $invoice->status;
            if (!isset($allowedTransitions[$currentStatus]) || 
                !in_array($newStatus, $allowedTransitions[$currentStatus])) {
                return response()->json([
                    'error' => "Cannot change invoice status from '{$currentStatus}' to '{$newStatus}'."
                ], 422);
            }
        }

        $validated = $request->validate([
            'invoice_date' => 'sometimes|date',
            'due_date' => 'sometimes|nullable|date|after:invoice_date',
            'tax_rate' => 'sometimes|numeric|min:0|max:100',
            'tax_application_mode' => 'sometimes|in:all_items,non_service_items,custom',
            'override_tax' => 'sometimes|boolean',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:draft,sent,cancelled',
        ]);

        // Update account tax preferences if changed using TaxService
        if (isset($validated['tax_rate']) || isset($validated['tax_application_mode'])) {
            $taxService = app(\App\Services\TaxService::class);
            $accountTaxSettings = [];
            
            if (isset($validated['tax_rate'])) {
                $accountTaxSettings['default_rate'] = $validated['tax_rate'];
            }
            
            if (isset($validated['tax_application_mode'])) {
                $accountTaxSettings['default_application_mode'] = $validated['tax_application_mode'];
            }
            
            if (!empty($accountTaxSettings)) {
                $taxService->setAccountTaxSettings($invoice->account_id, $accountTaxSettings);
            }
        }

        $invoice->update($validated);
        $invoice->calculateTotals();
        $invoice->save();

        $invoice->load(['account', 'user', 'lineItems', 'payments']);

        return response()->json([
            'data' => new InvoiceResource($invoice),
            'message' => 'Invoice updated successfully.',
        ]);
    }

    /**
     * Remove the specified invoice
     */
    public function destroy(Request $request, Invoice $invoice): JsonResponse
    {
        $user = $request->user();

        // Check permissions
        if (! $user->hasAnyPermission(['admin.write', 'billing.admin'])) {
            return response()->json(['error' => 'Insufficient permissions.'], 403);
        }

        // Can only delete draft or cancelled invoices
        if (! in_array($invoice->status, ['draft', 'cancelled'])) {
            return response()->json(['error' => 'Only draft or cancelled invoices can be deleted.'], 422);
        }

        // Remove invoice associations from time entries and addons
        TimeEntry::where('invoice_id', $invoice->id)->update(['invoice_id' => null]);
        TicketAddon::where('invoice_id', $invoice->id)->update(['invoice_id' => null]);

        $invoice->delete();

        return response()->json([
            'message' => 'Invoice deleted successfully.',
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
            'message' => 'Invoice sent successfully.',
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
            'notes' => 'nullable|string',
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
                'message' => 'Payment recorded successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Failed to record payment: '.$e->getMessage()], 500);
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
     * Get unbilled items for invoice generation
     */
    public function unbilledItems(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'include_unapproved' => 'sometimes|in:true,false,1,0',
        ]);

        $accountId = $validated['account_id'];
        $includeUnapproved = filter_var($validated['include_unapproved'] ?? true, FILTER_VALIDATE_BOOLEAN);

        // Verify user has access to account
        if (! $user->accounts()->where('accounts.id', $accountId)->exists() &&
            ! $user->hasAnyPermission(['admin.read', 'billing.admin'])) {
            return response()->json(['error' => 'You do not have access to this account.'], 403);
        }

        // Get effective tax application mode for this account
        $taxService = app(\App\Services\TaxService::class);
        $effectiveTaxApplicationMode = $taxService->getEffectiveTaxApplicationMode($accountId);

        // Determine if time entries should be taxable based on effective tax application mode
        $timeEntryTaxable = match($effectiveTaxApplicationMode) {
            'all_items' => true,           // Time entries are taxable by default
            'non_service_items' => false,  // Time entries are never taxed
            'custom' => false,             // Only explicitly marked items are taxable
            default => true                // Default to all items behavior
        };

        // Get approved time entries
        $approvedTimeEntries = TimeEntry::with(['user:id,name', 'ticket:id,ticket_number,title', 'billingRate:id,name,rate'])
            ->where('account_id', $accountId)
            ->where('status', 'approved')
            ->where('billable', true)
            ->whereNull('invoice_id')
            ->orderBy('started_at', 'desc')
            ->get()
            ->map(function ($entry) use ($timeEntryTaxable) {
                return [
                    'id' => $entry->id,
                    'type' => 'time_entry',
                    'description' => $entry->description,
                    'quantity' => round($entry->duration / 60, 2), // Convert minutes to hours
                    'unit_price' => $entry->rate_at_time ?? 0,
                    'total_amount' => $entry->calculated_cost ?? 0,
                    'started_at' => $entry->started_at,
                    'user' => $entry->user,
                    'ticket' => $entry->ticket,
                    'billing_rate' => $entry->billingRate,
                    'status' => $entry->status,
                    'is_taxable' => $timeEntryTaxable, // Based on effective tax application mode
                ];
            });

        // Get approved ticket addons
        $approvedAddons = TicketAddon::with(['ticket:id,ticket_number,title', 'addedBy:id,name'])
            ->whereHas('ticket', function ($q) use ($accountId) {
                $q->where('account_id', $accountId);
            })
            ->where('status', 'approved')
            ->where('billable', true)
            ->whereNull('invoice_id')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($addon) {
                return [
                    'id' => $addon->id,
                    'type' => 'ticket_addon',
                    'description' => $addon->name.($addon->description ? ' - '.$addon->description : ''),
                    'quantity' => $addon->quantity,
                    'unit_price' => $addon->unit_price,
                    'total_amount' => $addon->total_amount,
                    'created_at' => $addon->created_at,
                    'ticket' => $addon->ticket,
                    'added_by' => $addon->addedBy,
                    'status' => $addon->status,
                    'is_taxable' => $addon->is_taxable,
                    'tax_rate' => $addon->tax_rate,
                ];
            });

        $result = [
            'approved' => [
                'time_entries' => $approvedTimeEntries,
                'ticket_addons' => $approvedAddons,
            ],
        ];

        // Include unapproved items if requested
        if ($includeUnapproved) {
            // Get pending time entries
            $pendingTimeEntries = TimeEntry::with(['user:id,name', 'ticket:id,ticket_number,title', 'billingRate:id,name,rate'])
                ->where('account_id', $accountId)
                ->where('status', 'pending')
                ->where('billable', true)
                ->whereNull('invoice_id')
                ->orderBy('started_at', 'desc')
                ->get()
                ->map(function ($entry) use ($timeEntryTaxable) {
                    return [
                        'id' => $entry->id,
                        'type' => 'time_entry',
                        'description' => $entry->description,
                        'quantity' => round($entry->duration / 60, 2),
                        'unit_price' => $entry->rate_at_time ?? 0,
                        'total_amount' => $entry->calculated_cost ?? 0,
                        'started_at' => $entry->started_at,
                        'user' => $entry->user,
                        'ticket' => $entry->ticket,
                        'billing_rate' => $entry->billingRate,
                        'status' => $entry->status,
                        'is_taxable' => $timeEntryTaxable, // Based on effective tax application mode
                    ];
                });

            // Get pending ticket addons
            $pendingAddons = TicketAddon::with(['ticket:id,ticket_number,title', 'addedBy:id,name'])
                ->whereHas('ticket', function ($q) use ($accountId) {
                    $q->where('account_id', $accountId);
                })
                ->where('status', 'pending')
                ->where('billable', true)
                ->whereNull('invoice_id')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($addon) {
                    return [
                        'id' => $addon->id,
                        'type' => 'ticket_addon',
                        'description' => $addon->name.($addon->description ? ' - '.$addon->description : ''),
                        'quantity' => $addon->quantity,
                        'unit_price' => $addon->unit_price,
                        'total_amount' => $addon->total_amount,
                        'created_at' => $addon->created_at,
                        'ticket' => $addon->ticket,
                        'added_by' => $addon->addedBy,
                        'status' => $addon->status,
                        'is_taxable' => $addon->is_taxable,
                        'tax_rate' => $addon->tax_rate,
                    ];
                });

            $result['unapproved'] = [
                'time_entries' => $pendingTimeEntries,
                'ticket_addons' => $pendingAddons,
            ];
        }

        // Calculate totals
        $approvedAmount = $approvedTimeEntries->sum('total_amount') + $approvedAddons->sum('total_amount');
        $pendingAmount = 0;

        if ($includeUnapproved && isset($result['unapproved'])) {
            $pendingAmount = $result['unapproved']['time_entries']->sum('total_amount') +
                           $result['unapproved']['ticket_addons']->sum('total_amount');
        }

        $result['totals'] = [
            'approved_amount' => round($approvedAmount, 2),
            'pending_amount' => round($pendingAmount, 2),
            'approved_count' => $approvedTimeEntries->count() + $approvedAddons->count(),
            'pending_count' => $includeUnapproved ?
                (($result['unapproved']['time_entries']->count() ?? 0) + ($result['unapproved']['ticket_addons']->count() ?? 0)) : 0,
        ];

        return response()->json([
            'data' => $result,
            'message' => 'Unbilled items retrieved successfully',
        ]);
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

    /**
     * Get available items (time entries and addons) that can be added to a draft invoice
     */
    public function getAvailableItems(Request $request, Invoice $invoice): JsonResponse
    {
        $user = $request->user();

        // Check permissions
        if (! $user->accounts()->where('accounts.id', $invoice->account_id)->exists() &&
            ! $user->hasAnyPermission(['admin.write', 'billing.admin'])) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        // Can only add items to draft invoices
        if ($invoice->status !== 'draft') {
            return response()->json(['error' => 'Can only add items to draft invoices.'], 422);
        }

        // Get IDs of items already in this invoice (to prevent double-adding)
        $existingTimeEntryIds = $invoice->lineItems()
            ->whereNotNull('time_entry_id')
            ->pluck('time_entry_id')
            ->toArray();

        // Get approved, billable, uninvoiced time entries for this account
        $timeEntries = TimeEntry::with(['user:id,name', 'ticket:id,ticket_number,title', 'billingRate:id,name,rate'])
            ->where('account_id', $invoice->account_id)
            ->where('status', 'approved')
            ->where('billable', true)
            ->whereNull('invoice_id') // Not already invoiced elsewhere
            ->whereNotIn('id', $existingTimeEntryIds) // Not already in this invoice
            ->orderBy('started_at', 'desc')
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'type' => 'time_entry',
                    'description' => $entry->description,
                    'quantity' => round($entry->duration / 60, 2), // Convert minutes to hours
                    'unit_price' => $entry->rate_at_time ?? 0,
                    'total_amount' => $entry->calculated_cost ?? 0,
                    'started_at' => $entry->started_at,
                    'user' => $entry->user,
                    'ticket' => $entry->ticket,
                    'billing_rate' => $entry->billingRate,
                    'is_taxable' => true, // Time entries are typically taxable by default
                ];
            });

        // Get IDs of ticket addons already in this invoice
        $existingAddonIds = $invoice->lineItems()
            ->whereNotNull('ticket_addon_id')
            ->pluck('ticket_addon_id')
            ->toArray();

        // Get approved, billable, uninvoiced ticket addons for this account
        $ticketAddons = TicketAddon::with(['ticket:id,ticket_number,title', 'addedBy:id,name'])
            ->whereHas('ticket', function ($q) use ($invoice) {
                $q->where('account_id', $invoice->account_id);
            })
            ->where('status', 'approved')
            ->where('billable', true)
            ->whereNull('invoice_id') // Not already invoiced elsewhere
            ->whereNotIn('id', $existingAddonIds) // Not already in this invoice
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($addon) {
                return [
                    'id' => $addon->id,
                    'type' => 'ticket_addon',
                    'description' => $addon->name.($addon->description ? ' - '.$addon->description : ''),
                    'quantity' => $addon->quantity,
                    'unit_price' => $addon->unit_price,
                    'total_amount' => $addon->total_amount,
                    'created_at' => $addon->created_at,
                    'ticket' => $addon->ticket,
                    'added_by' => $addon->addedBy,
                    'is_taxable' => $addon->is_taxable,
                    'tax_rate' => $addon->tax_rate,
                ];
            });

        return response()->json([
            'data' => [
                'time_entries' => $timeEntries,
                'ticket_addons' => $ticketAddons,
            ]
        ]);
    }

    /**
     * Add a line item to a draft invoice
     */
    public function addLineItem(Request $request, Invoice $invoice): JsonResponse
    {
        $user = $request->user();

        // Check permissions
        if (! $user->accounts()->where('accounts.id', $invoice->account_id)->exists() &&
            ! $user->hasAnyPermission(['admin.write', 'billing.admin'])) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        // Can only add items to draft invoices
        if ($invoice->status !== 'draft') {
            return response()->json(['error' => 'Can only add items to draft invoices.'], 422);
        }

        $validated = $request->validate([
            'item_type' => 'required|in:time_entry,ticket_addon,custom',
            'item_id' => 'required_unless:item_type,custom|string',
            'description' => 'required_if:item_type,custom|string|max:1000',
            'quantity' => 'required_if:item_type,custom|numeric|min:0.01',
            'unit_price' => 'required_if:item_type,custom|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $lineItem = null;

            if ($validated['item_type'] === 'time_entry') {
                $timeEntry = TimeEntry::where('id', $validated['item_id'])
                    ->where('account_id', $invoice->account_id)
                    ->where('status', 'approved')
                    ->where('billable', true)
                    ->whereNull('invoice_id')
                    ->first();

                if (! $timeEntry) {
                    return response()->json(['error' => 'Time entry not found or already invoiced.'], 404);
                }

                $lineItem = InvoiceLineItem::create([
                    'invoice_id' => $invoice->id,
                    'time_entry_id' => $timeEntry->id,
                    'line_type' => 'time_entry',
                    'description' => $timeEntry->description,
                    'quantity' => $timeEntry->duration / 60, // Convert minutes to hours
                    'unit_price' => $timeEntry->rate_at_time ?? 0,
                    'total_amount' => $timeEntry->calculated_cost ?? 0,
                ]);

                // Mark time entry as invoiced
                $timeEntry->update(['invoice_id' => $invoice->id]);

            } elseif ($validated['item_type'] === 'ticket_addon') {
                $addon = TicketAddon::whereHas('ticket', function ($q) use ($invoice) {
                    $q->where('account_id', $invoice->account_id);
                })
                    ->where('id', $validated['item_id'])
                    ->where('status', 'approved')
                    ->where('billable', true)
                    ->whereNull('invoice_id')
                    ->first();

                if (! $addon) {
                    return response()->json(['error' => 'Ticket addon not found or already invoiced.'], 404);
                }

                $lineItem = InvoiceLineItem::create([
                    'invoice_id' => $invoice->id,
                    'ticket_addon_id' => $addon->id,
                    'line_type' => 'addon',
                    'description' => $addon->name.($addon->description ? ' - '.$addon->description : ''),
                    'quantity' => $addon->quantity,
                    'unit_price' => $addon->unit_price,
                    'discount_amount' => $addon->discount_amount ?? 0,
                    'total_amount' => $addon->total_amount,
                ]);

                // Mark addon as invoiced
                $addon->update(['invoice_id' => $invoice->id]);

            } elseif ($validated['item_type'] === 'custom') {
                $lineItem = InvoiceLineItem::create([
                    'invoice_id' => $invoice->id,
                    'line_type' => 'custom',
                    'description' => $validated['description'],
                    'quantity' => $validated['quantity'],
                    'unit_price' => $validated['unit_price'],
                    'total_amount' => $validated['quantity'] * $validated['unit_price'],
                ]);
            }

            // Recalculate invoice totals
            $invoice->calculateTotals();
            $invoice->save();

            DB::commit();

            $lineItem->load(['timeEntry', 'ticketAddon']);

            return response()->json([
                'data' => new \App\Http\Resources\InvoiceLineItemResource($lineItem),
                'message' => 'Line item added successfully.'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to add line item.'], 500);
        }
    }

    /**
     * Remove a line item from a draft invoice
     */
    public function removeLineItem(Request $request, Invoice $invoice, InvoiceLineItem $lineItem): JsonResponse
    {
        $user = $request->user();

        // Check permissions
        if (! $user->accounts()->where('accounts.id', $invoice->account_id)->exists() &&
            ! $user->hasAnyPermission(['admin.write', 'billing.admin'])) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        // Can only remove items from draft invoices
        if ($invoice->status !== 'draft') {
            return response()->json(['error' => 'Can only remove items from draft invoices.'], 422);
        }

        // Verify line item belongs to this invoice
        if ($lineItem->invoice_id !== $invoice->id) {
            return response()->json(['error' => 'Line item does not belong to this invoice.'], 422);
        }

        DB::beginTransaction();
        try {
            // If this line item is linked to a time entry or addon, unlink it
            if ($lineItem->time_entry_id) {
                TimeEntry::where('id', $lineItem->time_entry_id)->update(['invoice_id' => null]);
            }
            
            if ($lineItem->ticket_addon_id) {
                TicketAddon::where('id', $lineItem->ticket_addon_id)->update(['invoice_id' => null]);
            }

            // Delete the line item
            $lineItem->delete();

            // Recalculate invoice totals
            $invoice->calculateTotals();
            $invoice->save();

            DB::commit();

            return response()->json([
                'message' => 'Line item removed successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to remove line item.'], 500);
        }
    }

    /**
     * Update a line item (custom items only)
     */
    public function updateLineItem(Request $request, Invoice $invoice, InvoiceLineItem $lineItem): JsonResponse
    {
        $user = $request->user();

        // Check permissions
        if (! $user->accounts()->where('accounts.id', $invoice->account_id)->exists() &&
            ! $user->hasAnyPermission(['admin.write', 'billing.admin'])) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        // Can only update items in draft invoices
        if ($invoice->status !== 'draft') {
            return response()->json(['error' => 'Can only update items in draft invoices.'], 422);
        }

        // Verify line item belongs to this invoice
        if ($lineItem->invoice_id !== $invoice->id) {
            return response()->json(['error' => 'Line item does not belong to this invoice.'], 422);
        }

        // For taxable field updates, allow all line item types
        // For other fields, only allow custom line items
        $requestKeys = array_keys($request->all());
        $isTaxableOnlyUpdate = count($requestKeys) === 1 && in_array('taxable', $requestKeys);
        
        if (!$isTaxableOnlyUpdate && $lineItem->line_type !== 'custom') {
            return response()->json(['error' => 'Can only update description, quantity, and unit_price on custom line items.'], 422);
        }

        $validated = $request->validate([
            'description' => 'sometimes|string|max:1000',
            'quantity' => 'sometimes|numeric|min:0.01',
            'unit_price' => 'sometimes|numeric|min:0',
            'taxable' => 'sometimes|nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $lineItem->update($validated);

            // Recalculate totals if quantity, price, or taxable status changed
            if (isset($validated['quantity']) || isset($validated['unit_price']) || isset($validated['taxable'])) {
                // Recalculate line item totals
                if (isset($validated['quantity']) || isset($validated['unit_price'])) {
                    $lineItem->total_amount = $lineItem->quantity * $lineItem->unit_price;
                    $lineItem->save();
                }

                // Recalculate invoice totals (including tax)
                $invoice->calculateTotals();
                $invoice->save();
            }

            DB::commit();

            return response()->json([
                'data' => new \App\Http\Resources\InvoiceLineItemResource($lineItem),
                'message' => 'Line item updated successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update line item.'], 500);
        }
    }

    /**
     * Reorder line items
     */
    public function reorderLineItems(Request $request, Invoice $invoice): JsonResponse
    {
        $user = $request->user();

        // Check permissions
        if (! $user->accounts()->where('accounts.id', $invoice->account_id)->exists() &&
            ! $user->hasAnyPermission(['admin.write', 'billing.admin'])) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        // Can only reorder line items on draft invoices
        if ($invoice->status !== 'draft') {
            return response()->json(['error' => 'Can only reorder items on draft invoices.'], 422);
        }

        $validated = $request->validate([
            'line_items' => 'required|array',
            'line_items.*.id' => 'required|uuid|exists:invoice_line_items,id',
            'line_items.*.sort_order' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['line_items'] as $item) {
                $lineItem = InvoiceLineItem::where('id', $item['id'])
                                          ->where('invoice_id', $invoice->id)
                                          ->first();
                
                if ($lineItem) {
                    $lineItem->update(['sort_order' => $item['sort_order']]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Line items reordered successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to reorder line items.'], 500);
        }
    }

    /**
     * Add separator line item
     */
    public function addSeparator(Request $request, Invoice $invoice): JsonResponse
    {
        $user = $request->user();

        // Check permissions
        if (! $user->accounts()->where('accounts.id', $invoice->account_id)->exists() &&
            ! $user->hasAnyPermission(['admin.write', 'billing.admin'])) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        // Can only add separators to draft invoices
        if ($invoice->status !== 'draft') {
            return response()->json(['error' => 'Can only add separators to draft invoices.'], 422);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'position' => 'sometimes|integer|min:0', // Optional position, defaults to end
        ]);

        // Get the next sort order if position not specified
        $sortOrder = $validated['position'] ?? $invoice->lineItems()->max('sort_order') + 1;

        // Shift existing items if inserting in middle
        if (isset($validated['position'])) {
            $invoice->lineItems()
                    ->where('sort_order', '>=', $sortOrder)
                    ->increment('sort_order');
        }

        // Create separator line item
        $separator = InvoiceLineItem::create([
            'invoice_id' => $invoice->id,
            'line_type' => 'separator',
            'sort_order' => $sortOrder,
            'description' => $validated['title'],
            'quantity' => 0,
            'unit_price' => 0,
            'total_amount' => 0,
            'billable' => false,
        ]);

        return response()->json([
            'data' => new \App\Http\Resources\InvoiceLineItemResource($separator),
            'message' => 'Separator added successfully.',
        ], 201);
    }
}
