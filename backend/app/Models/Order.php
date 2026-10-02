<?php

namespace App\Models;

use App\Support\Vat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasOne;   

class Order extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'event_id',
        'total_cents',
        'status',
        'payment_intent_id',
        'invoice_number',
        'expires_at',
        'paid_at',
        'refunded_at',
        'subtotal_cents',
        'vat_cents',
        'vat_rate',
        'billing_address',
    ];

    protected function casts(): array
    {
        return [
            'total_cents' => 'integer',
            'subtotal_cents' => 'integer',
            'vat_cents' => 'integer',
            'vat_rate' => 'float',
            'billing_address' => 'array',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Order $order) {
            if ($order->isDirty('total_cents') || $order->vat_rate === null) {
                $order->vat_rate ??= config('eventix.vat_rate');

                $split = Vat::split(
                    $order->total_cents,
                    $order->vat_rate
                );

                $order->subtotal_cents = $split['subtotal_cents'];
                $order->vat_cents = $split['vat_cents'];
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function billingSnapshot(): array
    {
        $user = $this->user;

        return [
            'name' => $user->name,
            'email' => $user->email,
            'country' => $user->billing_country,
            'city' => $user->billing_city,
            'postal_code' => $user->billing_postal_code,
            'street' => $user->billing_street,
        ];
    }

    public function isRefundableByUser(): bool
    {
        return $this->status === 'completed'
            && $this->event
            && $this->event->starts_at->gt(
                now()->addHours(
                    config('eventix.refund_window_hours')
                )
            );
    }

public function refundRequests(): HasMany
{
    return $this->hasMany(RefundRequest::class);
}

public function refundRequest(): HasOne
{
    return $this->hasOne(RefundRequest::class)->latestOfMany();
}
}