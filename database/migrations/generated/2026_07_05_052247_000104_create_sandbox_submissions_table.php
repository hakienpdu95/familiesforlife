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
        if (Schema::hasTable('sandbox_submissions')) {
            return;
        }

        Schema::create('sandbox_submissions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('sandbox_session_id')->unique();
            $table->text('submitted_content')->comment('Output thực tế của người dùng');
            $table->string('ai_tools_used', 300)->nullable()->comment('pipe-delimited');
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('sandbox_submissions');
    }
};