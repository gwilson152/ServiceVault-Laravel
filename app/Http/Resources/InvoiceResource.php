<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status,
            'status_color' => $this->status_color,
            'invoice_date' => $this->invoice_date->format('Y-m-d'),
            'due_date' => $this->due_date->format('Y-m-d'),
            'is_overdue' => $this->isOverdue(),
            'days_overdue' => $this->days_overdue,
            'subtotal' => number_format($this->subtotal, 2),
            'tax_rate' => $this->tax_rate,
            'tax_amount' => number_format($this->tax_amount, 2),
            'total' => number_format($this->total, 2),
            'outstanding_balance' => number_format($this->getOutstandingBalance(), 2),
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'account' => $this->whenLoaded('account', function () {
                return [
                    'id' => $this->account->id,
                    'name' => $this->account->name,
                ];
            }),

            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),

            'line_items' => InvoiceLineItemResource::collection($this->whenLoaded('lineItems')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),

            // Computed fields
            'line_items_count' => $this->whenCounted('lineItems'),
            'payments_count' => $this->whenCounted('payments'),
            'total_paid' => $this->when(
                $this->relationLoaded('payments'),
                fn () => number_format($this->payments->where('status', 'completed')->sum('amount'), 2)
            ),
        ];

    }
}
