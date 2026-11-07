<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qr_identities', function (Blueprint $table) {
            $table->timestamp('snapshot_at')->nullable()->after('snapshot')->comment('Thời điểm chụp snapshot gần nhất (bản công khai cuối cùng)');
        });
    }

    public function down(): void
    {
        Schema::table('qr_identities', function (Blueprint $table) {
            $table->dropColumn('snapshot_at');
        });
    }
};
