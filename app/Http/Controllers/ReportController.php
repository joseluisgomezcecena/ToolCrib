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
        $groupBy = $request->string('group_by')->value() ?: 'location';

        if ($groupBy === 'work_order') {
            $rows = Movement::query()
                ->selectRaw('work_order as bucket, SUM(qty) as total, COUNT(*) as movs')
                ->whereIn('type', ['checkout', 'consume'])
                ->whereBetween('occurred_at', [$from, $to])
                ->whereNotNull('work_order')
                ->groupBy('work_order')
                ->orderByDesc('total')
                ->paginate(20, ['*'], 'bucketPage')
                ->withQueryString();
        } else {
            $rows = Movement::query()
                ->leftJoin('locations', 'locations.id', '=', 'movements.to_location_id')
                ->selectRaw('locations.name as bucket, locations.type as loc_type, SUM(movements.qty) as total, COUNT(*) as movs')
                ->whereIn('movements.type', ['checkout', 'consume'])
                ->whereBetween('movements.occurred_at', [$from, $to])
                ->groupBy('locations.id', 'locations.name', 'locations.type')
                ->orderByDesc('total')
                ->paginate(20, ['*'], 'bucketPage')
                ->withQueryString();
        }

        $byTool = Movement::query()
            ->join('tools', 'tools.id', '=', 'movements.tool_id')
            ->selectRaw('tools.id, tools.name, tools.code, SUM(movements.qty) as total, SUM(tools.unit_cost * movements.qty) as cost')
            ->whereIn('movements.type', ['checkout', 'consume'])
            ->whereBetween('movements.occurred_at', [$from, $to])
            ->groupBy('tools.id', 'tools.name', 'tools.code')
            ->orderByDesc('total')
            ->paginate(20, ['*'], 'toolPage')
            ->withQueryString();

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
            ->paginate(15, ['*'], 'mostPage')
            ->withQueryString();

        $least = Tool::query()
            ->leftJoin('movements', function ($j) {
                $j->on('movements.tool_id', '=', 'tools.id')
                  ->whereIn('movements.type', ['checkout', 'consume']);
            })
            ->selectRaw('tools.*, COUNT(movements.id) as uses')
            ->groupBy('tools.id')
            ->orderBy('uses')
            ->paginate(15, ['*'], 'leastPage')
            ->withQueryString();

        return view('reports.usage', compact('most', 'least'));
    }
}
