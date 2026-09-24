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
        Schema::create('backlogs', function (Blueprint $table) {
    $table->id();
    $table->string('ticket_number')->unique(); // e.g., BL-178-001
    $table->string('title'); // e.g., Unverified Smoke Report / Hydrant Flow Repair
    $table->enum('category', ['Emergency Incident', 'Apparatus Repair', 'Equipment Maintenance', 'General Task'])->default('General Task');
    $table->enum('priority', ['Low', 'Medium', 'High', 'Critical'])->default('Medium');
    $table->enum('status', ['Pending', 'In Progress', 'Deferred', 'Resolved'])->default('Pending');
    $table->text('description')->nullable();
    $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backlogs');
    }
};
