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
        if (Schema::hasTable('organization_members')) {
            return;
        }

        Schema::create('organization_members', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete()->comment('Tổ chức');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->comment('Thành viên');
            $table->enum('role', ['owner', 'admin', 'manager', 'member'])->default('member')->index()->comment('Vai trò trong tổ chức');
            $table->timestamp('joined_at')->nullable()->comment('Thời điểm gia nhập');
            $table->timestamps();
            

            // Indexes
            $table->unique(['organization_id', 'user_id']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_members');
    }
};