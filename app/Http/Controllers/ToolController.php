<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Location;
use App\Models\Tool;
use Illuminate\Http\Request;

class ToolController extends Controller
{
    public function index(Request $request)
    {
        $q = Tool::query()->with('category', 'location');

        if ($term = $request->string('q')->trim()->value()) {
            $q->where(function ($x) use ($term) {
                $x->where('name', 'like', "%{$term}%")
                  ->orWhere('code', 'like', "%{$term}%");
            });
        }
        if ($cat = $request->integer('category_id')) {
            $q->where('category_id', $cat);
        }
        if ($type = $request->string('type')->value()) {
            $q->where('type', $type);
        }
        if ($request->boolean('low_stock')) {
            $q->whereColumn('qty_available', '<=', 'stock_min');
        }

        $tools = $q->orderBy('name')->paginate(20)->withQueryString();

        return view('tools.index', [
            'tools' => $tools,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        $this->authorizeCreate();
        return view('tools.create', [
            'categories' => Category::orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeCreate();
        $data = $this->validated($request);
        Tool::create($data);
        return redirect()->route('tools.index')->with('status', 'Herramienta creada.');
    }

    public function show(Tool $tool)
    {
        $tool->load('category', 'location', 'movements.customer', 'movements.operator', 'maintenances');
        $locations = Location::where('is_active', true)->orderBy('name')->get();
        return view('tools.show', compact('tool', 'locations'));
    }

    public function edit(Tool $tool)
    {
        $this->authorizeUpdate();
        return view('tools.edit', [
            'tool' => $tool,
            'categories' => Category::orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Tool $tool)
    {
        $this->authorizeUpdate();
        $data = $this->validated($request, $tool->id);
        $tool->update($data);
        return redirect()->route('tools.show', $tool)->with('status', 'Actualizado.');
    }

    public function destroy(Tool $tool)
    {
        abort_unless(auth()->user()->can('tools.delete'), 403);
        $tool->delete();
        return redirect()->route('tools.index')->with('status', 'Eliminada.');
    }

    protected function authorizeCreate(): void
    {
        abort_unless(auth()->user()->can('tools.create'), 403);
    }

    protected function authorizeUpdate(): void
    {
        abort_unless(auth()->user()->can('tools.update'), 403);
    }

    protected function validated(Request $request, ?int $toolId = null): array
    {
        return $request->validate([
            'code' => 'required|string|max:64|unique:tools,code'.($toolId ? ",{$toolId}" : ''),
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'location_id' => 'nullable|exists:locations,id',
            'type' => 'required|in:durable,consumible',
            'condition' => 'required|in:ok,danado,scrap',
            'qty_total' => 'required|integer|min:0',
            'qty_available' => 'required|integer|min:0',
            'stock_min' => 'required|integer|min:0',
            'stock_max' => 'nullable|integer|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'life_cycles' => 'nullable|integer|min:0',
            'next_maintenance_at' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);
    }
}
