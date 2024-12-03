<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;
use App\Models\Role;

class RoleUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::find(1);
        $admin->roles()->attach(1);

        $agent = User::find(2);
        $agent->roles()->attach(2);
        
        $users = User::whereNotIn('id', [1, 2])->get();
        foreach ($users as $user) {
            $user->roles()->attach(3);
        }
    }
}