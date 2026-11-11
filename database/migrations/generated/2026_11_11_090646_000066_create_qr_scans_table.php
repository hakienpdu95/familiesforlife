<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('qr_scans')) {
            return;
        }

        Schema::create('qr_scans', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('qr_identity_id')->constrained('qr_identities')->cascadeOnDelete();
            $table->char('visitor_hash', 64)->comment('sha256(IP + app key) — không lưu IP thô');
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('scanned_at')->useCurrent();
            

            // Indexes
            $table->index(['qr_identity_id', 'scanned_at'], 'idx_qr_scans_identity_time');
            $table->index(['qr_identity_id', 'visitor_hash'], 'idx_qr_scans_identity_visitor');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_scans');
    }
};