<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->unsignedBigInteger('manager_id')->nullable()->after('depth');
            $table->foreign('manager_id', 'fk_branches_manager')
                  ->references('id')->on('employees')
                  ->nullOnDelete();
            $table->index('manager_id', 'idx_branches_manager');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign('fk_branches_manager');
            $table->dropIndex('idx_branches_manager');
            $table->dropColumn('manager_id');
        });
    }
};
