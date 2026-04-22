<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movement extends Model
{
    use HasFactory;

    protected $fillable = [
        'tool_id', 'customer_id', 'operator_id',
        'from_location_id', 'to_location_id',
        'type', 'qty',
        'work_order', 'machine', 'notes',
        'occurred_at', 'return_due_at', 'returned_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'return_due_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function isOverdue(): bool
    {
        return $this->type === 'checkout'
            && ! $this->returned_at
            && $this->return_due_at
            && $this->return_due_at->isPast();
    }

    public function scopeOpenCheckouts($q)
    {
        return $q->where('type', 'checkout')->whereNull('returned_at');
    }
}
