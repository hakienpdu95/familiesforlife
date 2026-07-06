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
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'manager_id')) {
                $table->unsignedBigInteger('manager_id')->nullable();
            }
            if (!Schema::hasIndex('branches', 'idx_branches_manager')) {
                $table->index('manager_id', 'idx_branches_manager');
            }
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $cols = array_filter(['manager_id'], fn($c) => Schema::hasColumn('branches', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};