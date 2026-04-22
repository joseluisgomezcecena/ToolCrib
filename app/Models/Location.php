<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = ['parent_id', 'name', 'code', 'type', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Location::class, 'parent_id');
    }

    public function tools(): HasMany
    {
        return $this->hasMany(Tool::class);
    }

    public function getFullPathAttribute(): string
    {
        $parts = [$this->name];
        $node = $this->parent;
        while ($node) {
            array_unshift($parts, $node->name);
            $node = $node->parent;
        }
        return implode(' / ', $parts);
    }
}
