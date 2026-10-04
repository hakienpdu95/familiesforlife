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
        if (Schema::hasTable('organization_feature_overrides')) {
            return;
        }

        Schema::create('organization_feature_overrides', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('feature_slug', 128);
            $table->string('value', 255);
            $table->string('override_reason', 255)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            

            // Indexes
            $table->unique(['organization_id', 'feature_slug'], 'uq_org_feature');
            $table->index(['organization_id', 'expires_at'], 'idx_override_active');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_feature_overrides');
    }
};