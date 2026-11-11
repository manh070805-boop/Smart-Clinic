<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id('appointment_id');
            $table->string('appointment_code', 50)->unique();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('schedule_id');
            $table->date('appointment_date');
            $table->time('start_time');
            $table->integer('queue_number')->nullable();
            $table->enum('status', ['pending', 'checked_in', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->text('symptom_description')->nullable();
            $table->text('ai_extracted_data')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->boolean('is_affected_by_leave')->default(false);
            $table->timestamp('rescheduled_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('doctor_id')->references('doctor_id')->on('doctors')->onDelete('set null');
            $table->foreign('schedule_id')->references('schedule_id')->on('schedules')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
