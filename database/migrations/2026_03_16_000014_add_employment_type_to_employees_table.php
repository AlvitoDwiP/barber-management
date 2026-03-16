<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees') || Schema::hasColumn('employees', 'employment_type')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table): void {
            $table->string('employment_type', 20)->default('permanent')->after('status');
            $table->index(['employment_type', 'name'], 'employees_employment_type_name_idx');
        });

        DB::table('employees')->update([
            'employment_type' => DB::raw("CASE WHEN status = 'freelance' THEN 'freelance' ELSE 'permanent' END"),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('employees') || ! Schema::hasColumn('employees', 'employment_type')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table): void {
            $table->dropIndex('employees_employment_type_name_idx');
            $table->dropColumn('employment_type');
        });
    }
};
