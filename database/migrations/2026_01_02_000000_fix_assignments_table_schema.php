<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. Make driver_id nullable
        // We use raw statement to avoid doctrine/dbal dependency
        DB::statement("ALTER TABLE assignments MODIFY driver_id BIGINT UNSIGNED NULL");

        // 2. Add vehicle_id if not exists
        Schema::table('assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('assignments', 'vehicle_id')) {
                $table->foreignId('vehicle_id')->nullable()->after('order_id')->constrained('vehicles')->nullOnDelete();
            }
        });
    }

    public function down()
    {
        // Revert driver_id to NOT NULL (careful if data has nulls)
        // DB::statement("ALTER TABLE assignments MODIFY driver_id BIGINT UNSIGNED NOT NULL");

        Schema::table('assignments', function (Blueprint $table) {
            if (Schema::hasColumn('assignments', 'vehicle_id')) {
                $table->dropForeign(['vehicle_id']);
                $table->dropColumn('vehicle_id');
            }
        });
    }
};
