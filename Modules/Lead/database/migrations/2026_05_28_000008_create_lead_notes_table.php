<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lead_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->unsignedInteger('organization_id');
            $table->text('content');
            $table->boolean('is_pinned')->default(false);
            $table->unsignedBigInteger('author_id')->nullable();
            $table->string('author_name', 191)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['lead_id', 'is_pinned', 'created_at'], 'idx_note_lead');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_notes');
    }
};
