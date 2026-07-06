<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jp_benefit_masters', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique()->comment('Public UUID');
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete()->comment('NULL = benefit toàn cục');
            $table->string('name', 150);
            $table->string('icon', 80)->nullable()->comment('Tabler icon: ti-heart');
            $table->string('category', 20)->default('other')->comment('health|finance|learning|work_life|equipment|other');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['organization_id', 'category', 'is_active'], 'idx_jp_benefit_cat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jp_benefit_masters');
    }
};
