<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'payment_reference' => $this->payment_reference,
            'payment_method' => $this->payment_method,
            'payment_method_label' => $this->payment_method_label,
            'transaction_id' => $this->transaction_id,
            'amount' => number_format($this->amount, 2),
            'fees' => number_format($this->fees, 2),
            'net_amount' => number_format($this->net_amount, 2),
            'currency' => $this->currency,
            'status' => $this->status,
            'status_color' => $this->status_color,
            'payment_date' => $this->payment_date->format('Y-m-d H:i:s'),
            'processed_at' => $this->processed_at?->format('Y-m-d H:i:s'),
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
