<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Extends the stub table created by JobPosting module with full Marketplace fields.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mkt_listings', function (Blueprint $table) {
            // Modify existing columns
            $table->unsignedBigInteger('org_id')->nullable()->change();  // allow NULL for individual poster
            $table->string('title', 300)->change();                       // expand from 200

            // New columns (not in JP stub)
            $table->unsignedBigInteger('posted_by')->default(0)->after('org_id');
            $table->string('slug', 320)->nullable()->unique()->after('title');
            $table->text('benefits')->nullable()->after('requirements');
            $table->string('visibility', 20)->default('public')->after('status');
            $table->boolean('salary_is_negotiable')->default(false)->after('salary_currency');
            $table->boolean('salary_is_visible')->default(true)->after('salary_is_negotiable');
            $table->decimal('budget_min', 15, 2)->nullable()->after('salary_is_visible');
            $table->decimal('budget_max', 15, 2)->nullable()->after('budget_min');
            $table->integer('duration_days')->nullable()->after('budget_max');
            $table->unsignedBigInteger('department_id')->nullable()->after('location');
            $table->unsignedBigInteger('position_id')->nullable()->after('department_id');
            $table->integer('bookmark_count')->default(0)->after('application_count');
            $table->string('jp_sync_status', 20)->nullable()->after('jp_job_post_id');
            $table->boolean('auto_close_on_jp')->default(true)->after('jp_sync_status');

            // FK constraints for new columns
            $table->foreign('posted_by')->references('id')->on('users');
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('position_id')->references('id')->on('job_titles')->nullOnDelete();
        });

        // Populate slug for existing rows (they'll be auto-generated via model otherwise)
        \DB::statement("UPDATE mkt_listings SET slug = CONCAT(LOWER(REPLACE(REPLACE(title, ' ', '-'), ',', '')), '-', id) WHERE slug IS NULL");
        \DB::statement("ALTER TABLE mkt_listings MODIFY COLUMN slug VARCHAR(320) NOT NULL");
        \DB::statement("CREATE UNIQUE INDEX idx_mkt_listing_slug ON mkt_listings(slug)");
    }

    public function down(): void
    {
        Schema::table('mkt_listings', function (Blueprint $table) {
            $table->dropForeign(['posted_by']);
            $table->dropForeign(['department_id']);
            $table->dropForeign(['position_id']);
            $table->dropColumn([
                'posted_by', 'slug', 'benefits', 'visibility',
                'salary_is_negotiable', 'salary_is_visible',
                'budget_min', 'budget_max', 'duration_days',
                'department_id', 'position_id', 'bookmark_count',
                'jp_sync_status', 'auto_close_on_jp',
            ]);
        });
    }
};
