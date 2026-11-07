<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('qr_scans');
    }

    public function down(): void
    {
        Schema::create('qr_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qr_identity_id')->constrained('qr_identities')->cascadeOnDelete();
            $table->char('visitor_hash', 64);
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('scanned_at')->useCurrent();
            $table->index(['qr_identity_id', 'scanned_at'], 'idx_qr_scans_identity_time');
        });
    }
};
