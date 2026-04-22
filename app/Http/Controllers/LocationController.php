<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index()
    {
        return view('locations.index', [
            'locations' => Location::with('parent')->orderBy('name')->paginate(50),
            'all' => Location::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Location::create($data);
        return back()->with('status', 'Ubicación creada.');
    }

    public function update(Request $request, Location $location)
    {
        $data = $this->validated($request, $location->id);
        $location->update($data);
        return back()->with('status', 'Ubicación actualizada.');
    }

    public function destroy(Location $location)
    {
        $location->delete();
        return back()->with('status', 'Ubicación eliminada.');
    }

    protected function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:120',
            'code' => 'required|string|max:64|unique:locations,code'.($id ? ",{$id}" : ''),
            'type' => 'required|in:almacen,area,linea,maquina,kit',
            'parent_id' => 'nullable|exists:locations,id',
            'is_active' => 'nullable|boolean',
        ]);
    }
}
