<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Caller details and response milestones for each incident
        Schema::table('incidents', function (Blueprint $table) {
            $table->string('caller_name')->nullable();
            $table->string('caller_contact')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('arrived_at')->nullable();   // first unit on scene
            $table->timestamp('controlled_at')->nullable(); // fire under control
            $table->timestamp('resolved_at')->nullable();  // fire out / operation complete
        });

        // Apparatus sent to an incident
        Schema::create('incident_apparatus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('apparatus_id')->constrained('apparatuses')->cascadeOnDelete();
            $table->timestamp('dispatched_at');
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });

        // Personnel sent to an incident
        Schema::create('incident_personnel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('Responder'); // Team Leader, Driver, Nozzleman, ...
            $table->timestamp('dispatched_at');
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });

        // Response tracking timeline (status changes and field notes)
        Schema::create('incident_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('stage')->nullable(); // null for note-only updates
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_updates');
        Schema::dropIfExists('incident_personnel');
        Schema::dropIfExists('incident_apparatus');

        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn(['caller_name', 'caller_contact', 'dispatched_at', 'arrived_at', 'controlled_at', 'resolved_at']);
        });
    }
};
