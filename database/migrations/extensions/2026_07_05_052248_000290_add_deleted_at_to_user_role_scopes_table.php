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
        Schema::table('user_role_scopes', function (Blueprint $table) {
            if (!Schema::hasColumn('user_role_scopes', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->comment('Thời gian xóa mềm');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_role_scopes', function (Blueprint $table) {
            $cols = array_filter(['deleted_at'], fn($c) => Schema::hasColumn('user_role_scopes', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};