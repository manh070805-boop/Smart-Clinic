<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('symptoms', function (Blueprint $table) {
            $table->id('symptom_id');
            $table->string('symptom_code', 50)->unique();
            $table->string('symptom_name', 150);
            $table->unsignedBigInteger('specialty_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('specialty_id')->references('specialty_id')->on('specialties')->onDelete('set null');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('symptoms');
    }
};
