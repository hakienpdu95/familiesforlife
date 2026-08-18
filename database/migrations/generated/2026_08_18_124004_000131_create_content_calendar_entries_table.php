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
        if (Schema::hasTable('content_calendar_entries')) {
            return;
        }

        Schema::create('content_calendar_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('post_category_id')->constrained('post_categories')->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('brief')->nullable();
            $table->string('origin', 30)->default('manual');
            $table->text('origin_note')->nullable();
            $table->string('status', 20)->default('idea');
            $table->date('target_publish_date')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('post_article_id')->nullable()->constrained('post_articles')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['post_category_id', 'target_publish_date'], 'cc_entries_category_date_idx');
            $table->index('status', 'cc_entries_status_idx');
            $table->index(['assigned_to', 'status'], 'cc_entries_assignee_status_idx');
            $table->index(['created_by', 'status'], 'cc_entries_creator_status_idx');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('content_calendar_entries');
    }
};