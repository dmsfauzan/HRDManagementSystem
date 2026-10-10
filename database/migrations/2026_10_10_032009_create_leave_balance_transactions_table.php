<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_balance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_balance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->decimal('amount', 6, 2);
            $table->decimal('balance_after', 6, 2);
            $table->string('description', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balance_transactions');
    }
};
