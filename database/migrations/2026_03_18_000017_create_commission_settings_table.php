<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('commission_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('default_service_commission_type', ['percent', 'fixed'])
                ->default('percent');
            $table->decimal('default_service_commission_value', 12, 2)
                ->default('50.00');
            $table->enum('default_product_commission_type', ['percent', 'fixed'])
                ->default('fixed');
            $table->decimal('default_product_commission_value', 12, 2)
                ->default('5000.00');
            $table->timestamps();
        });

        DB::table('commission_settings')->insert([
            'id' => 1,
            'default_service_commission_type' => 'percent',
            'default_service_commission_value' => '50.00',
            'default_product_commission_type' => 'fixed',
            'default_product_commission_value' => '5000.00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_settings');
    }
};
