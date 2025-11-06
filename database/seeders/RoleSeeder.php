<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['role_name' => 'admin']);
        Role::firstOrCreate(['role_name' => 'client']);
    }
}