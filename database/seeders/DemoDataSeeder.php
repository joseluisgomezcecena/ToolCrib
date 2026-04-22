<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Location;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@daseo.co'],
            [
                'name' => 'Super Admin',
                'employee_code' => 'ADM-0001',
                'department' => 'Sistemas',
                'password' => Hash::make('admin1234'),
                'email_verified_at' => now(),
            ],
        );
        $admin->syncRoles(['super_admin']);

        $toolcribUser = User::firstOrCreate(
            ['email' => 'toolcrib@daseo.co'],
            [
                'name' => 'Operador Toolcrib',
                'employee_code' => 'TC-0001',
                'department' => 'Tool Crib',
                'password' => Hash::make('toolcrib1234'),
                'email_verified_at' => now(),
            ],
        );
        $toolcribUser->syncRoles(['toolcrib']);

        $cliente = User::firstOrCreate(
            ['email' => 'cliente@daseo.co'],
            [
                'name' => 'Juan Pérez',
                'employee_code' => 'OP-0001',
                'department' => 'Producción',
                'password' => Hash::make('cliente1234'),
                'email_verified_at' => now(),
            ],
        );
        $cliente->syncRoles(['cliente']);

        $cats = collect([
            ['name' => 'Herramientas de corte', 'description' => 'Brocas, insertos, fresas'],
            ['name' => 'Medición', 'description' => 'Calibradores, micrómetros, patrones'],
            ['name' => 'Herramientas manuales', 'description' => 'Llaves, desarmadores, pinzas'],
            ['name' => 'EPP', 'description' => 'Equipo de protección personal'],
            ['name' => 'Consumibles', 'description' => 'Guantes, trapos, lubricantes'],
        ])->map(fn ($c) => Category::firstOrCreate(['name' => $c['name']], $c));

        $almacen = Location::firstOrCreate(
            ['code' => 'ALM-01'],
            ['name' => 'Almacén Central', 'type' => 'almacen'],
        );
        $lineaA = Location::firstOrCreate(
            ['code' => 'LIN-A'],
            ['name' => 'Línea A', 'type' => 'linea', 'parent_id' => $almacen->id],
        );
        $lineaB = Location::firstOrCreate(
            ['code' => 'LIN-B'],
            ['name' => 'Línea B', 'type' => 'linea', 'parent_id' => $almacen->id],
        );
        Location::firstOrCreate(
            ['code' => 'MAQ-A1'],
            ['name' => 'CNC-01', 'type' => 'maquina', 'parent_id' => $lineaA->id],
        );
        Location::firstOrCreate(
            ['code' => 'MAQ-B1'],
            ['name' => 'Torno-03', 'type' => 'maquina', 'parent_id' => $lineaB->id],
        );

        $tools = [
            ['code' => 'TL-0001', 'name' => 'Calibrador digital 150 mm', 'type' => 'durable',
             'qty_total' => 5, 'qty_available' => 5, 'stock_min' => 1, 'unit_cost' => 1200],
            ['code' => 'TL-0002', 'name' => 'Micrómetro 0-25 mm', 'type' => 'durable',
             'qty_total' => 3, 'qty_available' => 3, 'stock_min' => 1, 'unit_cost' => 2500],
            ['code' => 'TL-0003', 'name' => 'Broca HSS 1/4"', 'type' => 'consumible',
             'qty_total' => 100, 'qty_available' => 100, 'stock_min' => 20, 'unit_cost' => 45],
            ['code' => 'TL-0004', 'name' => 'Llave Allen 5 mm', 'type' => 'durable',
             'qty_total' => 10, 'qty_available' => 10, 'stock_min' => 2, 'unit_cost' => 35],
            ['code' => 'TL-0005', 'name' => 'Guantes nitrilo caja 100', 'type' => 'consumible',
             'qty_total' => 50, 'qty_available' => 50, 'stock_min' => 10, 'unit_cost' => 180],
        ];

        foreach ($tools as $i => $t) {
            Tool::firstOrCreate(
                ['code' => $t['code']],
                array_merge($t, [
                    'category_id' => $cats->get($i % $cats->count())->id,
                    'location_id' => $almacen->id,
                    'condition' => 'ok',
                    'is_active' => true,
                ]),
            );
        }
    }
}
