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
        if (Schema::hasTable('n8n_inbound_logs')) {
            return;
        }

        Schema::create('n8n_inbound_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('connection_id')->nullable()->constrained('n8n_connections')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->boolean('signature_valid')->nullable();
            $table->unsignedSmallInteger('http_status_returned');
            $table->string('event_name', 100)->nullable();
            $table->unsignedTinyInteger('listener_count')->default(0);
            $table->text('payload_excerpt')->nullable();
            $table->string('error_message', 500)->nullable();
            $table->timestamp('received_at')->useCurrent();
            

            // Indexes
            $table->index(['connection_id', 'received_at']);
            $table->index('received_at');
            $table->index('event_name');
            $table->index('signature_valid');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('n8n_inbound_logs');
    }
};