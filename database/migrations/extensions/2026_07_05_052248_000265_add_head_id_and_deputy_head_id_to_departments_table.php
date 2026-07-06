<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (!Schema::hasColumn('departments', 'head_id')) {
                $table->unsignedBigInteger('head_id')->nullable();
            }
            if (!Schema::hasColumn('departments', 'deputy_head_id')) {
                $table->unsignedBigInteger('deputy_head_id')->nullable()->after('head_id');
            }
            if (!Schema::hasIndex('departments', 'idx_depts_head')) {
                $table->index('head_id', 'idx_depts_head');
            }
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $cols = array_filter(['head_id', 'deputy_head_id'], fn($c) => Schema::hasColumn('departments', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};