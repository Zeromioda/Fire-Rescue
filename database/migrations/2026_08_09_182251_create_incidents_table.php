<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('incidents', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Reporter
        $table->string('title');
        $table->text('description');
        $table->string('category')->default('Structural Fire');
        $table->string('severity'); // Low, Medium, High, Critical
        $table->string('location_address');
        $table->decimal('latitude', 10, 8)->nullable();
        $table->decimal('longitude', 11, 8)->nullable();
        $table->string('image_path')->nullable();
        $table->enum('status', ['Pending', 'Dispatched', 'Under Control', 'Resolved'])->default('Pending');
        $table->text('dispatcher_notes')->nullable();
        $table->text('after_action_report')->nullable(); // Final report from responders
        $table->timestamps();
    });
}

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
