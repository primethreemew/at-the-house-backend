<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'phone' => '+818012345678',
            'is_email_exist' => true,
            'gender' => 'male',
            'birthdate' => '1990-01-01',
            'otp' => '123456',
            'email_verified_at' => now(),
            'iso_code' => 'JP',
            'country_code' => '+81',
            'address' => '123 Main St',
            'suite_number' => 'Apt 1',
            'city' => 'Tokyo',
            'state' => 'Tokyo',
            'zip' => '10001',
            'password' => Hash::make('password'),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'remember_token' => Str::random(10),
            'current_team_id' => null,
            'profile_photo' => null,
            'account_type' => 'register',
        ]);

        User::create([
            'name' => 'Agent',
            'email' => 'agent@gmail.com',
            'phone' => '+818012345678',
            'is_email_exist' => true,
            'gender' => 'male',
            'birthdate' => '1990-01-01',
            'otp' => '123456',
            'email_verified_at' => now(),
            'iso_code' => 'JP',
            'country_code' => '+81',
            'address' => '123 Main St',
            'suite_number' => 'Apt 1',
            'city' => 'Tokyo',
            'state' => 'Tokyo',
            'zip' => '10001',
            'password' => Hash::make('password'),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'remember_token' => Str::random(10),
            'current_team_id' => null,
            'profile_photo' => null,
            'account_type' => 'register',
        ]);

        User::create([
            'name' => 'User',
            'email' => 'user@gmail.com',
            'phone' => '+818012345678',
            'is_email_exist' => true,
            'gender' => 'male',
            'birthdate' => '1990-01-01',
            'otp' => '123456',
            'email_verified_at' => now(),
            'iso_code' => 'JP',
            'country_code' => '+81',
            'address' => '123 Main St',
            'suite_number' => 'Apt 1',
            'city' => 'Tokyo',
            'state' => 'Tokyo',
            'zip' => '10001',
            'password' => Hash::make('password'),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'remember_token' => Str::random(10),
            'current_team_id' => null,
            'profile_photo' => null,
            'account_type' => 'register',
        ]);

        for ($i = 0; $i < 7; $i++) {
            User::create([
                'name' => fake()->name(),
                'email' => fake()->unique()->email(),
                'phone' => fake()->unique()->phoneNumber(),
                'is_email_exist' => fake()->boolean(),
                'gender' => fake()->randomElement(['male', 'female']),
                'birthdate' => fake()->date(),
                'otp' => fake()->randomNumber(6, true),
                'email_verified_at' => now(),
                'iso_code' => fake()->randomElement(['JP', 'US']),
                'country_code' => fake()->randomElement(['+81', '+1']),
                'suite_number' => fake()->randomNumber(4, true),
                'city' => fake()->city(),
                'state' => fake()->state(),
                'zip' => fake()->postcode(),
                'password' => Hash::make('password'),
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'remember_token' => Str::random(10),
                'current_team_id' => null,
                'profile_photo' => null,
                'account_type' => 'register',
            ]);
        }
    }
}