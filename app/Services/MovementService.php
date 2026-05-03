<?php

namespace App\Services;

use App\Events\AlertCreated;
use App\Models\Alert;
use App\Models\Movement;
use App\Models\Tool;
use App\Models\ToolItem;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MovementService
{
    public function checkout(array $data): Movement
    {
        return DB::transaction(function () use ($data) {
            /** @var Tool $tool */
            $tool = Tool::lockForUpdate()->findOrFail($data['tool_id']);

            $item = null;
            $qty = (int) ($data['qty'] ?? 1);

            if ($tool->isSerialized()) {
                if (empty($data['tool_item_id'])) {
                    throw new RuntimeException('Herramienta serializada: se requiere seleccionar una instancia específica.');
                }
                /** @var ToolItem $item */
                $item = ToolItem::lockForUpdate()
                    ->where('tool_id', $tool->id)
                    ->findOrFail($data['tool_item_id']);

                if ($item->status !== 'available') {
                    throw new RuntimeException('Instancia '.$item->tag.' no disponible. Estado: '.$item->status);
                }
                if ($item->isBlocked()) {
                    throw new RuntimeException('Instancia '.$item->tag.' bloqueada por condición o mantenimiento.');
                }
                $qty = 1;
                $type = 'checkout';
            } else {
                if ($tool->isBlocked()) {
                    throw new RuntimeException('Herramienta bloqueada: revisar condición o mantenimiento.');
                }
                if ($tool->qty_available < $qty) {
                    throw new RuntimeException('Stock insuficiente. Disponible: '.$tool->qty_available);
                }
                $type = $tool->type === 'consumible' ? 'consume' : 'checkout';
            }

            $movement = Movement::create([
                'tool_id' => $tool->id,
                'tool_item_id' => $item?->id,
                'customer_id' => $data['customer_id'] ?? null,
                'operator_id' => $data['operator_id'] ?? null,
                'from_location_id' => $item?->location_id ?? $tool->location_id,
                'to_location_id' => $data['to_location_id'] ?? null,
                'type' => $type,
                'qty' => $qty,
                'work_order' => $data['work_order'] ?? null,
                'notes' => $data['notes'] ?? null,
                'occurred_at' => now(),
                'return_due_at' => $type === 'checkout' ? ($data['return_due_at'] ?? now()->addHours(8)) : null,
            ]);

            if ($item) {
                $item->update([
                    'status' => 'in_use',
                    'location_id' => $data['to_location_id'] ?? $item->location_id,
                ]);
                $tool->decrement('qty_available', 1);
            } else {
                $tool->decrement('qty_available', $qty);
            }

            if ($tool->type === 'durable' && $tool->life_cycles) {
                $tool->increment('used_cycles', $qty);
            }
            if ($item && $tool->life_cycles) {
                $item->increment('used_cycles', $qty);
            }

            $this->checkLowStock($tool);

            return $movement;
        });
    }

    public function checkin(int $movementId, ?int $operatorId = null, ?string $notes = null): Movement
    {
        return DB::transaction(function () use ($movementId, $operatorId, $notes) {
            /** @var Movement $movement */
            $movement = Movement::lockForUpdate()->findOrFail($movementId);

            if ($movement->type !== 'checkout' || $movement->returned_at) {
                throw new RuntimeException('Este movimiento ya no requiere devolución.');
            }

            $movement->update([
                'returned_at' => now(),
                'notes' => trim(($movement->notes ?? '')."\nDevuelto: ".($notes ?? '')),
            ]);

            /** @var Tool $tool */
            $tool = Tool::lockForUpdate()->findOrFail($movement->tool_id);

            if ($movement->tool_item_id) {
                /** @var ToolItem $item */
                $item = ToolItem::lockForUpdate()->findOrFail($movement->tool_item_id);
                $item->update([
                    'status' => 'available',
                    'location_id' => $tool->location_id,
                ]);
                $tool->increment('qty_available', 1);
            } else {
                $tool->increment('qty_available', $movement->qty);
            }

            Movement::create([
                'tool_id' => $tool->id,
                'tool_item_id' => $movement->tool_item_id,
                'customer_id' => $movement->customer_id,
                'operator_id' => $operatorId,
                'from_location_id' => $movement->to_location_id,
                'to_location_id' => $tool->location_id,
                'type' => 'checkin',
                'qty' => $movement->qty,
                'work_order' => $movement->work_order,
                'occurred_at' => now(),
            ]);

            return $movement->fresh();
        });
    }

    public function scrap(array $data): Movement
    {
        return DB::transaction(function () use ($data) {
            /** @var Tool $tool */
            $tool = Tool::lockForUpdate()->findOrFail($data['tool_id']);
            $item = null;

            if (! empty($data['tool_item_id'])) {
                $item = ToolItem::lockForUpdate()
                    ->where('tool_id', $tool->id)
                    ->findOrFail($data['tool_item_id']);
                if (in_array($item->status, ['scrapped', 'lost'])) {
                    throw new RuntimeException('Instancia ya dada de baja.');
                }
                $qty = 1;
            } else {
                if ($tool->isSerialized()) {
                    throw new RuntimeException('Herramienta serializada: especifica la instancia a dar de baja.');
                }
                $qty = (int) ($data['qty'] ?? 1);
                if ($tool->qty_available < $qty) {
                    throw new RuntimeException('Stock insuficiente para scrap. Disponible: '.$tool->qty_available);
                }
            }

            $movement = Movement::create([
                'tool_id' => $tool->id,
                'tool_item_id' => $item?->id,
                'operator_id' => $data['operator_id'] ?? null,
                'from_location_id' => $item?->location_id ?? $tool->location_id,
                'type' => 'scrap',
                'qty' => $qty,
                'notes' => $data['notes'] ?? null,
                'occurred_at' => now(),
            ]);

            if ($item) {
                $item->update(['status' => 'scrapped', 'condition' => 'scrap']);
                if ($item->status === 'available') {
                    $tool->decrement('qty_available', 1);
                }
                $tool->decrement('qty_total', 1);
            } else {
                $tool->decrement('qty_available', $qty);
                $tool->decrement('qty_total', $qty);
            }

            if ($tool->qty_total <= 0 && ! $tool->isSerialized()) {
                $tool->update(['condition' => 'scrap']);
            }

            return $movement;
        });
    }

    public function transfer(array $data): Movement
    {
        return DB::transaction(function () use ($data) {
            /** @var Tool $tool */
            $tool = Tool::lockForUpdate()->findOrFail($data['tool_id']);
            $item = null;

            $to = (int) $data['to_location_id'];
            if (! $to) {
                throw new RuntimeException('Ubicación destino requerida.');
            }

            if (! empty($data['tool_item_id'])) {
                $item = ToolItem::lockForUpdate()
                    ->where('tool_id', $tool->id)
                    ->findOrFail($data['tool_item_id']);
                if ($item->location_id === $to) {
                    throw new RuntimeException('La instancia ya está en esa ubicación.');
                }
                $from = $item->location_id;
                $qty = 1;
            } else {
                if ($tool->location_id === $to) {
                    throw new RuntimeException('La herramienta ya está en esa ubicación.');
                }
                $from = $tool->location_id;
                $qty = (int) ($data['qty'] ?? $tool->qty_available);
            }

            $movement = Movement::create([
                'tool_id' => $tool->id,
                'tool_item_id' => $item?->id,
                'operator_id' => $data['operator_id'] ?? null,
                'from_location_id' => $from,
                'to_location_id' => $to,
                'type' => 'transfer',
                'qty' => $qty,
                'notes' => $data['notes'] ?? null,
                'occurred_at' => now(),
            ]);

            if ($item) {
                $item->update(['location_id' => $to]);
            } else {
                $tool->update(['location_id' => $to]);
            }

            return $movement;
        });
    }

    public function receipt(array $data): Movement
    {
        return DB::transaction(function () use ($data) {
            /** @var Tool $tool */
            $tool = Tool::lockForUpdate()->findOrFail($data['tool_id']);

            $qty = (int) ($data['qty'] ?? 1);
            if ($qty <= 0) {
                throw new RuntimeException('Cantidad debe ser mayor a cero.');
            }

            $movement = Movement::create([
                'tool_id' => $tool->id,
                'operator_id' => $data['operator_id'] ?? null,
                'to_location_id' => $tool->location_id,
                'type' => 'receipt',
                'qty' => $qty,
                'work_order' => $data['purchase_order'] ?? null,
                'notes' => $data['notes'] ?? null,
                'occurred_at' => now(),
            ]);

            $tool->increment('qty_total', $qty);
            $tool->increment('qty_available', $qty);

            if (! empty($data['unit_cost'])) {
                $tool->update(['unit_cost' => $data['unit_cost']]);
            }

            if ($tool->isSerialized()) {
                $this->generateItems($tool, $qty);
            }

            return $movement;
        });
    }

    public function generateItems(Tool $tool, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            ToolItem::create([
                'tool_id' => $tool->id,
                'tag' => $tool->fresh()->nextItemTag(),
                'status' => 'available',
                'condition' => 'ok',
                'location_id' => $tool->location_id,
            ]);
        }
    }

    public function scanOverdueReturns(): int
    {
        $overdue = Movement::openCheckouts()
            ->whereNotNull('return_due_at')
            ->where('return_due_at', '<', now())
            ->with('tool', 'toolItem', 'customer')
            ->get();

        foreach ($overdue as $m) {
            $exists = Alert::where('movement_id', $m->id)
                ->where('type', 'no_devolucion')
                ->whereNull('resolved_at')
                ->exists();

            if ($exists) {
                continue;
            }

            $label = $m->toolItem?->tag ?? $m->tool?->name ?? 'herramienta';

            $alert = Alert::create([
                'tool_id' => $m->tool_id,
                'tool_item_id' => $m->tool_item_id,
                'user_id' => $m->customer_id,
                'movement_id' => $m->id,
                'type' => 'no_devolucion',
                'severity' => 'critical',
                'title' => 'Herramienta no devuelta',
                'message' => sprintf(
                    '%s tiene %s vencida desde %s',
                    optional($m->customer)->name ?? 'Usuario',
                    $label,
                    $m->return_due_at->diffForHumans(),
                ),
            ]);

            broadcast(new AlertCreated($alert));
        }

        return $overdue->count();
    }

    protected function checkLowStock(Tool $tool): void
    {
        if (! $tool->isLowStock()) {
            return;
        }

        $exists = Alert::where('tool_id', $tool->id)
            ->where('type', 'stock_bajo')
            ->whereNull('resolved_at')
            ->exists();

        if ($exists) {
            return;
        }

        $alert = Alert::create([
            'tool_id' => $tool->id,
            'type' => 'stock_bajo',
            'severity' => 'warning',
            'title' => 'Stock bajo',
            'message' => sprintf(
                '%s quedó con %d unidades (mínimo %d).',
                $tool->name,
                $tool->qty_available,
                $tool->stock_min,
            ),
        ]);

        broadcast(new AlertCreated($alert));
    }
}
