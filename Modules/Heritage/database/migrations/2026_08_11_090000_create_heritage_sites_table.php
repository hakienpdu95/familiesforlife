<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** spec/Heritage_Technical_Specification.md §3.6 — ERD heritage_sites. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heritage_sites', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('name', 200);
            $table->string('slug', 220)->unique();
            $table->string('heritage_type', 30);              // HeritageType
            $table->string('rank', 20)->default('unranked');  // HeritageRank
            $table->string('era', 100)->nullable();            // Niên đại — text tự do (VD "Thế kỷ 11"), không ép định dạng năm
            $table->text('description')->nullable();
            $table->char('province_code', 2)->nullable();
            $table->string('province_name', 255)->nullable();
            $table->char('ward_code', 5)->nullable();
            $table->string('ward_name', 255)->nullable();
            $table->string('address', 255)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('visiting_status', 20)->default('unknown'); // HeritageVisitingStatus
            $table->string('status', 20)->default('draft');            // HeritageSiteStatus: draft|published
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'province_code'], 'idx_heritage_status_province');
            $table->index(['status', 'heritage_type'], 'idx_heritage_status_type');
            $table->index(['status', 'is_featured'], 'idx_heritage_status_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heritage_sites');
    }
};
