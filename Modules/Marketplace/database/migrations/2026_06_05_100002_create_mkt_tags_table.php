<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mkt_tags', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('name', 80)->unique();
            $table->string('slug', 90)->unique();
            $table->string('listing_type', 20)->nullable()->index(); // NULL = dùng cho mọi loại
            $table->integer('use_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mkt_tags');
    }
};
