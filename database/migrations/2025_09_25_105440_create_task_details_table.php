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
        Schema::create('task_details', function (Blueprint $table) {
            $table->bigIncrements('Task_id');
            $table->unsignedBigInteger('Employee')->index();
            $table->unsignedBigInteger('Project_Id')->index();
            $table->unsignedBigInteger('Time_sheet_id')->nullable()->index();
            $table->string('Task_name');
            $table->text('Task_description')->nullable();
            $table->string('Task_mode')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_details');
    }
};
