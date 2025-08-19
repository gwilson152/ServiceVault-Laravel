<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'invoice_id',
        'account_id',
        'payment_reference',
        'payment_method',
        'payment_gateway_id',
        'transaction_id',
        'amount',
        'fees',
        'net_amount',
        'currency',
        'status',
        'payment_date',
        'processed_at',
        'notes',
        'gateway_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fees' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'processed_at' => 'datetime',
        'gateway_response' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (! $payment->net_amount) {
                $payment->net_amount = $payment->amount - $payment->fees;
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    // Helper methods
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markAsCompleted(): bool
    {
        $this->status = 'completed';
        $this->processed_at = now();

        $result = $this->save();

        // Update invoice status if fully paid
        if ($result && $this->invoice) {
            $totalPaid = $this->invoice->payments()->completed()->sum('amount');
            if ($totalPaid >= $this->invoice->total) {
                $this->invoice->update(['status' => 'paid']);
            }
        }

        return $result;
    }

    public function getFormattedAmountAttribute(): string
    {
        return '$'.number_format($this->amount, 2);
    }

    public function getFormattedNetAmountAttribute(): string
    {
        return '$'.number_format($this->net_amount, 2);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'completed' => 'green',
            'failed' => 'red',
            'refunded' => 'purple',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
            'bank_transfer' => 'Bank Transfer',
            'check' => 'Check',
            'cash' => 'Cash',
            'other' => 'Other',
            default => ucfirst($this->payment_method),
        };
    }
}
