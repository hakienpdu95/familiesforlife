<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** spec/Province_Showcase_Technical_Specification.md §3.4 — ERD ocop_categories. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocop_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('name', 150);
            $table->string('slug', 180)->unique();
            $table->string('icon', 80)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocop_categories');
    }
};
