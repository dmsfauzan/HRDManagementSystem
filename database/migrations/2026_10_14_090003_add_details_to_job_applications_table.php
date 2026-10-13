<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->string('linkedin_url', 255)->nullable()->after('source');
            $table->string('portfolio_url', 255)->nullable()->after('linkedin_url');
            $table->decimal('expected_salary', 12, 2)->nullable()->after('portfolio_url');
            $table->string('status_token', 64)->nullable()->unique()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn(['linkedin_url', 'portfolio_url', 'expected_salary', 'status_token']);
        });
    }
};
