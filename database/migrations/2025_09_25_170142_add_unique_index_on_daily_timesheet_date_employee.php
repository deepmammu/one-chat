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
        Schema::table('daily_timeSheet', function (Blueprint $table) {
            // Add a unique index to ensure only one timesheet per user per date
            if (! $this->indexExists('daily_timeSheet', 'daily_timeSheet_date_employee_unique')) {
                $table->unique(['Date', 'Employee_id'], 'daily_timeSheet_date_employee_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_timeSheet', function (Blueprint $table) {
            if ($this->indexExists('daily_timeSheet', 'daily_timeSheet_date_employee_unique')) {
                $table->dropUnique('daily_timeSheet_date_employee_unique');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        // Portable way without DB::select raw - rely on Schema builder where possible
        // Some drivers don't support listing indexes via Schema, so attempt and fallback
        try {
            $connection = Schema::getConnection();
            $schema = $connection->getDoctrineSchemaManager();
            $indexes = $schema->listTableIndexes($connection->getTablePrefix() . $table);
            return array_key_exists($indexName, $indexes);
        } catch (\Throwable $e) {
            return false;
        }
    }
};
