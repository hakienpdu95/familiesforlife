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
        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $cols = array_filter(['organization_id'], fn($c) => Schema::hasColumn('notifications', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};