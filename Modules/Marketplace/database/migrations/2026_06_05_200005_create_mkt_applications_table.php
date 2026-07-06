<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mkt_applications', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->nullable()->unique();
            $table->unsignedBigInteger('listing_id');
            $table->foreign('listing_id')->references('id')->on('mkt_listings')->cascadeOnDelete();
            $table->unsignedBigInteger('applicant_id');
            $table->foreign('applicant_id')->references('id')->on('mkt_applicants')->cascadeOnDelete();
            $table->string('status', 20)->default('submitted');
            $table->text('cover_letter')->nullable();
            $table->decimal('expected_salary', 15, 2)->nullable();
            $table->date('available_from')->nullable();
            $table->string('portfolio_url', 300)->nullable();
            $table->string('import_status', 20)->default('not_imported');
            $table->char('imported_rc_candidate_id', 36)->nullable();
            $table->char('imported_rc_application_id', 36)->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->unsignedBigInteger('imported_by')->nullable();
            $table->foreign('imported_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        DB::statement('CREATE UNIQUE INDEX idx_mkt_app_unique ON mkt_applications(listing_id, applicant_id)');
        DB::statement('CREATE INDEX idx_mkt_app_listing ON mkt_applications(listing_id, status)');
        DB::statement('CREATE INDEX idx_mkt_app_applicant ON mkt_applications(applicant_id, status)');
        DB::statement('CREATE INDEX idx_mkt_app_import ON mkt_applications(import_status, listing_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('mkt_applications');
    }
};
