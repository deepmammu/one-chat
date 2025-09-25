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
        Schema::table('task_details', function (Blueprint $table) {
            // Add new column if missing
            if (!Schema::hasColumn('task_details', 'employee_id')) {
                $table->unsignedBigInteger('employee_id')->nullable()->index()->after('Task_id');
            }
        });

        // Backfill from old 'Employee' column if it exists
        if (Schema::hasColumn('task_details', 'Employee')) {
            DB::statement('UPDATE task_details SET employee_id = Employee WHERE employee_id IS NULL');
        }

        Schema::table('task_details', function (Blueprint $table) {
            if (Schema::hasColumn('task_details', 'Employee')) {
                // Drop index if present, then drop column
                try { $table->dropIndex(['Employee']); } catch (\Throwable $e) { /* ignore */ }
                $table->dropColumn('Employee');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_details', function (Blueprint $table) {
            if (!Schema::hasColumn('task_details', 'Employee')) {
                $table->unsignedBigInteger('Employee')->nullable()->index()->after('Task_id');
            }
        });

        // Restore data
        if (Schema::hasColumn('task_details', 'employee_id')) {
            DB::statement('UPDATE task_details SET Employee = employee_id WHERE Employee IS NULL');
        }

        Schema::table('task_details', function (Blueprint $table) {
            if (Schema::hasColumn('task_details', 'employee_id')) {
                try { $table->dropIndex(['employee_id']); } catch (\Throwable $e) { /* ignore */ }
                $table->dropColumn('employee_id');
            }
        });
    }
};
