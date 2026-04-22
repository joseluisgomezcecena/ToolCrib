<?php

namespace App\Services;

use App\Events\AlertCreated;
use App\Models\Alert;
use App\Models\Movement;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MovementService
{
    public function checkout(array $data): Movement
    {
        return DB::transaction(function () use ($data) {
            /** @var Tool $tool */
            $tool = Tool::lockForUpdate()->findOrFail($data['tool_id']);

            if ($tool->isBlocked()) {
                throw new RuntimeException('Herramienta bloqueada: revisar condición o mantenimiento.');
            }

            $qty = (int) ($data['qty'] ?? 1);
            if ($tool->qty_available < $qty) {
                throw new RuntimeException('Stock insuficiente. Disponible: '.$tool->qty_available);
            }

            $type = $tool->type === 'consumible' ? 'consume' : 'checkout';

            $movement = Movement::create([
                'tool_id' => $tool->id,
                'customer_id' => $data['customer_id'] ?? null,
                'operator_id' => $data['operator_id'] ?? null,
                'from_location_id' => $tool->location_id,
                'to_location_id' => $data['to_location_id'] ?? null,
                'type' => $type,
                'qty' => $qty,
                'work_order' => $data['work_order'] ?? null,
                'notes' => $data['notes'] ?? null,
                'occurred_at' => now(),
                'return_due_at' => $type === 'checkout' ? ($data['return_due_at'] ?? now()->addHours(8)) : null,
            ]);

            $tool->decrement('qty_available', $qty);

            if ($tool->type === 'durable' && $tool->life_cycles) {
                $tool->increment('used_cycles', $qty);
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
            $tool->increment('qty_available', $movement->qty);

            Movement::create([
                'tool_id' => $tool->id,
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

            $qty = (int) ($data['qty'] ?? 1);
            if ($tool->qty_available < $qty) {
                throw new RuntimeException('Stock insuficiente para scrap. Disponible: '.$tool->qty_available);
            }

            $movement = Movement::create([
                'tool_id' => $tool->id,
                'operator_id' => $data['operator_id'] ?? null,
                'from_location_id' => $tool->location_id,
                'type' => 'scrap',
                'qty' => $qty,
                'notes' => $data['notes'] ?? null,
                'occurred_at' => now(),
            ]);

            $tool->decrement('qty_available', $qty);
            $tool->decrement('qty_total', $qty);

            if ($tool->qty_total <= 0) {
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

            $to = (int) $data['to_location_id'];
            if (! $to) {
                throw new RuntimeException('Ubicación destino requerida.');
            }
            if ($tool->location_id === $to) {
                throw new RuntimeException('La herramienta ya está en esa ubicación.');
            }

            $movement = Movement::create([
                'tool_id' => $tool->id,
                'operator_id' => $data['operator_id'] ?? null,
                'from_location_id' => $tool->location_id,
                'to_location_id' => $to,
                'type' => 'transfer',
                'qty' => (int) ($data['qty'] ?? $tool->qty_available),
                'notes' => $data['notes'] ?? null,
                'occurred_at' => now(),
            ]);

            $tool->update(['location_id' => $to]);

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

            return $movement;
        });
    }

    public function scanOverdueReturns(): int
    {
        $overdue = Movement::openCheckouts()
            ->whereNotNull('return_due_at')
            ->where('return_due_at', '<', now())
            ->with('tool', 'customer')
            ->get();

        foreach ($overdue as $m) {
            $exists = Alert::where('movement_id', $m->id)
                ->where('type', 'no_devolucion')
                ->whereNull('resolved_at')
                ->exists();

            if ($exists) {
                continue;
            }

            $alert = Alert::create([
                'tool_id' => $m->tool_id,
                'user_id' => $m->customer_id,
                'movement_id' => $m->id,
                'type' => 'no_devolucion',
                'severity' => 'critical',
                'title' => 'Herramienta no devuelta',
                'message' => sprintf(
                    '%s tiene %s vencida desde %s',
                    optional($m->customer)->name ?? 'Usuario',
                    optional($m->tool)->name ?? 'herramienta',
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
