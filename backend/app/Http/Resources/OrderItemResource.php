<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'seat' => [
                'id' => $this->seat->id,
                'label' => $this->seat->label(),
                'section' => $this->seat->section->name,
            ],
            'unit_price_cents' => $this->unit_price_cents,
        ];
    }
}
