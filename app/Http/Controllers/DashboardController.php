<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Movement;
use App\Models\Tool;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('cliente')) {
            return redirect()->route('mis.movimientos');
        }

        $stats = [
            'tools_total' => Tool::count(),
            'tools_available' => Tool::sum('qty_available'),
            'tools_low_stock' => Tool::whereColumn('qty_available', '<=', 'stock_min')->count(),
            'open_checkouts' => Movement::openCheckouts()->count(),
            'overdue' => Movement::openCheckouts()
                ->whereNotNull('return_due_at')
                ->where('return_due_at', '<', now())
                ->count(),
            'alerts_unresolved' => Alert::unresolved()->count(),
        ];

        $alerts = Alert::unresolved()
            ->with('tool', 'user')
            ->latest()
            ->limit(10)
            ->get();

        $recent = Movement::with('tool', 'customer', 'operator')
            ->latest('occurred_at')
            ->limit(10)
            ->get();

        return view('dashboard', compact('stats', 'alerts', 'recent'));
    }
}
