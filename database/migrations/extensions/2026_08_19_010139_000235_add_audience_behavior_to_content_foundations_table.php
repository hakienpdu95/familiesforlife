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
        Schema::table('content_foundations', function (Blueprint $table) {
            if (!Schema::hasColumn('content_foundations', 'audience_behavior')) {
                $table->text('audience_behavior')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('content_foundations', function (Blueprint $table) {
            $cols = array_filter(['audience_behavior'], fn($c) => Schema::hasColumn('content_foundations', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};