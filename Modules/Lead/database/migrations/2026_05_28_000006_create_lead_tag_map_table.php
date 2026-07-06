<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lead_tag_map', function (Blueprint $table) {
            $table->unsignedBigInteger('lead_id');
            $table->unsignedSmallInteger('tag_id');

            $table->primary(['lead_id', 'tag_id']);
            $table->index(['tag_id', 'lead_id'], 'idx_tag_map_tag');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_tag_map');
    }
};
