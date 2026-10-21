<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reimbursements', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->enum('status', [
                'DRAFT', 'SUBMITTED', 'REVISION', 'APPROVED',
                'VERIFIED', 'PAID', 'REJECTED', 'CANCELLED',
            ])->default('DRAFT');
            $table->enum('revision_from', ['approver', 'finance'])->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->enum('payment_method', ['transfer', 'cash'])->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('payment_note')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('user_id');
            $table->index('department_id');
            $table->index('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reimbursements');
    }
};
