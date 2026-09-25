<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // EquipmentController reads/writes equipment.status, but the create migration never added it
    public function up(): void
    {
        if (! Schema::hasColumn('equipment', 'status')) {
            Schema::table('equipment', function (Blueprint $table) {
                $table->string('status')->default('Available');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('equipment', 'status')) {
            Schema::table('equipment', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
