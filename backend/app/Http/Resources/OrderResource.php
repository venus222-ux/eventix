<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'total_cents' => $this->total_cents,
            'invoice_number' => $this->invoice_number,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'event' => $this->whenLoaded('event', fn () => [
                'id' => $this->event->id,
                'title' => $this->event->title,
                'slug' => $this->event->slug,
                'starts_at' => $this->event->starts_at,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'name' => $this->user->name,
                'email' => $this->user->email,
                'billing_country' => $this->user->billing_country,
                'billing_city' => $this->user->billing_city,
                'billing_postal_code' => $this->user->billing_postal_code,
                'billing_street' => $this->user->billing_street,
            ]),
            // Frozen at payment time (null while the order is pending)
            'billing_address' => $this->billing_address,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
            'subtotal_cents' => $this->subtotal_cents,
            'vat_cents' => $this->vat_cents,
            'vat_rate' => $this->vat_rate,
            // No second request while one is waiting for review
            'can_refund' => $this->whenLoaded('event', fn () => $this->isRefundableByUser()
                && ! ($this->relationLoaded('refundRequest') && $this->refundRequest?->status === 'pending')),
            'refund_request' => $this->whenLoaded('refundRequest', fn () => $this->refundRequest ? [
                'status' => $this->refundRequest->status,
                'reason' => $this->refundRequest->reason,
                'created_at' => $this->refundRequest->created_at?->toIso8601String(),
            ] : null),
        ];
    }
}