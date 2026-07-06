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
        if (Schema::hasTable('mkt_reviews')) {
            return;
        }

        Schema::create('mkt_reviews', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('listing_id');
            $table->unsignedBigInteger('application_id');
            $table->string('reviewer_type', 20);
            $table->unsignedBigInteger('reviewer_id');
            $table->string('relation_type', 30)->default('hired');
            $table->smallInteger('overall_rating');
            $table->string('title', 200)->nullable();
            $table->text('content')->nullable();
            $table->smallInteger('rating_quality')->nullable();
            $table->smallInteger('rating_communication')->nullable();
            $table->smallInteger('rating_punctuality')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamp('created_at')->nullable();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('mkt_reviews');
    }
};