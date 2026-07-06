<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mkt_listing_tags', function (Blueprint $table) {
            $table->unsignedBigInteger('listing_id');
            $table->unsignedBigInteger('tag_id');

            $table->primary(['listing_id', 'tag_id']);
            $table->foreign('listing_id')->references('id')->on('mkt_listings')->cascadeOnDelete();
            $table->foreign('tag_id')->references('id')->on('mkt_tags');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mkt_listing_tags');
    }
};
