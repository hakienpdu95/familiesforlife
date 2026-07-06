<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blueprint_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blueprint_version_id')->constrained('blueprint_versions')->cascadeOnDelete();
            $table->foreignId('capability_id')->nullable()->constrained('blueprint_capabilities')->nullOnDelete();
            $table->string('code', 50);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blueprint_workflows');
    }
};
