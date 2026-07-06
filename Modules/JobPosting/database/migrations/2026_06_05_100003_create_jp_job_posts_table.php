<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jp_job_posts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique()->comment('Public UUID — expose ra ngoài');

            // ── Tenant & References ──────────────────────────────────────────
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('job_title_id')->nullable()->constrained('job_titles')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            // ── Thông tin cơ bản ─────────────────────────────────────────────
            $table->string('title', 200);
            $table->string('code', 30);
            $table->string('slug', 250);
            $table->string('status', 20)->default('draft')->index();
            $table->string('visibility', 10)->default('public');

            // ── Phân loại ────────────────────────────────────────────────────
            $table->string('employment_type', 20)->default('full_time')->index();
            $table->string('work_arrangement', 10)->default('onsite')->index();
            $table->string('experience_level', 15)->default('junior')->index();
            $table->string('industry', 20)->default('other')->index();
            $table->smallInteger('headcount')->default(1);
            $table->smallInteger('hired_count')->default(0);

            // ── Địa điểm ────────────────────────────────────────────────────
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->char('country', 2)->default('VN');
            $table->string('address_detail', 300)->nullable();
            $table->boolean('is_remote_allowed')->default(false);
            $table->text('remote_countries')->nullable()->comment('Danh sách ISO codes phân tách phẩy');

            // ── Nội dung tin ─────────────────────────────────────────────────
            $table->string('summary', 500)->nullable();
            $table->text('description');
            $table->text('responsibilities')->nullable();
            $table->text('requirements');
            $table->text('nice_to_have')->nullable();
            $table->text('what_you_will_learn')->nullable();
            $table->text('about_company')->nullable();

            // ── Học vấn & Kinh nghiệm ───────────────────────────────────────
            $table->smallInteger('min_experience_years')->nullable();
            $table->smallInteger('max_experience_years')->nullable();
            $table->string('education_level', 15)->nullable();
            $table->string('education_field', 200)->nullable();
            $table->text('certifications_required')->nullable();

            // ── Lương & Phúc lợi ────────────────────────────────────────────
            $table->string('salary_type', 10)->default('monthly');
            $table->decimal('salary_min', 15, 2)->nullable();
            $table->decimal('salary_max', 15, 2)->nullable();
            $table->char('salary_currency', 3)->default('VND');
            $table->boolean('salary_is_negotiable')->default(false);
            $table->boolean('salary_is_visible')->default(true);
            $table->string('salary_note', 300)->nullable();
            $table->smallInteger('probation_duration_days')->nullable();
            $table->smallInteger('probation_salary_pct')->nullable();

            // ── Thời hạn ────────────────────────────────────────────────────
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('expire_at')->nullable()->index();
            $table->timestamp('closed_at')->nullable();

            // ── Cấu hình ứng tuyển ───────────────────────────────────────────
            $table->string('application_email', 150)->nullable();
            $table->text('application_url')->nullable();
            $table->boolean('allow_direct_apply')->default(true);
            $table->boolean('require_cover_letter')->default(false);
            $table->boolean('require_portfolio')->default(false);

            // ── Phân phối kênh ───────────────────────────────────────────────
            $table->boolean('publish_to_marketplace')->default(false);
            $table->boolean('publish_to_career_page')->default(true);
            $table->char('mkt_listing_id', 36)->nullable()->index()->comment('Soft ref → mkt_listings.uuid');
            $table->string('mkt_sync_status', 15)->nullable()->comment('synced|out_of_sync');

            // ── Analytics (denormalized) ─────────────────────────────────────
            $table->integer('view_count')->default(0);
            $table->integer('application_count')->default(0);
            $table->integer('share_count')->default(0);

            // ── Metadata ─────────────────────────────────────────────────────
            $table->string('tags', 500)->nullable();
            $table->string('seo_title', 200)->nullable();
            $table->string('seo_description', 300)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ── Indexes ───────────────────────────────────────────────────────
            $table->unique(['organization_id', 'code'], 'idx_jp_post_code');
            $table->unique(['organization_id', 'slug'], 'idx_jp_post_slug');
            $table->index(['organization_id', 'status', 'published_at'], 'idx_jp_post_status');
            $table->index(['department_id', 'status'], 'idx_jp_post_dept');
            $table->index(['owner_id', 'status'], 'idx_jp_post_owner');
            $table->index(['expire_at', 'status'], 'idx_jp_post_expire');
            $table->index(['employment_type', 'work_arrangement', 'experience_level', 'status'], 'idx_jp_post_type');
            $table->index(['country', 'province', 'city', 'status'], 'idx_jp_post_location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jp_job_posts');
    }
};
