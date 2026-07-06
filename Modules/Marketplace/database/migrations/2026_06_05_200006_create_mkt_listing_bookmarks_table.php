<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mkt_listing_bookmarks', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->nullable()->unique();
            $table->unsignedBigInteger('listing_id');
            $table->foreign('listing_id')->references('id')->on('mkt_listings')->cascadeOnDelete();
            $table->unsignedBigInteger('applicant_id');
            $table->foreign('applicant_id')->references('id')->on('mkt_applicants')->cascadeOnDelete();
            $table->string('note', 300)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        DB::statement('CREATE UNIQUE INDEX idx_mkt_bookmark_unique ON mkt_listing_bookmarks(listing_id, applicant_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('mkt_listing_bookmarks');
    }
};
