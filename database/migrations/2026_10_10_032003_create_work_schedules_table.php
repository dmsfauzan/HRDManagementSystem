<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->time('check_in_time');
            $table->time('check_out_time');
            $table->unsignedSmallInteger('late_tolerance_minutes')->default(15);
            $table->json('work_days');
            $table->boolean('crosses_midnight')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_schedules');
    }
};
