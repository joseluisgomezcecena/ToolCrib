<?php

namespace App\Http\Controllers;

use App\Models\Tool;
use App\Models\ToolItem;
use App\Services\MovementService;
use Illuminate\Http\Request;

class ToolItemController extends Controller
{
    public function __construct(protected MovementService $service) {}

    public function store(Request $request, Tool $tool)
    {
        abort_unless(auth()->user()->can('tools.update'), 403);

        $data = $request->validate([
            'count' => 'nullable|integer|min:1|max:500',
            'tag' => 'nullable|string|max:64|unique:tool_items,tag',
        ]);

        if (! empty($data['tag'])) {
            ToolItem::create([
                'tool_id' => $tool->id,
                'tag' => $data['tag'],
                'status' => 'available',
                'condition' => 'ok',
                'location_id' => $tool->location_id,
            ]);
            $tool->increment('qty_total');
            $tool->increment('qty_available');
        } else {
            $count = (int) ($data['count'] ?? 1);
            $this->service->generateItems($tool, $count);
            $tool->increment('qty_total', $count);
            $tool->increment('qty_available', $count);
        }

        return back()->with('status', 'Instancia(s) creada(s).');
    }

    public function update(Request $request, Tool $tool, ToolItem $item)
    {
        abort_unless(auth()->user()->can('tools.update'), 403);
        abort_unless($item->tool_id === $tool->id, 404);

        $data = $request->validate([
            'tag' => 'required|string|max:64|unique:tool_items,tag,'.$item->id,
            'status' => 'required|in:available,in_use,maintenance,lost,scrapped',
            'condition' => 'required|in:ok,danado,scrap',
            'location_id' => 'nullable|exists:locations,id',
            'next_maintenance_at' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $prevStatus = $item->status;
        $item->update($data);

        if ($prevStatus !== $data['status']) {
            if ($prevStatus === 'available' && $data['status'] !== 'available') {
                $tool->decrement('qty_available');
            } elseif ($prevStatus !== 'available' && $data['status'] === 'available') {
                $tool->increment('qty_available');
            }
        }

        return back()->with('status', 'Instancia actualizada.');
    }

    public function destroy(Tool $tool, ToolItem $item)
    {
        abort_unless(auth()->user()->can('tools.delete'), 403);
        abort_unless($item->tool_id === $tool->id, 404);

        if ($item->status === 'available') {
            $tool->decrement('qty_available');
        }
        $tool->decrement('qty_total');

        $item->delete();
        return back()->with('status', 'Instancia eliminada.');
    }
}
