<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Movement;
use App\Models\Tool;
use App\Models\User;
use App\Services\MovementService;
use Illuminate\Http\Request;

class MovementController extends Controller
{
    public function __construct(protected MovementService $service) {}

    public function index(Request $request)
    {
        $q = Movement::query()->with('tool', 'customer', 'operator');

        if ($type = $request->string('type')->value()) {
            $q->where('type', $type);
        }
        if ($request->boolean('open')) {
            $q->openCheckouts();
        }
        if ($term = $request->string('q')->trim()->value()) {
            $q->whereHas('tool', fn ($x) => $x->where('name', 'like', "%{$term}%")
                                              ->orWhere('code', 'like', "%{$term}%"));
        }

        $movements = $q->latest('occurred_at')->paginate(25)->withQueryString();

        return view('movements.index', compact('movements'));
    }

    public function createCheckout()
    {
        abort_unless(auth()->user()->can('movements.checkout'), 403);
        return view('movements.checkout', [
            'tools' => Tool::where('is_active', true)->orderBy('name')->get(),
            'customers' => User::role('cliente')->orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
        ]);
    }

    public function storeCheckout(Request $request)
    {
        abort_unless(auth()->user()->can('movements.checkout'), 403);

        $data = $request->validate([
            'tool_code' => 'required|string',
            'customer_code' => 'nullable|string',
            'qty' => 'required|integer|min:1',
            'work_order' => 'nullable|string|max:120',
            'to_location_id' => 'required|exists:locations,id',
            'return_due_at' => 'nullable|date|after_or_equal:now',
            'notes' => 'nullable|string|max:500',
        ]);

        $tool = Tool::where('code', $data['tool_code'])->firstOrFail();

        $customer = null;
        if (! empty($data['customer_code'])) {
            $customer = User::where('employee_code', $data['customer_code'])
                ->orWhere('email', $data['customer_code'])
                ->first();
            abort_unless($customer, 422, 'Cliente no encontrado por gafete.');
        }

        try {
            $movement = $this->service->checkout([
                'tool_id' => $tool->id,
                'customer_id' => $customer?->id,
                'operator_id' => auth()->id(),
                'qty' => $data['qty'],
                'work_order' => $data['work_order'] ?? null,
                'to_location_id' => $data['to_location_id'] ?? null,
                'return_due_at' => $data['return_due_at'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['tool_code' => $e->getMessage()])->withInput();
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'movement_id' => $movement->id]);
        }

        return redirect()->route('movements.index')->with('status', 'Salida registrada.');
    }

    public function checkin(Request $request, Movement $movement)
    {
        abort_unless(auth()->user()->can('movements.checkin'), 403);

        try {
            $this->service->checkin($movement->id, auth()->id(), $request->input('notes'));
        } catch (\RuntimeException $e) {
            return back()->withErrors(['movement' => $e->getMessage()]);
        }

        return back()->with('status', 'Devolución registrada.');
    }

    public function storeScrap(Request $request, \App\Models\Tool $tool)
    {
        abort_unless(auth()->user()->can('movements.checkout') || auth()->user()->can('tools.update'), 403);

        $data = $request->validate([
            'qty' => 'required|integer|min:1',
            'notes' => 'required|string|max:500',
        ]);

        try {
            $this->service->scrap([
                'tool_id' => $tool->id,
                'operator_id' => auth()->id(),
                'qty' => $data['qty'],
                'notes' => $data['notes'],
            ]);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['scrap' => $e->getMessage()]);
        }

        return back()->with('status', 'Registrado como scrap.');
    }

    public function storeTransfer(Request $request, \App\Models\Tool $tool)
    {
        abort_unless(auth()->user()->can('movements.transfer'), 403);

        $data = $request->validate([
            'to_location_id' => 'required|exists:locations,id',
            'qty' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->service->transfer([
                'tool_id' => $tool->id,
                'operator_id' => auth()->id(),
                'to_location_id' => $data['to_location_id'],
                'qty' => $data['qty'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['transfer' => $e->getMessage()]);
        }

        return back()->with('status', 'Transferencia registrada.');
    }

    public function storeReceipt(Request $request, \App\Models\Tool $tool)
    {
        abort_unless(auth()->user()->can('tools.update'), 403);

        $data = $request->validate([
            'qty' => 'required|integer|min:1',
            'purchase_order' => 'nullable|string|max:120',
            'unit_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->service->receipt([
                'tool_id' => $tool->id,
                'operator_id' => auth()->id(),
                'qty' => $data['qty'],
                'purchase_order' => $data['purchase_order'] ?? null,
                'unit_cost' => $data['unit_cost'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['receipt' => $e->getMessage()]);
        }

        return back()->with('status', 'Ingreso a inventario registrado.');
    }

    public function myMovements()
    {
        $movements = Movement::where('customer_id', auth()->id())
            ->with('tool', 'operator')
            ->latest('occurred_at')
            ->paginate(20);

        return view('movements.mine', compact('movements'));
    }
}
