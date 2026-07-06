<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Rename legacy column if exists
            if (Schema::hasColumn('users', 'department')) {
                $table->renameColumn('department', 'department_legacy');
            }

            $table->unsignedBigInteger('branch_id')->nullable()->after('organization_id');
            $table->unsignedBigInteger('department_id')->nullable()->after('branch_id');

            $table->foreign('branch_id', 'fk_users_branch')
                  ->references('id')->on('branches')
                  ->nullOnDelete();
            $table->foreign('department_id', 'fk_users_department')
                  ->references('id')->on('departments')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('fk_users_branch');
            $table->dropForeign('fk_users_department');
            $table->dropColumn(['branch_id', 'department_id']);
        });
    }
};
