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
        // Add Unbillable_hours to task_details
        Schema::table('task_details', function (Blueprint $table) {
            if (!Schema::hasColumn('task_details', 'Unbillable_hours')) {
                $table->unsignedTinyInteger('Unbillable_hours')->default(0)->after('Billing_hours');
            }
        });

        // Add Unbillable_hours to daily_timeSheet to keep a daily total separate from billable
        Schema::table('daily_timeSheet', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_timeSheet', 'Unbillable_hours')) {
                $table->unsignedSmallInteger('Unbillable_hours')->default(0)->after('Billing_hours');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_details', function (Blueprint $table) {
            if (Schema::hasColumn('task_details', 'Unbillable_hours')) {
                $table->dropColumn('Unbillable_hours');
            }
        });
        Schema::table('daily_timeSheet', function (Blueprint $table) {
            if (Schema::hasColumn('daily_timeSheet', 'Unbillable_hours')) {
                $table->dropColumn('Unbillable_hours');
            }
        });
    }
};
