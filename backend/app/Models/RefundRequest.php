<?php

namespace App\Models;

use App\Services\RefundRequestService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundRequest extends Model
{
    protected $fillable = [
        'order_id', 'user_id', 'reason', 'message', 'status',
        'decided_by', 'decided_by_user_id', 'decision_note', 'snapshot', 'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'decided_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reasonLabel(): string
    {
        return RefundRequestService::REASONS[$this->reason]
            ?? ucfirst(str_replace('_', ' ', $this->reason));
    }
}