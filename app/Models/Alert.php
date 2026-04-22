<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'tool_id', 'user_id', 'movement_id',
        'type', 'severity',
        'title', 'message',
        'data', 'read_at', 'resolved_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(Movement::class);
    }

    public function scopeUnread($q)
    {
        return $q->whereNull('read_at');
    }

    public function scopeUnresolved($q)
    {
        return $q->whereNull('resolved_at');
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }
}
