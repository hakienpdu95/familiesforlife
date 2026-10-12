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
        Schema::table('assessment_results', function (Blueprint $table) {
            if (!Schema::hasColumn('assessment_results', 'public_token')) {
                $table->string('public_token', 64)->nullable()->unique()->comment('Token cho phép xem kết quả công khai không cần đăng nhập');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assessment_results', function (Blueprint $table) {
            $cols = array_filter(['public_token'], fn($c) => Schema::hasColumn('assessment_results', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};