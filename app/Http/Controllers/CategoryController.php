<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return view('categories.index', [
            'categories' => Category::withCount('tools')->orderBy('name')->paginate(30),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120|unique:categories,name',
            'description' => 'nullable|string|max:500',
        ]);
        Category::create($data);
        return back()->with('status', 'Categoría creada.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120|unique:categories,name,'.$category->id,
            'description' => 'nullable|string|max:500',
        ]);
        $category->update($data);
        return back()->with('status', 'Categoría actualizada.');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return back()->with('status', 'Categoría eliminada.');
    }
}
