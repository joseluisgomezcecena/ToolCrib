@csrf
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div><label class="block text-sm font-medium">Nombre *</label>
        <input name="name" value="{{ old('name', $user->name ?? '') }}" required class="mt-1 w-full border rounded-md px-3 py-2"></div>
    <div><label class="block text-sm font-medium">Email *</label>
        <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required class="mt-1 w-full border rounded-md px-3 py-2"></div>
    <div><label class="block text-sm font-medium">Gafete / código</label>
        <input name="employee_code" value="{{ old('employee_code', $user->employee_code ?? '') }}" class="mt-1 w-full border rounded-md px-3 py-2 font-mono"></div>
    <div><label class="block text-sm font-medium">Departamento</label>
        <input name="department" value="{{ old('department', $user->department ?? '') }}" class="mt-1 w-full border rounded-md px-3 py-2"></div>
    <div><label class="block text-sm font-medium">Contraseña {{ isset($user) ? '(opcional)' : '*' }}</label>
        <input type="password" name="password" class="mt-1 w-full border rounded-md px-3 py-2" {{ isset($user) ? '' : 'required' }}></div>
    <div><label class="block text-sm font-medium">Confirmar</label>
        <input type="password" name="password_confirmation" class="mt-1 w-full border rounded-md px-3 py-2"></div>
    <div><label class="block text-sm font-medium">Rol *</label>
        <select name="role" class="mt-1 w-full border rounded-md px-3 py-2" required>
            @foreach($roles as $r)
                <option value="{{ $r->name }}" @selected(old('role', isset($user) ? $user->getRoleNames()->first() : '') === $r->name)>{{ $r->name }}</option>
            @endforeach
        </select>
    </div>
    <label class="inline-flex items-center gap-2 mt-6">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true))>
        <span class="text-sm">Activo</span>
    </label>
</div>
<div class="flex justify-end gap-2 mt-6">
    <a href="{{ route('users.index') }}" class="px-4 py-2 text-sm border rounded-md">Cancelar</a>
    <button class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-md">Guardar</button>
</div>
