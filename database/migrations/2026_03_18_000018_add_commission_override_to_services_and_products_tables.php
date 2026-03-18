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
        Schema::table('services', function (Blueprint $table) {
            $table->enum('commission_type', ['percent'])->nullable()->after('price');
            $table->decimal('commission_value', 12, 2)->nullable()->after('commission_type');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->enum('commission_type', ['percent', 'fixed'])->nullable()->after('price');
            $table->decimal('commission_value', 12, 2)->nullable()->after('commission_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['commission_type', 'commission_value']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['commission_type', 'commission_value']);
        });
    }
};
