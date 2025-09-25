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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'Employee_id')) {
                $table->unsignedBigInteger('Employee_id')->nullable()->index()->after('id');
            }
            if (!Schema::hasColumn('users', 'Status')) {
                $table->enum('Status', ['active', 'inactive'])->default('active')->after('password');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'Status')) {
                $table->dropColumn('Status');
            }
            if (Schema::hasColumn('users', 'Employee_id')) {
                $table->dropIndex(['Employee_id']);
                $table->dropColumn('Employee_id');
            }
        });
    }
};
