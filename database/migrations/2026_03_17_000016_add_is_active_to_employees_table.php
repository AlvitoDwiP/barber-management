<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees') || Schema::hasColumn('employees', 'is_active')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('employment_type');
            $table->index(['is_active', 'name'], 'employees_is_active_name_idx');
        });

        DB::table('employees')->update(['is_active' => true]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('employees') || ! Schema::hasColumn('employees', 'is_active')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table): void {
            $table->dropIndex('employees_is_active_name_idx');
            $table->dropColumn('is_active');
        });
    }
};
