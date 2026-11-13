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
        Schema::table('heritage_sites', function (Blueprint $table) {
            if (!Schema::hasColumn('heritage_sites', 'content')) {
                $table->longText('content')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('heritage_sites', function (Blueprint $table) {
            $cols = array_filter(['content'], fn($c) => Schema::hasColumn('heritage_sites', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};