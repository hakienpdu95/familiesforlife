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
        if (Schema::hasTable('customer_field_definitions')) {
            return;
        }

        Schema::create('customer_field_definitions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('organization_id');
            $table->string('field_key', 100);
            $table->string('label', 255);
            $table->tinyInteger('value_type')->default(1);
            $table->boolean('is_required')->default(false);
            $table->string('default_value', 500)->nullable();
            $table->string('placeholder', 255)->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->tinyInteger('applies_to')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            

            // Indexes
            $table->unique(['organization_id', 'field_key'], 'uq_cfd_org_key');
            $table->index(['organization_id', 'applies_to', 'is_active', 'sort_order'], 'idx_cfd_org');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_field_definitions');
    }
};