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
        if (! Schema::hasColumn('expenses', 'category')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->string('category')->default('lainnya')->after('expense_date');
            });
        }

        if (Schema::hasColumn('expenses', 'expense_category_id') && Schema::hasTable('expense_categories')) {
            $categoryNames = DB::table('expense_categories')->pluck('name', 'id');

            $expenses = DB::table('expenses')->select('id', 'expense_category_id')->get();

            foreach ($expenses as $expense) {
                $category = $categoryNames[$expense->expense_category_id] ?? 'lainnya';

                DB::table('expenses')
                    ->where('id', $expense->id)
                    ->update(['category' => $category]);
            }

            Schema::table('expenses', function (Blueprint $table) {
                $table->dropForeign(['expense_category_id']);
                $table->dropColumn('expense_category_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('expenses', 'expense_category_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->foreignId('expense_category_id')
                    ->after('expense_date')
                    ->constrained('expense_categories')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        }

        if (Schema::hasColumn('expenses', 'category')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }
};
