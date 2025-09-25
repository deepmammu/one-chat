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
        Schema::create('projects', function (Blueprint $table) {
            $table->bigIncrements('Project_Id');
            $table->unsignedBigInteger('Employee_id')->index();
            $table->string('Project_name');
            $table->text('Project_description')->nullable();
            $table->date('Project_start_date')->nullable();
            $table->date('Project_end_date')->nullable();
            $table->integer('Billing_days')->default(0);
            $table->integer('Project_sow')->nullable();
            $table->enum('Project_status', ['active','inactive'])->default('active');
            $table->string('Role')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
