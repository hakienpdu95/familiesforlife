<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->unsignedBigInteger('head_id')->nullable()->after('depth');
            $table->unsignedBigInteger('deputy_head_id')->nullable()->after('head_id');

            $table->foreign('head_id', 'fk_departments_head')
                  ->references('id')->on('employees')
                  ->nullOnDelete();
            $table->foreign('deputy_head_id', 'fk_departments_deputy')
                  ->references('id')->on('employees')
                  ->nullOnDelete();

            $table->index('head_id', 'idx_depts_head');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign('fk_departments_head');
            $table->dropForeign('fk_departments_deputy');
            $table->dropIndex('idx_depts_head');
            $table->dropColumn(['head_id', 'deputy_head_id']);
        });
    }
};
