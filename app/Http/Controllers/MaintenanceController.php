<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use App\Models\Tool;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function index()
    {
        return view('maintenances.index', [
            'maintenances' => Maintenance::with('tool', 'performer')
                ->orderBy('scheduled_at')
                ->paginate(25),
        ]);
    }

    public function create()
    {
        abort_unless(auth()->user()->can('maintenances.manage'), 403);
        return view('maintenances.create', [
            'tools' => Tool::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('maintenances.manage'), 403);
        $data = $request->validate([
            'tool_id' => 'required|exists:tools,id',
            'type' => 'required|in:calibracion,preventivo,correctivo,inspeccion',
            'scheduled_at' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);
        $data['status'] = 'pendiente';
        Maintenance::create($data);

        Tool::where('id', $data['tool_id'])
            ->update(['next_maintenance_at' => $data['scheduled_at']]);

        return redirect()->route('maintenances.index')->with('status', 'Mantenimiento programado.');
    }

    public function complete(Maintenance $maintenance, Request $request)
    {
        abort_unless(auth()->user()->can('maintenances.manage'), 403);

        $data = $request->validate([
            'cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $maintenance->update([
            'status' => 'completado',
            'completed_at' => now(),
            'cost' => $data['cost'] ?? null,
            'notes' => $data['notes'] ?? $maintenance->notes,
            'performed_by' => auth()->id(),
        ]);

        $maintenance->tool->update([
            'last_maintenance_at' => now()->toDateString(),
            'next_maintenance_at' => null,
        ]);

        return back()->with('status', 'Mantenimiento completado.');
    }
}
