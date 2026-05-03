<?php

namespace App\Http\Controllers;

use App\Models\Movement;
use App\Models\Tool;
use App\Services\MovementService;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function __construct(protected MovementService $service) {}

    public function index(Request $request)
    {
        $q = Movement::query()->where('type', 'receipt')->with('tool', 'operator');

        if ($po = $request->string('po')->trim()->value()) {
            $q->where('work_order', 'like', "%{$po}%");
        }
        if ($toolId = $request->integer('tool_id')) {
            $q->where('tool_id', $toolId);
        }

        $purchases = $q->latest('occurred_at')->paginate(20)->withQueryString();
        $tools = Tool::orderBy('name')->get(['id', 'name', 'code']);

        return view('purchases.index', compact('purchases', 'tools'));
    }

    public function create()
    {
        abort_unless(auth()->user()->can('tools.update'), 403);
        $tools = Tool::where('is_active', true)->orderBy('name')->get();
        return view('purchases.create', compact('tools'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('tools.update'), 403);

        $data = $request->validate([
            'tool_id' => 'required|exists:tools,id',
            'qty' => 'required|integer|min:1',
            'purchase_order' => 'nullable|string|max:120',
            'unit_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->service->receipt([
                'tool_id' => $data['tool_id'],
                'operator_id' => auth()->id(),
                'qty' => $data['qty'],
                'purchase_order' => $data['purchase_order'] ?? null,
                'unit_cost' => $data['unit_cost'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['receipt' => $e->getMessage()])->withInput();
        }

        return redirect()->route('purchases.index')->with('status', 'Compra registrada en inventario.');
    }
}
