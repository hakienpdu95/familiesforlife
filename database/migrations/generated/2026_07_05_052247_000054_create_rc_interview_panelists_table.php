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
        if (Schema::hasTable('rc_interview_panelists')) {
            return;
        }

        Schema::create('rc_interview_panelists', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('interview_id')->index();
            $table->unsignedBigInteger('user_id');
            $table->string('role', 20)->default('interviewer');
            $table->string('response_status', 20)->default('pending');
            $table->timestamp('responded_at')->nullable();
            

            // Indexes
            $table->unique(['interview_id', 'user_id'], 'idx_rc_panelist_unique');
            $table->index(['user_id', 'response_status'], 'idx_rc_panelist_user');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('rc_interview_panelists');
    }
};