<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('item_type', ['service', 'product']);
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('item_name');
            $table->decimal('item_price', 12, 2);
            $table->unsignedInteger('qty');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('commission', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['item_type', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_details');
    }
};
