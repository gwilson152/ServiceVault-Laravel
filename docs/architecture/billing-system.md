# Billing System Architecture

This document outlines the technical architecture and implementation details of the Service Vault billing and financial management system.

## System Overview

The billing system is built as an integrated component of the Service Vault platform, providing enterprise-grade financial management capabilities for B2B service delivery workflows.

### Architecture Principles

-   **Integration-First Design**: Seamlessly integrates with timers, tickets, and account management
-   **Permission-Based Security**: Three-dimensional permission system for fine-grained access control
-   **Account-Aware Operations**: Hierarchical account structure with inherited billing configurations
-   **Real-Time Processing**: Live updates and synchronization across all billing operations
-   **Scalable Design**: Optimized for high-volume billing operations with efficient database queries

## Database Architecture

### Core Tables

#### invoices

```sql
CREATE TABLE invoices (
    id UUID PRIMARY KEY,
    account_id UUID NOT NULL REFERENCES accounts(id),
    invoice_number VARCHAR(255) UNIQUE NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    status invoice_status NOT NULL DEFAULT 'draft',
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    notes TEXT,
    created_by UUID REFERENCES users(id),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TYPE invoice_status AS ENUM (
    'draft', 'pending', 'sent', 'paid', 'overdue', 'void'
);
```

#### payments

```sql
CREATE TABLE payments (
    id UUID PRIMARY KEY,
    invoice_id UUID REFERENCES invoices(id),
    account_id UUID NOT NULL REFERENCES accounts(id),
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method payment_method NOT NULL,
    status payment_status NOT NULL DEFAULT 'pending',
    reference_number VARCHAR(255),
    notes TEXT,
    processed_by UUID REFERENCES users(id),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TYPE payment_method AS ENUM (
    'bank_transfer', 'credit_card', 'check', 'online', 'wire', 'ach'
);

CREATE TYPE payment_status AS ENUM (
    'pending', 'completed', 'failed', 'refunded'
);
```

#### billing_rates

```sql
CREATE TABLE billing_rates (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    rate DECIMAL(8,2) NOT NULL,
    account_id UUID REFERENCES accounts(id), -- NULL for global rates
    user_id UUID REFERENCES users(id),       -- NULL for non-user-specific rates
    is_active BOOLEAN DEFAULT true,
    effective_date DATE DEFAULT CURRENT_DATE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### addon_templates

```sql
CREATE TABLE addon_templates (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category addon_category NOT NULL,
    sku VARCHAR(100),
    default_unit_price DECIMAL(10,2) NOT NULL,
    default_quantity INTEGER DEFAULT 1,
    billable BOOLEAN DEFAULT true,
    is_taxable BOOLEAN DEFAULT true,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TYPE addon_category AS ENUM (
    'hardware', 'software', 'service', 'training', 'support', 'consulting'
);
```

### Relationship Architecture

```
accounts (1) -----> (many) invoices
accounts (1) -----> (many) billing_rates
accounts (1) -----> (many) payments

invoices (1) -----> (many) invoice_line_items
invoices (1) -----> (many) payments

users (1) --------> (many) billing_rates
users (1) --------> (many) timers

timers (many) ----> (1) billing_rates
tickets (many) ---> (many) addon_templates
```

## Model Architecture

### Invoice Model

```php
class Invoice extends Model
{
    use HasUuids, BelongsToAccount;

    protected $fillable = [
        'account_id', 'invoice_number', 'issue_date', 'due_date',
        'status', 'subtotal', 'tax_amount', 'total_amount', 'notes'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2'
    ];

    // Relationships
    public function account() { return $this->belongsTo(Account::class); }
    public function lineItems() { return $this->hasMany(InvoiceLineItem::class); }
    public function payments() { return $this->hasMany(Payment::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }

    // Computed Properties
    public function getIsPaidAttribute(): bool
    {
        return $this->status === 'paid';
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'overdue' ||
               ($this->status === 'sent' && $this->due_date < now());
    }

    public function getPaidAmountAttribute(): float
    {
        return $this->payments()
            ->where('status', 'completed')
            ->sum('amount');
    }

    public function getOutstandingAmountAttribute(): float
    {
        return $this->total_amount - $this->paid_amount;
    }
}
```

### Payment Model

```php
class Payment extends Model
{
    use HasUuids, BelongsToAccount;

    protected $fillable = [
        'invoice_id', 'account_id', 'amount', 'payment_date',
        'payment_method', 'status', 'reference_number', 'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date'
    ];

    // Relationships
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function account() { return $this->belongsTo(Account::class); }
    public function processedBy() { return $this->belongsTo(User::class, 'processed_by'); }

    // Business Logic
    public function process(): bool
    {
        DB::transaction(function () {
            $this->update(['status' => 'completed']);
            $this->updateInvoiceStatus();
        });

        return true;
    }

    protected function updateInvoiceStatus(): void
    {
        if ($this->invoice && $this->invoice->outstanding_amount <= 0) {
            $this->invoice->update(['status' => 'paid']);
        }
    }
}
```

### BillingRate Model

```php
class BillingRate extends Model
{
    use HasUuids;

    protected $fillable = [
        'name', 'description', 'rate', 'account_id', 'user_id',
        'is_active', 'effective_date'
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_active' => 'boolean',
        'effective_date' => 'date'
    ];

    // Relationships
    public function account() { return $this->belongsTo(Account::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function timers() { return $this->hasMany(Timer::class, 'billing_rate_id'); }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('effective_date', '<=', now());
    }

    public function scopeForAccount($query, Account $account)
    {
        return $query->where(function ($q) use ($account) {
            $q->where('account_id', $account->id)
              ->orWhereNull('account_id'); // Include global rates
        });
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhereNull('user_id'); // Include non-user-specific rates
        });
    }
}
```

## Controller Architecture

### API Controllers

#### InvoiceController

```php
class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $user = $request->user();

        // Permission check with three-dimensional system
        if (!$user->hasAnyPermission(['admin.read', 'billing.admin', 'invoices.view.all'])) {
            abort(403, 'Insufficient permissions');
        }

        $query = Invoice::with(['account', 'lineItems', 'payments'])
            ->when($request->account_id, function ($q, $accountId) {
                return $q->where('account_id', $accountId);
            })
            ->when($request->status, function ($q, $status) {
                return $q->where('status', $status);
            });

        return InvoiceResource::collection(
            $query->paginate($request->get('per_page', 15))
        );
    }

    public function store(StoreInvoiceRequest $request)
    {
        $user = $request->user();

        if (!$user->hasAnyPermission(['admin.write', 'billing.admin', 'invoices.create'])) {
            abort(403, 'Insufficient permissions');
        }

        $invoice = DB::transaction(function () use ($request) {
            $invoice = Invoice::create([
                'account_id' => $request->account_id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'issue_date' => $request->issue_date ?? now(),
                'due_date' => $request->due_date ?? now()->addDays(30),
                'status' => 'draft',
                'created_by' => auth()->id()
            ]);

            // Create line items
            foreach ($request->line_items as $item) {
                $invoice->lineItems()->create($item);
            }

            // Calculate totals
            $this->calculateInvoiceTotals($invoice);

            return $invoice->fresh(['lineItems']);
        });

        return new InvoiceResource($invoice);
    }

    protected function generateInvoiceNumber(): string
    {
        $prefix = config('billing.invoice_prefix', 'INV-');
        $lastInvoice = Invoice::where('invoice_number', 'like', $prefix . '%')
            ->orderBy('invoice_number', 'desc')
            ->first();

        $nextNumber = $lastInvoice
            ? intval(substr($lastInvoice->invoice_number, strlen($prefix))) + 1
            : 1;

        return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    protected function calculateInvoiceTotals(Invoice $invoice): void
    {
        $subtotal = $invoice->lineItems()->sum(DB::raw('quantity * unit_price'));
        $taxRate = config('billing.default_tax_rate', 0);
        $taxAmount = $subtotal * ($taxRate / 100);
        $total = $subtotal + $taxAmount;

        $invoice->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $total
        ]);
    }
}
```

## Service Layer Architecture

### BillingService

```php
class BillingService
{
    public function generateInvoiceFromTimeEntries(Account $account, array $timeEntryIds): Invoice
    {
        return DB::transaction(function () use ($account, $timeEntryIds) {
            $timeEntries = TimeEntry::whereIn('id', $timeEntryIds)
                ->with(['timer.billingRate', 'user'])
                ->get();

            $invoice = Invoice::create([
                'account_id' => $account->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'issue_date' => now(),
                'due_date' => now()->addDays(config('billing.default_payment_terms', 30)),
                'status' => 'draft',
                'created_by' => auth()->id()
            ]);

            // Group time entries by billing rate
            $groupedEntries = $timeEntries->groupBy('timer.billing_rate_id');

            foreach ($groupedEntries as $rateId => $entries) {
                $totalHours = $entries->sum('duration_hours');
                $rate = $entries->first()->timer->billingRate;

                $invoice->lineItems()->create([
                    'description' => "Professional Services - {$rate->name}",
                    'quantity' => $totalHours,
                    'unit_price' => $rate->rate,
                    'line_total' => $totalHours * $rate->rate
                ]);
            }

            $this->calculateInvoiceTotals($invoice);

            return $invoice->fresh(['lineItems']);
        });
    }

    public function processPayment(Invoice $invoice, array $paymentData): Payment
    {
        return DB::transaction(function () use ($invoice, $paymentData) {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'account_id' => $invoice->account_id,
                'amount' => $paymentData['amount'],
                'payment_date' => $paymentData['payment_date'] ?? now(),
                'payment_method' => $paymentData['payment_method'],
                'status' => 'completed',
                'reference_number' => $paymentData['reference_number'] ?? null,
                'processed_by' => auth()->id()
            ]);

            // Update invoice status if fully paid
            if ($invoice->outstanding_amount <= 0) {
                $invoice->update(['status' => 'paid']);
            }

            return $payment;
        });
    }

    public function getBillingRateForTimer(Timer $timer): ?BillingRate
    {
        // Priority order: User-specific -> Account-specific -> Global
        return BillingRate::active()
            ->where(function ($q) use ($timer) {
                $q->where('user_id', $timer->user_id)
                  ->where('account_id', $timer->account_id);
            })
            ->orWhere(function ($q) use ($timer) {
                $q->where('user_id', $timer->user_id)
                  ->whereNull('account_id');
            })
            ->orWhere(function ($q) use ($timer) {
                $q->whereNull('user_id')
                  ->where('account_id', $timer->account_id);
            })
            ->orWhere(function ($q) {
                $q->whereNull('user_id')
                  ->whereNull('account_id');
            })
            ->orderByRaw('
                CASE
                    WHEN user_id IS NOT NULL AND account_id IS NOT NULL THEN 1
                    WHEN user_id IS NOT NULL AND account_id IS NULL THEN 2
                    WHEN user_id IS NULL AND account_id IS NOT NULL THEN 3
                    ELSE 4
                END
            ')
            ->first();
    }
}
```

## Widget Architecture

### Billing Widgets System

The billing system includes four specialized dashboard widgets with permission-based filtering:

#### Widget Registry Configuration

```php
// In WidgetRegistryService.php
'billing-overview' => [
    'name' => 'Billing Overview',
    'component' => 'BillingOverviewWidget',
    'permissions' => ['billing.view.account', 'billing.manage', 'billing.reports'],
    'context' => 'both',
    'account_aware' => true
],

'invoice-status' => [
    'name' => 'Invoice Status',
    'component' => 'InvoiceStatusWidget',
    'permissions' => ['billing.view.account', 'billing.manage', 'invoices.create'],
    'context' => 'both',
    'account_aware' => true
],

'payment-tracking' => [
    'name' => 'Payment Tracking',
    'component' => 'PaymentTrackingWidget',
    'permissions' => ['billing.view.account', 'billing.manage', 'payments.view'],
    'context' => 'both',
    'account_aware' => true
],

'billing-rates' => [
    'name' => 'Billing Rates',
    'component' => 'BillingRatesWidget',
    'permissions' => ['billing.rates.view', 'billing.rates.manage', 'billing.manage'],
    'context' => 'service_provider',
    'account_aware' => true
]
```

#### Widget Component Architecture

```vue
<!-- BillingOverviewWidget.vue -->
<template>
    <div class="widget-content">
        <div class="widget-header">
            <h3 class="widget-title">
                {{ widgetConfig?.name || "Billing Overview" }}
            </h3>
            <button @click="refreshData" :disabled="isLoading">
                <RefreshIcon :class="{ 'animate-spin': isLoading }" />
            </button>
        </div>

        <div class="widget-data">
            <div class="grid grid-cols-2 gap-4">
                <MetricCard
                    :value="totalRevenue"
                    label="Total Revenue"
                    format="currency"
                />
                <MetricCard
                    :value="pendingInvoices"
                    label="Pending Invoices"
                    format="number"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    widgetData: Object,
    widgetConfig: Object,
    accountContext: Object,
});

const refreshData = async () => {
    const params = new URLSearchParams();
    if (props.accountContext?.id) {
        params.append("account_id", props.accountContext.id);
    }

    const response = await fetch(`/api/billing/overview?${params}`);
    const data = await response.json();
    // Update widget data...
};
</script>
```

## Permission Integration

### Three-Dimensional Permission Architecture

The billing system fully integrates with Service Vault's three-dimensional permission system:

#### 1. Functional Permissions (Business Logic)

```php
// Example: Invoice creation check
public function store(StoreInvoiceRequest $request)
{
    $user = $request->user();

    // Check functional permission
    if (!$user->hasAnyPermission([
        'invoices.create',
        'invoices.edit.account',
        'billing.admin'
    ])) {
        abort(403, 'Cannot create invoices');
    }

    // Additional account-scoped validation
    if (!$user->hasPermissionForAccount('invoices.create', $request->account)) {
        abort(403, 'Cannot create invoices for this account');
    }
}
```

#### 2. Widget Permissions (Dashboard Access)

```php
// Widget filtering in dashboard
$availableWidgets = $widgetRegistry->getAvailableWidgets($user);
$billingWidgets = array_filter($availableWidgets, function ($widget) use ($user) {
    return $widget['category'] === 'financial' &&
           $user->hasAnyPermission($widget['permissions']);
});
```

#### 3. Page Permissions (Route Access)

```php
// Navigation filtering
$navigationItems = $navigationService->getNavigationForUser($user);
$billingAccess = $user->hasAnyPermission([
    'pages.billing.overview',
    'pages.billing.invoices',
    'pages.billing.payments'
]);
```

## Performance Optimizations

### Database Optimizations

#### Indexed Queries

```sql
-- Primary indexes for billing queries
CREATE INDEX idx_invoices_account_status ON invoices (account_id, status);
CREATE INDEX idx_payments_invoice_status ON payments (invoice_id, status);
CREATE INDEX idx_billing_rates_active_account ON billing_rates (is_active, account_id, effective_date);
CREATE INDEX idx_invoices_due_date_status ON invoices (due_date, status);
```

#### Query Optimization

```php
// Optimized invoice queries with eager loading
$invoices = Invoice::with([
    'account:id,name',
    'lineItems:id,invoice_id,description,quantity,unit_price,line_total',
    'payments:id,invoice_id,amount,status'
])
->select(['id', 'account_id', 'invoice_number', 'total_amount', 'status'])
->paginate(15);

// Efficient rate lookup with caching
$billingRate = Cache::remember(
    "billing_rate_user_{$user->id}_account_{$account->id}",
    300, // 5 minutes
    fn() => $this->getBillingRateForUser($user, $account)
);
```

### Frontend Optimizations

#### TanStack Table Integration

```vue
<!-- Optimized table with virtual scrolling -->
<script setup>
import { useVueTable, createColumnHelper } from "@tanstack/vue-table";

const columnHelper = createColumnHelper();

const columns = [
    columnHelper.accessor("invoice_number", {
        header: "Invoice #",
        cell: (info) => info.getValue(),
    }),
    columnHelper.accessor("total_amount", {
        header: "Amount",
        cell: (info) => formatCurrency(info.getValue()),
    }),
    columnHelper.accessor("status", {
        header: "Status",
        cell: (info) => h(StatusBadge, { status: info.getValue() }),
    }),
];

const table = useVueTable({
    data: invoices,
    columns,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
});
</script>
```

## API Integration

### RESTful API Design

#### Resource Structure

```
/api/billing/
├── invoices/           # Invoice management
├── payments/           # Payment processing
├── rates/              # Billing rate management
├── addons/             # Addon template management
├── overview/           # Dashboard data
└── reports/            # Financial reporting
```

#### Response Format

```json
{
    "data": {
        "id": "uuid",
        "invoice_number": "INV-000001",
        "account": {
            "id": "uuid",
            "name": "Acme Corp"
        },
        "total_amount": "1250.00",
        "status": "sent",
        "line_items": [
            {
                "description": "Professional Services",
                "quantity": 10.5,
                "unit_price": "125.00",
                "line_total": "1312.50"
            }
        ]
    },
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 45
    }
}
```

### API Authentication

#### Token Abilities

```php
// Billing-specific token abilities
'billing:read'          // View billing data
'billing:write'         // Manage billing settings
'invoices:read'         // View invoices
'invoices:write'        // Create/modify invoices
'invoices:send'         // Send invoices
'payments:read'         // View payments
'payments:write'        // Process payments
'rates:read'            // View billing rates
'rates:write'           // Manage billing rates
```

## Security Architecture

### Access Control

#### Permission Validation

```php
class BillingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'billing.view.all',
            'billing.view.account',
            'billing.admin'
        ]);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        // Super admin can view all
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Account-based permission check
        return $user->hasPermissionForAccount(
            'billing.view.account',
            $invoice->account
        );
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission([
            'invoices.create',
            'billing.admin'
        ]);
    }
}
```

#### Data Isolation

```php
// Account-scoped queries for security
public function scopeForUser($query, User $user)
{
    if ($user->isSuperAdmin()) {
        return $query;
    }

    $accessibleAccountIds = $user->getAccessibleAccountIds('billing.view.account');

    return $query->whereIn('account_id', $accessibleAccountIds);
}
```

### Audit Logging

#### Financial Operation Logging

```php
class BillingAuditService
{
    public function logInvoiceCreated(Invoice $invoice, User $user): void
    {
        AuditLog::create([
            'user_id' => $user->id,
            'auditable_type' => Invoice::class,
            'auditable_id' => $invoice->id,
            'event' => 'created',
            'old_values' => [],
            'new_values' => $invoice->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    public function logPaymentProcessed(Payment $payment, User $user): void
    {
        AuditLog::create([
            'user_id' => $user->id,
            'auditable_type' => Payment::class,
            'auditable_id' => $payment->id,
            'event' => 'processed',
            'old_values' => ['status' => 'pending'],
            'new_values' => $payment->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }
}
```

## Integration Points

### Timer System Integration

#### Automatic Rate Assignment

```php
class TimerService
{
    public function startTimer(array $data): Timer
    {
        $timer = Timer::create($data);

        // Automatically assign billing rate
        $billingRate = app(BillingService::class)
            ->getBillingRateForTimer($timer);

        if ($billingRate) {
            $timer->update(['billing_rate_id' => $billingRate->id]);
        }

        return $timer;
    }

    public function commitTimer(Timer $timer): TimeEntry
    {
        return DB::transaction(function () use ($timer) {
            $timeEntry = TimeEntry::create([
                'timer_id' => $timer->id,
                'user_id' => $timer->user_id,
                'account_id' => $timer->account_id,
                'duration_hours' => $timer->duration_hours,
                'billing_rate_id' => $timer->billing_rate_id,
                'billable_amount' => $timer->billing_rate
                    ? $timer->duration_hours * $timer->billing_rate->rate
                    : null
            ]);

            $timer->delete();

            return $timeEntry;
        });
    }
}
```

### Service Ticket Integration

#### Addon Billing

```php
class TicketService
{
    public function addAddonToTicket(Ticket $ticket, AddonTemplate $addon, array $data): TicketAddon
    {
        $ticketAddon = TicketAddon::create([
            'ticket_id' => $ticket->id,
            'addon_template_id' => $addon->id,
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'] ?? $addon->default_unit_price,
            'total_cost' => $data['quantity'] * ($data['unit_price'] ?? $addon->default_unit_price),
            'requires_approval' => $addon->requires_approval
        ]);

        if ($addon->requires_approval) {
            event(new AddonRequiresApproval($ticketAddon));
        }

        return $ticketAddon;
    }
}
```

## Deployment Considerations

### Environment Configuration

```bash
# .env billing configuration
BILLING_CURRENCY=USD
BILLING_TAX_RATE=0.0875
BILLING_PAYMENT_TERMS=30
INVOICE_PREFIX=INV-
BILLING_CACHE_RATES=true
BILLING_RATE_CACHE_TTL=300
```

### Production Optimizations

-   **Database Connection Pooling**: For high-volume billing operations
-   **Redis Caching**: Cache billing rates and frequently accessed data
-   **Queue Processing**: Background invoice generation and email sending
-   **File Storage**: Optimized PDF generation and storage for invoices

---

**Service Vault Billing System Architecture** - Enterprise-grade financial management system architecture for B2B service platforms.

_Last Updated: August 12, 2025 - Phase 13B Complete_
