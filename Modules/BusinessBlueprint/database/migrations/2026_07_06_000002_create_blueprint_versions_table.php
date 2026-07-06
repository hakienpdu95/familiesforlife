<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blueprint_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blueprint_id')->constrained('blueprints')->cascadeOnDelete();
            $table->string('version', 30); // semantic version "1.0.0"
            $table->string('status', 20)->default('draft'); // BlueprintVersionStatus (9 trạng thái)
            $table->text('release_note')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('published_by')->nullable();
            $table->foreignId('parent_version_id')->nullable()->constrained('blueprint_versions')->nullOnDelete(); // lineage Clone
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['blueprint_id', 'version']);
            $table->index(['blueprint_id', 'status']);
        });

        // ALTER blueprints — thêm FK current_version_id (sau khi blueprint_versions tồn tại)
        Schema::table('blueprints', function (Blueprint $table) {
            $table->foreign('current_version_id')->references('id')->on('blueprint_versions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('blueprints', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });

        Schema::dropIfExists('blueprint_versions');
    }
};
