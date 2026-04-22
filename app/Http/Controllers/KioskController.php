<?php

namespace App\Http\Controllers;

use App\Models\Tool;
use App\Models\User;
use App\Services\MovementService;
use Illuminate\Http\Request;

class KioskController extends Controller
{
    public function __construct(protected MovementService $service) {}

    public function index()
    {
        abort_unless(auth()->user()?->can('kiosk.operate'), 403);
        return view('kiosk.index');
    }

    public function lookupTool(Request $request)
    {
        $code = $request->string('code')->trim()->value();
        $tool = Tool::where('code', $code)->first();
        abort_unless($tool, 404, 'Herramienta no encontrada');

        return response()->json([
            'id' => $tool->id,
            'code' => $tool->code,
            'name' => $tool->name,
            'type' => $tool->type,
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
            'work_order' => 'nullable|string|max:120',
            'machine' => 'nullable|string|max:120',
            'return_due_at' => 'nullable|date|after_or_equal:now',
        ]);

        $tool = Tool::where('code', $data['tool_code'])->firstOrFail();
        $customer = User::where('employee_code', $data['employee_code'])->firstOrFail();

        try {
            $movement = $this->service->checkout([
                'tool_id' => $tool->id,
                'customer_id' => $customer->id,
                'operator_id' => auth()->id(),
                'qty' => $data['qty'],
                'work_order' => $data['work_order'] ?? null,
                'machine' => $data['machine'] ?? null,
                'return_due_at' => $data['return_due_at'] ?? null,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'movement_id' => $movement->id,
            'tool' => $tool->name,
            'customer' => $customer->name,
            'qty' => $movement->qty,
        ]);
    }
}
