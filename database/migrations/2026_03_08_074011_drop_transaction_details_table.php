<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('transaction_details');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kept empty to avoid restoring deprecated schema.
    }
};
