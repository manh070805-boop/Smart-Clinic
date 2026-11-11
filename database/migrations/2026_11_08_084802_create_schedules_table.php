<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id('schedule_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->date('work_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('max_patients')->default(1);
            $table->enum('status', ['available', 'full', 'cancelled'])->default('available');
            $table->boolean('is_day_off')->default(false);
            $table->timestamps();

            $table->foreign('doctor_id')->references('doctor_id')->on('doctors')->onDelete('cascade');
            $table->foreign('clinic_id')->references('clinic_id')->on('clinics')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
