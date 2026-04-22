<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tool extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'description',
        'category_id', 'location_id',
        'type', 'condition',
        'qty_total', 'qty_available',
        'stock_min', 'stock_max',
        'unit_cost',
        'life_cycles', 'used_cycles',
        'last_maintenance_at', 'next_maintenance_at',
        'is_active', 'image_path',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_maintenance_at' => 'date',
        'next_maintenance_at' => 'date',
        'unit_cost' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function isLowStock(): bool
    {
        return $this->qty_available <= $this->stock_min;
    }

    public function isMaintenanceDue(): bool
    {
        return $this->next_maintenance_at
            && $this->next_maintenance_at->isPast();
    }

    public function isBlocked(): bool
    {
        return $this->condition !== 'ok'
            || ! $this->is_active
            || $this->isMaintenanceDue();
    }

    public function scopeDurable($q)
    {
        return $q->where('type', 'durable');
    }

    public function scopeConsumible($q)
    {
        return $q->where('type', 'consumible');
    }
}
