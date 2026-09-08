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
        if (Schema::hasTable('push_subscriptions')) {
            return;
        }

        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('endpoint', 500);
            $table->string('public_key', 500)->nullable();
            $table->string('auth_token', 255)->nullable();
            $table->string('content_encoding', 20)->nullable()->default('aesgcm');
            $table->timestamps();
            

            // Indexes
            $table->unique('endpoint', 'push_sub_endpoint_unique');
            $table->index('user_id', 'push_sub_user_idx');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};