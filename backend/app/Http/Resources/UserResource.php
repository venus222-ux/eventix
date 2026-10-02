<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name ?? 'N/A',
            'email' => $this->email ?? 'N/A',
            'roles' => $this->getRoleNames()->isNotEmpty() ? $this->getRoleNames() : ['user'],
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : 'Unknown',
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : 'Unknown',
            'billing_country'     => $this->billing_country,
            'billing_city'        => $this->billing_city,
            'billing_postal_code' => $this->billing_postal_code,
             'billing_street'      => $this->billing_street,
        ];
    }
}
