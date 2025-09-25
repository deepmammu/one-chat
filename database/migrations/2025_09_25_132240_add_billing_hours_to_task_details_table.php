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
        Schema::table('task_details', function (Blueprint $table) {
            if (!Schema::hasColumn('task_details', 'Billing_hours')) {
                $table->unsignedTinyInteger('Billing_hours')->default(0)->after('Task_mode');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_details', function (Blueprint $table) {
            if (Schema::hasColumn('task_details', 'Billing_hours')) {
                $table->dropColumn('Billing_hours');
            }
        });
    }
};
