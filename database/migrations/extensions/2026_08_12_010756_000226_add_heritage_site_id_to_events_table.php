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
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'heritage_site_id')) {
                $table->foreignId('heritage_site_id')->nullable()->constrained('heritage_sites')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'heritage_site_id')) $table->dropForeign(['heritage_site_id']);
            $cols = array_filter(['heritage_site_id'], fn($c) => Schema::hasColumn('events', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};