<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Task details: add Billable_hours, copy, drop old Billing_hours
        Schema::table('task_details', function (Blueprint $table) {
            if (!Schema::hasColumn('task_details', 'Billable_hours')) {
                $table->unsignedTinyInteger('Billable_hours')->default(0)->after('Task_mode');
            }
        });
        if (Schema::hasColumn('task_details', 'Billing_hours')) {
            DB::statement('UPDATE task_details SET Billable_hours = Billing_hours');
            Schema::table('task_details', function (Blueprint $table) {
                $table->dropColumn('Billing_hours');
            });
        }

        // Daily timesheet: add Billable_hours, copy, drop old Billing_hours
        Schema::table('daily_timeSheet', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_timeSheet', 'Billable_hours')) {
                $table->unsignedSmallInteger('Billable_hours')->default(0)->after('Date');
            }
        });
        if (Schema::hasColumn('daily_timeSheet', 'Billing_hours')) {
            DB::statement('UPDATE daily_timeSheet SET Billable_hours = Billing_hours');
            Schema::table('daily_timeSheet', function (Blueprint $table) {
                $table->dropColumn('Billing_hours');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse for task_details
        Schema::table('task_details', function (Blueprint $table) {
            if (!Schema::hasColumn('task_details', 'Billing_hours')) {
                $table->unsignedTinyInteger('Billing_hours')->default(0)->after('Task_mode');
            }
        });
        if (Schema::hasColumn('task_details', 'Billable_hours')) {
            DB::statement('UPDATE task_details SET Billing_hours = Billable_hours');
            Schema::table('task_details', function (Blueprint $table) {
                $table->dropColumn('Billable_hours');
            });
        }

        // Reverse for daily_timeSheet
        Schema::table('daily_timeSheet', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_timeSheet', 'Billing_hours')) {
                $table->unsignedSmallInteger('Billing_hours')->default(0)->after('Date');
            }
        });
        if (Schema::hasColumn('daily_timeSheet', 'Billable_hours')) {
            DB::statement('UPDATE daily_timeSheet SET Billing_hours = Billable_hours');
            Schema::table('daily_timeSheet', function (Blueprint $table) {
                $table->dropColumn('Billable_hours');
            });
        }
    }
};
