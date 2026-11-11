<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            Role::ROLE_ADMIN,
            Role::ROLE_DOCTOR,
            Role::ROLE_RECEPTIONIST,
            Role::ROLE_PATIENT,
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['role_name' => $roleName]);
        }
    }
}
