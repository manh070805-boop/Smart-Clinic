<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('id');
            $table->string('cccd', 20)->nullable()->unique()->after('password');
            $table->string('phone', 20)->nullable()->after('cccd');
            $table->date('dob')->nullable()->after('phone');

            $table->foreign('role_id')->references('role_id')->on('roles')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn(['role_id', 'cccd', 'phone', 'dob']);
        });
    }
};
