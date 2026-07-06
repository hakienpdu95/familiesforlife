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
        if (Schema::hasTable('mkt_listing_bookmarks')) {
            return;
        }

        Schema::create('mkt_listing_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('listing_id');
            $table->unsignedBigInteger('applicant_id');
            $table->string('note', 300)->nullable();
            $table->timestamp('created_at')->nullable();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('mkt_listing_bookmarks');
    }
};