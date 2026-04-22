<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index', [
            'users' => User::with('roles')->orderBy('name')->paginate(25),
        ]);
    }

    public function create()
    {
        return view('users.create', ['roles' => Role::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'employee_code' => 'nullable|string|max:64|unique:users,employee_code',
            'department' => 'nullable|string|max:120',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
            'is_active' => 'nullable|boolean',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'employee_code' => $data['employee_code'] ?? null,
            'department' => $data['department'] ?? null,
            'password' => Hash::make($data['password']),
            'is_active' => $data['is_active'] ?? true,
            'email_verified_at' => now(),
        ]);
        $user->syncRoles([$data['role']]);

        return redirect()->route('users.index')->with('status', 'Usuario creado.');
    }

    public function edit(User $user)
    {
        return view('users.edit', [
            'user' => $user,
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'employee_code' => 'nullable|string|max:64|unique:users,employee_code,'.$user->id,
            'department' => 'nullable|string|max:120',
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
            'is_active' => 'nullable|boolean',
        ]);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'employee_code' => $data['employee_code'] ?? null,
            'department' => $data['department'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();
        $user->syncRoles([$data['role']]);

        return redirect()->route('users.index')->with('status', 'Usuario actualizado.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'No puedes eliminarte a ti mismo.']);
        }
        $user->delete();
        return back()->with('status', 'Usuario eliminado.');
    }
}
