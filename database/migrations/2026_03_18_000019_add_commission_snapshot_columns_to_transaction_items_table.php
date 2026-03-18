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
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->enum('commission_source', ['default', 'override'])->nullable()->after('subtotal');
            $table->enum('commission_type', ['percent', 'fixed'])->nullable()->after('commission_source');
            $table->decimal('commission_value', 12, 2)->nullable()->after('commission_type');
        });

        DB::table('transaction_items')
            ->where('item_type', 'service')
            ->update([
                'commission_source' => 'default',
                'commission_type' => 'percent',
                'commission_value' => '50.00',
            ]);

        DB::table('transaction_items')
            ->where('item_type', 'product')
            ->update([
                'commission_source' => 'default',
                'commission_type' => 'fixed',
                'commission_value' => '5000.00',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropColumn([
                'commission_source',
                'commission_type',
                'commission_value',
            ]);
        });
    }
};
