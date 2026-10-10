<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->timestamp('check_in_at')->nullable();
            $table->timestamp('check_out_at')->nullable();
            $table->string('check_in_ip', 45)->nullable();
            $table->string('check_out_ip', 45)->nullable();
            $table->string('status', 20)->default('present');
            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->unsignedInteger('work_minutes')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'work_date']);
            $table->index('work_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
