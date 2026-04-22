<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Usuarios
            'users.view', 'users.create', 'users.update', 'users.delete',
            // Catálogos
            'categories.manage', 'locations.manage',
            // Herramientas
            'tools.view', 'tools.create', 'tools.update', 'tools.delete',
            // Movimientos
            'movements.view', 'movements.checkout', 'movements.checkin', 'movements.transfer',
            'movements.view.own',
            // Mantenimientos
            'maintenances.view', 'maintenances.manage',
            // Alertas
            'alerts.view', 'alerts.resolve',
            // Reportes
            'reports.view',
            // Kiosko
            'kiosk.operate',
            // Configuración
            'settings.manage',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        $toolcrib = Role::firstOrCreate(['name' => 'toolcrib', 'guard_name' => 'web']);
        $toolcrib->syncPermissions([
            'tools.view', 'tools.update',
            'categories.manage', 'locations.manage',
            'movements.view', 'movements.checkout', 'movements.checkin', 'movements.transfer',
            'maintenances.view', 'maintenances.manage',
            'alerts.view', 'alerts.resolve',
            'reports.view',
            'kiosk.operate',
        ]);

        $cliente = Role::firstOrCreate(['name' => 'cliente', 'guard_name' => 'web']);
        $cliente->syncPermissions([
            'tools.view',
            'movements.view.own',
        ]);
    }
}
