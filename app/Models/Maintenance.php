<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Maintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'tool_id', 'tool_item_id', 'performed_by',
        'type', 'status',
        'scheduled_at', 'started_at', 'completed_at',
        'cost', 'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cost' => 'decimal:2',
    ];

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    public function toolItem(): BelongsTo
    {
        return $this->belongsTo(ToolItem::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
