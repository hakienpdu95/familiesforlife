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
        if (Schema::hasTable('identity_verifications')) {
            return;
        }

        Schema::create('identity_verifications', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('method', 30)->comment('email | vne_id | passport');
            $table->string('status', 20)->default('pending')->comment('pending | verified | rejected | expired');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('rejection_reason', 300)->nullable();
            $table->timestamps();
            

            // Indexes
            $table->index(['user_id', 'method'], 'iv_user_method_index');
            $table->index('status', 'iv_status_index');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_verifications');
    }
};