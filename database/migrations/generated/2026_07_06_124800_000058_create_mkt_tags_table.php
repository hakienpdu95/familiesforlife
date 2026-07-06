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
        if (Schema::hasTable('mkt_tags')) {
            return;
        }

        Schema::create('mkt_tags', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('name', 80)->unique();
            $table->string('slug', 90)->unique();
            $table->string('listing_type', 20)->nullable()->index();
            $table->integer('use_count')->default(0);
            $table->timestamps();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('mkt_tags');
    }
};