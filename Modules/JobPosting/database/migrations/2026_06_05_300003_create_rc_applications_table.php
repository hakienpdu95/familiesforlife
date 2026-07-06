<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Stub table — will be owned by Recruitment Center module when built.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rc_applications', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID');
            $table->char('jp_job_post_id', 36)->nullable()->index()->comment('Soft ref → jp_job_posts.uuid');
            $table->unsignedBigInteger('candidate_id')->nullable()->index()->comment('FK → rc_candidates.id');
            $table->unsignedBigInteger('org_id')->index();
            $table->string('apply_source', 50)->default('career_page')->comment('career_page|marketplace|referral|direct');
            $table->char('mkt_application_id', 36)->nullable()->comment('Soft ref → mkt_applications.uuid');
            $table->string('status', 30)->default('received')
                ->comment('received|reviewing|shortlisted|interview|offer|rejected|withdrawn');
            $table->text('cover_letter')->nullable();
            $table->json('answers')->nullable()->comment('Screening question answers keyed by question uuid');
            $table->boolean('disqualified')->default(false);
            $table->timestamps();

            $table->index(['jp_job_post_id', 'created_at']);
            $table->index(['org_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rc_applications');
    }
};
