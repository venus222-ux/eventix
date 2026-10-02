<?php

namespace App\Models;

use App\Enums\SeatStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Seat extends Model
{
    use HasFactory;

    protected $fillable = ['section_id', 'row', 'number', 'status'];

    protected function casts(): array
    {
        return ['status' => SeatStatus::class];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function orderItem(): HasOne
    {
        return $this->hasOne(OrderItem::class);
    }

    public function label(): string
    {
        return "{$this->row}{$this->number}";
    }
}
