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
        Schema::table('score_rule_options', function (Blueprint $table) {
            if (!Schema::hasColumn('score_rule_options', 'option_label')) {
                $table->string('option_label', 255)->default('')->comment('Nhãn hiển thị của option');
            }
        });
    }

    public function down(): void
    {
        Schema::table('score_rule_options', function (Blueprint $table) {
            $cols = array_filter(['option_label'], fn($c) => Schema::hasColumn('score_rule_options', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};