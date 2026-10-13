<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->string('work_mode', 20)->default('onsite')->after('location');
            $table->string('experience_level', 20)->default('junior')->after('work_mode');
            $table->text('responsibilities')->nullable()->after('description');
            $table->text('benefits')->nullable()->after('requirements');
            $table->date('closes_at')->nullable()->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropColumn(['work_mode', 'experience_level', 'responsibilities', 'benefits', 'closes_at']);
        });
    }
};
