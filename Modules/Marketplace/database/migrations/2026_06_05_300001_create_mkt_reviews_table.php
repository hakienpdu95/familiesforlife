<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mkt_reviews', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID');
            $table->unsignedBigInteger('listing_id');
            $table->foreign('listing_id')->references('id')->on('mkt_listings')->cascadeOnDelete();
            $table->unsignedBigInteger('application_id');
            $table->foreign('application_id')->references('id')->on('mkt_applications')->cascadeOnDelete();
            $table->string('reviewer_type', 20);   // org | applicant — no FK, polymorphic
            $table->unsignedBigInteger('reviewer_id'); // users.id or mkt_applicants.id
            $table->string('relation_type', 30)->default('hired');
            $table->smallInteger('overall_rating');  // 1-5
            $table->string('title', 200)->nullable();
            $table->text('content')->nullable();
            $table->smallInteger('rating_quality')->nullable();
            $table->smallInteger('rating_communication')->nullable();
            $table->smallInteger('rating_punctuality')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });

        DB::statement('CREATE UNIQUE INDEX idx_mkt_review_unique ON mkt_reviews(application_id, reviewer_type, reviewer_id)');
        DB::statement('CREATE INDEX idx_mkt_review_listing ON mkt_reviews(listing_id, is_public)');
        DB::statement('CREATE INDEX idx_mkt_review_created ON mkt_reviews(created_at)');
    }

    public function down(): void
    {
        Schema::dropIfExists('mkt_reviews');
    }
};
