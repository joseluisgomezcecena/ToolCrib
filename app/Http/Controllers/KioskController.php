<?php

namespace App\Http\Controllers;

use App\Models\Movement;
use App\Models\Tool;
use App\Models\ToolItem;
use App\Models\User;
use App\Services\MovementService;
use Illuminate\Http\Request;

class KioskController extends Controller
{
    public function __construct(protected MovementService $service) {}

    public function index()
    {
        abort_unless(auth()->user()?->can('kiosk.operate'), 403);
        $locations = \App\Models\Location::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);
        return view('kiosk.index', compact('locations'));
    }

    public function lookupTool(Request $request)
    {
        $code = $request->string('code')->trim()->value();

        $item = ToolItem::where('tag', $code)->with('tool')->first();
        if ($item) {
            $tool = $item->tool;
            return response()->json([
                'id' => $tool->id,
                'item_id' => $item->id,
                'code' => $item->tag,
                'name' => $tool->name,
                'type' => $tool->type,
                'tracking_mode' => 'serialized',
                'qty_available' => $item->status === 'available' ? 1 : 0,
                'item_tag' => $item->tag,
                'item_status' => $item->status,
                'blocked' => $item->isBlocked() || $item->status !== 'available',
                'reason' => $this->blockedReason($item),
            ]);
        }

        $tool = Tool::where('code', $code)->first();
        abort_unless($tool, 404, 'Herramienta no encontrada');

        if ($tool->isSerialized()) {
            abort(422, 'Esta herramienta es serializada. Escanea el tag de una instancia específica, no el catálogo '.$tool->code.'.');
        }

        return response()->json([
            'id' => $tool->id,
            'item_id' => null,
            'code' => $tool->code,
            'name' => $tool->name,
            'type' => $tool->type,
            'tracking_mode' => 'bulk',
            'qty_available' => $tool->qty_available,
            'blocked' => $tool->isBlocked(),
            'reason' => $tool->isBlocked()
                ? 'Condición: '.$tool->condition.($tool->isMaintenanceDue() ? ', mantenimiento vencido' : '')
                : null,
        ]);
    }

    public function lookupEmployee(Request $request)
    {
        $code = $request->string('code')->trim()->value();
        $user = User::where('employee_code', $code)->where('is_active', true)->first();
        abort_unless($user, 404, 'Gafete no reconocido');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'department' => $user->department,
            'employee_code' => $user->employee_code,
        ]);
    }

    public function commit(Request $request)
    {
        abort_unless(auth()->user()?->can('kiosk.operate'), 403);

        $data = $request->validate([
            'tool_code' => 'required|string',
            'employee_code' => 'required|string',
            'qty' => 'required|integer|min:1',
            'tool_item_id' => 'nullable|exists:tool_items,id',
            'work_order' => 'nullable|string|max:120',
            'to_location_id' => 'required|exists:locations,id',
            'return_due_at' => 'nullable|date|after_or_equal:now',
        ]);

        $tool = null;
        if (! empty($data['tool_item_id'])) {
            $item = ToolItem::with('tool')->findOrFail($data['tool_item_id']);
            $tool = $item->tool;
        } else {
            $tool = Tool::where('code', $data['tool_code'])->firstOrFail();
        }

        $customer = User::where('employee_code', $data['employee_code'])->firstOrFail();

        try {
            $movement = $this->service->checkout([
                'tool_id' => $tool->id,
                'tool_item_id' => $data['tool_item_id'] ?? null,
                'customer_id' => $customer->id,
                'operator_id' => auth()->id(),
                'qty' => $data['qty'],
                'work_order' => $data['work_order'] ?? null,
                'to_location_id' => $data['to_location_id'],
                'return_due_at' => $data['return_due_at'] ?? null,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'movement_id' => $movement->id,
            'tool' => $tool->name,
            'tag' => $movement->toolItem?->tag,
            'customer' => $customer->name,
            'qty' => $movement->qty,
        ]);
    }

    public function lookupCheckin(Request $request)
    {
        abort_unless(auth()->user()?->can('movements.checkin'), 403);

        $code = $request->string('code')->trim()->value();

        $query = Movement::openCheckouts()
            ->with('tool', 'toolItem', 'customer', 'toLocation');

        $item = ToolItem::where('tag', $code)->first();
        if ($item) {
            $matches = $query->where('tool_item_id', $item->id)->get();
        } else {
            $tool = Tool::where('code', $code)->first();
            abort_unless($tool, 404, 'Código no encontrado');
            $matches = $query->where('tool_id', $tool->id)
                ->whereNull('tool_item_id')
                ->get();
        }

        if ($matches->isEmpty()) {
            abort(404, 'No hay salidas pendientes para devolver con ese código.');
        }

        return response()->json([
            'movements' => $matches->map(fn ($m) => [
                'id' => $m->id,
                'tool_name' => $m->tool->name,
                'tag' => $m->toolItem?->tag ?? $m->tool->code,
                'qty' => $m->qty,
                'customer' => $m->customer?->name ?? '—',
                'occurred_at' => $m->occurred_at->format('d/M H:i'),
                'return_due_at' => $m->return_due_at?->format('d/M H:i'),
                'overdue' => $m->return_due_at && $m->return_due_at->isPast(),
                'work_order' => $m->work_order,
                'destination' => $m->toLocation?->name,
            ]),
        ]);
    }

    public function commitCheckin(Request $request)
    {
        abort_unless(auth()->user()?->can('movements.checkin'), 403);

        $data = $request->validate([
            'movement_id' => 'required|exists:movements,id',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $movement = $this->service->checkin($data['movement_id'], auth()->id(), $data['notes'] ?? null);
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'movement_id' => $movement->id,
            'tool' => $movement->tool->name,
            'tag' => $movement->toolItem?->tag,
            'customer' => $movement->customer?->name,
        ]);
    }

    protected function blockedReason(ToolItem $item): ?string
    {
        if ($item->status !== 'available') {
            return 'Instancia en estado: '.$item->status;
        }
        if ($item->condition !== 'ok') {
            return 'Condición: '.$item->condition;
        }
        if ($item->isMaintenanceDue()) {
            return 'Mantenimiento vencido';
        }
        return null;
    }
}
