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
        if (Schema::hasTable('member_post_exit_audits')) {
            return;
        }

        Schema::create('member_post_exit_audits', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('org_membership_id')->constrained('organization_members')->cascadeOnDelete();
            $table->timestamp('effective_left_at')->useCurrent();
            $table->timestamp('offboarded_at')->useCurrent();
            $table->unsignedSmallInteger('gap_days');
            $table->unsignedSmallInteger('login_count_in_gap')->default(0);
            $table->unsignedSmallInteger('sandbox_sessions_in_gap')->default(0);
            $table->timestamp('last_login_in_gap')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable()->comment('FK users.id — HR đã review');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamp('created_at')->nullable();
            

            // Indexes
            $table->index(['organization_id', 'user_id'], 'mpea_org_user_index');
            $table->index('gap_days', 'mpea_gap_days_index');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('member_post_exit_audits');
    }
};