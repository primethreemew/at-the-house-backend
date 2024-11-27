<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); 
            $table->string('name')->nullable();
            $table->string('email')->unique(); 
            $table->string('phone')->nullable(); 
            $table->boolean('is_email_exist')->default(false);
            $table->string('gender')->nullable(); 
            $table->string('birthdate')->nullable();
            $table->string('otp')->nullable(); 
            $table->timestamp('email_verified_at')->nullable(); 
            $table->string('iso_code')->nullable(); 
            $table->string('country_code')->nullable();
            $table->string('address')->nullable(); 
            $table->string('suite_number')->nullable();
            $table->string('city')->nullable(); 
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('password')->nullable();
            $table->text('two_factor_secret')->nullable(); 
            $table->text('two_factor_recovery_codes')->nullable(); 
            $table->rememberToken(); 
            $table->foreignId('current_team_id')->nullable();
            $table->string('profile_photo', 191)->nullable();
            $table->enum('account_type', ['google', 'facebook', 'register'])->default('register'); // ENUM NOT NULL
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
