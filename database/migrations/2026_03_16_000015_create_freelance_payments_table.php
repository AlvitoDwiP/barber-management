<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freelance_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->date('work_date');
            $table->decimal('total_service_amount', 12, 2)->default(0);
            $table->decimal('service_commission', 12, 2)->default(0);
            $table->unsignedInteger('total_product_qty')->default(0);
            $table->decimal('product_commission', 12, 2)->default(0);
            $table->decimal('total_commission', 12, 2)->default(0);
            $table->foreignId('expense_id')->nullable()->unique()->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->timestamps();

            $table->unique(['employee_id', 'work_date']);
            $table->index(['payment_status', 'work_date'], 'freelance_payments_status_work_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freelance_payments');
    }
};
