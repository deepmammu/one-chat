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
        Schema::create('daily_timeSheet', function (Blueprint $table) {
            $table->bigIncrements('Time_sheet_id');
            $table->date('Date');
            $table->unsignedBigInteger('Employee_id')->nullable()->index();
            $table->unsignedBigInteger('Project_Id')->nullable()->index();
            $table->integer('Billing_hours');
            $table->string('Comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_timeSheet');
    }
};
