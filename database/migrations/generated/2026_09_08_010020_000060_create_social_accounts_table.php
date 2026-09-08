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
        if (Schema::hasTable('social_accounts')) {
            return;
        }

        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 20);
            $table->string('provider_user_id', 255);
            $table->string('provider_email', 255)->nullable();
            $table->string('provider_name', 255)->nullable();
            $table->string('provider_avatar', 500)->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('linked_at')->useCurrent();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            

            // Indexes
            $table->unique(['provider', 'provider_user_id'], 'uq_provider_user');
            $table->index('user_id', 'idx_sa_user_id');
            $table->index('provider', 'idx_sa_provider');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};