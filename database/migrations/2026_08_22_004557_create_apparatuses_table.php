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
        Schema::create('apparatuses', function (Blueprint $table) {
            $table->id();
            $table->string('call_sign'); // e.g., Engine 1, Rescue Truck 2, Tanker 1
            $table->string('plate_number')->unique();
            $table->string('type'); // Changed from enum to string for flexibility
            $table->string('status')->default('In Service'); // Changed from enum to string to avoid SQLite CHECK constraints
            $table->integer('water_capacity_liters')->default(0);
            $table->integer('fuel_level_percent')->default(100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apparatuses');
    }
};