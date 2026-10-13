<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained()->cascadeOnDelete();
            $table->string('candidate_name', 150);
            $table->string('candidate_email', 150);
            $table->string('candidate_phone', 30)->nullable();
            $table->string('domicile', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('education', 100)->nullable();
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->string('source', 50)->nullable();
            $table->text('cover_letter')->nullable();
            $table->string('cv_path', 255);
            $table->string('photo_path', 255)->nullable();
            $table->string('status', 20)->default('applied');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('hired_at')->nullable();
            $table->timestamps();
            $table->index(['job_posting_id', 'status']);
            $table->index('candidate_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
