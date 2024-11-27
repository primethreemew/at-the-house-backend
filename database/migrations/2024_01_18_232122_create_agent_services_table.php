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
        Schema::create('agent_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Foreign key with cascade delete
            $table->foreignId('category_id')->constrained('services')->onDelete('cascade'); // Foreign key with cascade delete
            $table->string('service_name');
            $table->text('short_description');
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zipcode')->nullable();
            $table->string('website')->nullable();
            $table->string('message_number');
            $table->string('phone_number');
            $table->string('featured_image')->nullable();
            $table->string('banner_image')->nullable();
            $table->text('hours');
            $table->enum('service_type', ['popular', 'most_demanding', 'normal'])->default('normal');
            $table->text('latitude')->nullable();
            $table->text('longitude')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_services');
    }
};
