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
        Schema::table('qr_identities', function (Blueprint $table) {
            if (!Schema::hasColumn('qr_identities', 'snapshot_at')) {
                $table->timestamp('snapshot_at')->nullable()->comment('Thời điểm chụp snapshot gần nhất (bản công khai cuối cùng)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('qr_identities', function (Blueprint $table) {
            $cols = array_filter(['snapshot_at'], fn($c) => Schema::hasColumn('qr_identities', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};