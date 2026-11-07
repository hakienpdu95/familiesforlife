<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qr_identity_id')->constrained('qr_identities')->cascadeOnDelete();
            $table->string('reason', 30)->comment('wrong_info|suspected_fake|expired|other');
            $table->text('content');
            $table->string('contact', 120)->nullable()->comment('SĐT/email người phản ánh (tuỳ chọn)');
            $table->char('visitor_hash', 64);
            $table->string('status', 20)->default('new')->index()->comment('new|resolved');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_reports');
    }
};
