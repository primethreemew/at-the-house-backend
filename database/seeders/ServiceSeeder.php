<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            DB::table('services')->insert([
                'category_name' => 'Service ' . $i,
                'image' => 'service' . $i . '.jpg',
                'category_type' => 'Home',
                'recommended' => 'yes',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}