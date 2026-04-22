<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $q = Alert::query()->with('tool', 'user');

        if ($request->boolean('unresolved', true)) {
            $q->unresolved();
        }
        if ($type = $request->string('type')->value()) {
            $q->where('type', $type);
        }
        if ($sev = $request->string('severity')->value()) {
            $q->where('severity', $sev);
        }

        return view('alerts.index', [
            'alerts' => $q->latest()->paginate(30)->withQueryString(),
        ]);
    }

    public function markRead(Alert $alert)
    {
        $alert->markAsRead();
        return back();
    }

    public function resolve(Alert $alert)
    {
        abort_unless(auth()->user()->can('alerts.resolve'), 403);
        $alert->update(['resolved_at' => now(), 'read_at' => $alert->read_at ?? now()]);
        return back()->with('status', 'Alerta resuelta.');
    }
}
