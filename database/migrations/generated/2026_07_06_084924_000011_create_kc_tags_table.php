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
        if (Schema::hasTable('kc_tags')) {
            return;
        }

        Schema::create('kc_tags', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('name', 80);
            $table->string('slug', 90);
            $table->char('color_hex', 7)->nullable();
            $table->timestamps();
            

            // Indexes
            $table->unique(['organization_id', 'slug'], 'uq_kc_tag_org_slug');
            $table->index('organization_id', 'idx_kc_tag_org');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('kc_tags');
    }
};