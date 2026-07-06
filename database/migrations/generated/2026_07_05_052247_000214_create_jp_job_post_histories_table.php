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
        if (Schema::hasTable('jp_job_post_histories')) {
            return;
        }

        Schema::create('jp_job_post_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('job_post_id')->constrained('jp_job_posts')->cascadeOnDelete();
            $table->string('change_type', 20)->comment('created|updated|status_changed|published|closed|archived');
            $table->string('old_status', 20)->nullable();
            $table->string('new_status', 20)->nullable();
            $table->text('changed_fields')->nullable()->comment('Danh sách trường thay đổi, phân tách phẩy');
            $table->text('note')->nullable();
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->nullable();
            

            // Indexes
            $table->index(['job_post_id', 'created_at'], 'idx_jp_hist_post');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('jp_job_post_histories');
    }
};