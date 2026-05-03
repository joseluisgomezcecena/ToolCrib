<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ToolItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'tool_id', 'tag', 'status', 'condition',
        'location_id',
        'last_maintenance_at', 'next_maintenance_at',
        'used_cycles', 'notes',
    ];

    protected $casts = [
        'last_maintenance_at' => 'date',
        'next_maintenance_at' => 'date',
    ];

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class);
    }

    public function isMaintenanceDue(): bool
    {
        return $this->next_maintenance_at && $this->next_maintenance_at->isPast();
    }

    public function isBlocked(): bool
    {
        return $this->condition !== 'ok'
            || in_array($this->status, ['maintenance', 'lost', 'scrapped'])
            || $this->isMaintenanceDue();
    }

    public function scopeAvailable($q)
    {
        return $q->where('status', 'available');
    }
}
