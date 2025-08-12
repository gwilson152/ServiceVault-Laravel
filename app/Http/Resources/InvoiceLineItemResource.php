<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceLineItemResource extends JsonResource
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
            'line_type' => $this->line_type,
            'description' => $this->description,
            'quantity' => number_format($this->quantity, 2),
            'unit_price' => number_format($this->unit_price, 2),
            'discount_amount' => number_format($this->discount_amount, 2),
            'tax_rate' => $this->tax_rate,
            'tax_amount' => number_format($this->tax_amount, 2),
            'total_amount' => number_format($this->total_amount, 2),
            'is_billable' => $this->is_billable,
            'metadata' => $this->metadata,
            
            // Relationships
            'time_entry' => $this->whenLoaded('timeEntry', function () {
                return [
                    'id' => $this->timeEntry->id,
                    'description' => $this->timeEntry->description,
                    'duration_formatted' => $this->timeEntry->duration_formatted,
                ];
            }),
            
            'ticket_addon' => $this->whenLoaded('ticketAddon', function () {
                return [
                    'id' => $this->ticketAddon->id,
                    'name' => $this->ticketAddon->name,
                    'category' => $this->ticketAddon->category,
                ];
            }),
        ];
    }
}
