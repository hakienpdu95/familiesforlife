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
        if (Schema::hasTable('n8n_connections')) {
            return;
        }

        Schema::create('n8n_connections', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('name')->unique();
            $table->string('purpose_note', 500)->nullable();
            $table->boolean('inbound_enabled')->default(false);
            $table->boolean('outbound_enabled')->default(false);
            $table->string('inbound_token', 64)->unique();
            $table->text('inbound_secret')->nullable();
            $table->text('outbound_webhook_url')->nullable();
            $table->text('outbound_secret')->nullable();
            $table->json('allowed_ip_cidrs')->nullable();
            $table->unsignedSmallInteger('rate_limit_per_minute')->nullable();
            $table->timestamp('last_inbound_at')->nullable();
            $table->timestamp('last_outbound_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('n8n_connections');
    }
};