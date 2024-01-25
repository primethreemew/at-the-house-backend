<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if the role exists before creating
        if (!Role::where('name', 'admin')->exists()) {
            Role::create(['name' => 'admin']);
        }


        if (!Role::where('name', 'agent')->exists()) {
            Role::create(['name' => 'agent']);
        }

        if (!Role::where('name', 'user')->exists()) {
            Role::create(['name' => 'user']);
        }
    }
}
