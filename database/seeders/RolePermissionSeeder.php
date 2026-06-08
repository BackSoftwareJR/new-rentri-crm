<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['admin', 'segreteria', 'operatore', 'editor'];

        foreach ($roles as $role) {
            Role::findOrCreate($role);
        }

        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Amministratore',
                'password' => 'password',
            ]
        );

        $admin->syncRoles(['admin']);

        $segreteria = User::updateOrCreate(
            ['email' => 'segreteria@example.com'],
            [
                'name' => 'Segreteria',
                'password' => 'password',
            ]
        );
        $segreteria->syncRoles(['segreteria']);

        $operatore = User::updateOrCreate(
            ['email' => 'operatore@example.com'],
            [
                'name' => 'Operatore',
                'password' => 'password',
            ]
        );
        $operatore->syncRoles(['operatore']);
    }
}
