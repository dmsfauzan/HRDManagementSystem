<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('employee_id', 30)->unique();
            $table->string('full_name', 150);
            $table->string('email', 150)->nullable()->unique();
            $table->string('phone', 30)->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('work_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->date('join_date');
            $table->string('employment_type', 30)->default('permanent');
            $table->string('employment_status', 30)->default('active');
            $table->date('birth_date')->nullable();
            $table->string('gender', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('department_id');
            $table->index('employment_status');
            $table->index('join_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
