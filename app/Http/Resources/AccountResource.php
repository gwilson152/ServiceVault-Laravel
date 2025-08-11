<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
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
            'name' => $this->name,
            'display_name' => $this->display_name,
            'company_name' => $this->company_name,
            'account_type' => $this->account_type,
            'account_type_display' => $this->account_type_display,
            'description' => $this->description,
            'parent_id' => $this->parent_id,
            'is_active' => $this->is_active,
            'status' => $this->is_active ? 'active' : 'inactive',
            
            // Contact Information
            'contact_person' => $this->contact_person,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            
            // Address Information
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'full_address' => $this->full_address,
            
            // Billing Information
            'billing_address' => $this->billing_address,
            'billing_city' => $this->billing_city,
            'billing_state' => $this->billing_state,
            'billing_postal_code' => $this->billing_postal_code,
            'billing_country' => $this->billing_country,
            'full_billing_address' => $this->full_billing_address,
            
            // Business Details
            'tax_id' => $this->tax_id,
            'notes' => $this->notes,
            
            // Hierarchy Information
            'hierarchy_level' => $this->hierarchy_level,
            'has_parent' => !is_null($this->parent_id),
            'has_children' => $this->children()->count() > 0,
            'children_count' => $this->children()->count(),
            'users_count' => $this->users()->count(),
            
            // System Information
            'settings' => $this->settings,
            'theme_settings' => $this->theme_settings,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Include hierarchy relationships when loaded
            'parent' => $this->whenLoaded('parent'),
            'children' => $this->whenLoaded('children'),
            
            // Include users when loaded
            'users' => $this->whenLoaded('users'),
        ];
    }
}
