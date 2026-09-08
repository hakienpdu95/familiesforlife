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
        if (Schema::hasTable('n8n_outbound_logs')) {
            return;
        }

        Schema::create('n8n_outbound_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('connection_id')->constrained('n8n_connections')->cascadeOnDelete();
            $table->string('event_name', 100)->nullable();
            $table->string('caller', 150)->nullable();
            $table->boolean('success');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('error_message', 500)->nullable();
            $table->text('payload_excerpt')->nullable();
            $table->timestamp('requested_at')->useCurrent();
            

            // Indexes
            $table->index(['connection_id', 'requested_at']);
            $table->index('requested_at');
            $table->index(['success', 'requested_at']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('n8n_outbound_logs');
    }
};