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
        Schema::table('content_outlines', function (Blueprint $table) {
            if (!Schema::hasColumn('content_outlines', 'outline_depth')) {
                $table->string('outline_depth', 10)->default('standard');
            }
            if (!Schema::hasColumn('content_outlines', 'content_role')) {
                $table->string('content_role', 10)->nullable()->after('outline_depth');
            }
        });
    }

    public function down(): void
    {
        Schema::table('content_outlines', function (Blueprint $table) {
            $cols = array_filter(['outline_depth', 'content_role'], fn($c) => Schema::hasColumn('content_outlines', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};