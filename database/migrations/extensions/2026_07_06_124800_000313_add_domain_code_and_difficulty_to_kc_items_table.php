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
        Schema::table('kc_items', function (Blueprint $table) {
            if (!Schema::hasColumn('kc_items', 'domain_code')) {
                $table->string('domain_code', 10)->nullable();
            }
            if (!Schema::hasColumn('kc_items', 'difficulty')) {
                $table->unsignedTinyInteger('difficulty')->nullable()->after('domain_code');
            }
            if (!Schema::hasIndex('kc_items', 'kc_items_domain_code_index')) {
                $table->index('domain_code');
            }
            if (!Schema::hasIndex('kc_items', 'kc_items_difficulty_index')) {
                $table->index('difficulty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kc_items', function (Blueprint $table) {
            $cols = array_filter(['domain_code', 'difficulty'], fn($c) => Schema::hasColumn('kc_items', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};