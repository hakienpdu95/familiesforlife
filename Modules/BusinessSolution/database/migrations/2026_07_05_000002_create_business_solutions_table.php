<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_solutions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('vertical_id')->constrained('verticals')->restrictOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete(); // NULL = solution dùng chung toàn platform
            $table->string('code', 50)->unique();       // AI-TXNG, AI-OCOP, AI-WORKFORCE
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->json('target_customers')->nullable();      // ["htx", "sme", ...]
            $table->string('status', 20)->default('draft');     // BusinessSolutionStatus: draft|published|archived
            $table->string('visibility', 20)->default('private'); // private|public|marketplace
            $table->string('thumbnail_url', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vertical_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_solutions');
    }
};
