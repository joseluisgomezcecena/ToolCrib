<?php

namespace App\Http\Controllers;

use App\Models\Movement;
use App\Models\Tool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function consumption(Request $request)
    {
        $from = $request->date('from') ?? now()->subMonth();
        $to = $request->date('to') ?? now();
        $groupBy = $request->string('group_by')->value() ?: 'machine';

        $column = match ($groupBy) {
            'work_order' => 'work_order',
            'line' => 'to_location_id',
            default => 'machine',
        };

        $rows = Movement::query()
            ->selectRaw("$column as bucket, SUM(qty) as total, COUNT(*) as movs")
            ->whereIn('type', ['checkout', 'consume'])
            ->whereBetween('occurred_at', [$from, $to])
            ->groupBy('bucket')
            ->orderByDesc('total')
            ->limit(50)
            ->get();

        $byTool = Movement::query()
            ->join('tools', 'tools.id', '=', 'movements.tool_id')
            ->selectRaw('tools.id, tools.name, tools.code, SUM(movements.qty) as total, SUM(tools.unit_cost * movements.qty) as cost')
            ->whereIn('movements.type', ['checkout', 'consume'])
            ->whereBetween('movements.occurred_at', [$from, $to])
            ->groupBy('tools.id', 'tools.name', 'tools.code')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        return view('reports.consumption', compact('rows', 'byTool', 'from', 'to', 'groupBy'));
    }

    public function usage()
    {
        $most = Tool::query()
            ->leftJoin('movements', function ($j) {
                $j->on('movements.tool_id', '=', 'tools.id')
                  ->whereIn('movements.type', ['checkout', 'consume']);
            })
            ->selectRaw('tools.*, COUNT(movements.id) as uses')
            ->groupBy('tools.id')
            ->orderByDesc('uses')
            ->limit(15)
            ->get();

        $least = Tool::query()
            ->leftJoin('movements', function ($j) {
                $j->on('movements.tool_id', '=', 'tools.id')
                  ->whereIn('movements.type', ['checkout', 'consume']);
            })
            ->selectRaw('tools.*, COUNT(movements.id) as uses')
            ->groupBy('tools.id')
            ->orderBy('uses')
            ->limit(15)
            ->get();

        return view('reports.usage', compact('most', 'least'));
    }
}
