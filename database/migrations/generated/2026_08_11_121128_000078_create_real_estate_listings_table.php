<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('real_estate_listings')) {
            return;
        }

        Schema::create('real_estate_listings', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('listing_type', 10);
            $table->string('property_type', 20);
            $table->string('title', 250);
            $table->string('slug', 270);
            $table->text('description')->nullable();
            $table->string('address_detail', 255)->nullable();
            $table->char('province_code', 2);
            $table->char('ward_code', 5);
            $table->decimal('area', 10, 2)->nullable();
            $table->unsignedSmallInteger('bedrooms')->nullable();
            $table->unsignedSmallInteger('bathrooms')->nullable();
            $table->unsignedSmallInteger('floors')->nullable();
            $table->string('interior_status', 20)->nullable();
            $table->boolean('is_price_negotiable')->default(false);
            $table->decimal('price', 15, 0)->nullable();
            $table->boolean('is_urgent')->default(false);
            $table->unsignedSmallInteger('urgent_days')->nullable();
            $table->decimal('monthly_rent', 15, 0)->nullable();
            $table->decimal('deposit', 15, 0)->nullable();
            $table->unsignedSmallInteger('rental_period_months')->nullable();
            $table->string('house_subtype', 20)->nullable();
            $table->string('apartment_subtype', 20)->nullable();
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('length', 8, 2)->nullable();
            $table->decimal('land_area', 10, 2)->nullable();
            $table->decimal('usable_area', 10, 2)->nullable();
            $table->decimal('net_area', 10, 2)->nullable();
            $table->string('legal_status', 20)->nullable();
            $table->string('direction', 15)->nullable();
            $table->string('balcony_direction', 15)->nullable();
            $table->string('project_name', 150)->nullable();
            $table->string('apartment_address', 150)->nullable();
            $table->string('usage_status', 20)->nullable();
            $table->decimal('front_road_width', 8, 2)->nullable();
            $table->decimal('current_rental_income', 15, 0)->nullable();
            $table->decimal('management_fee', 15, 0)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique(['organization_id', 'slug'], 'uq_real_estate_org_slug');
            $table->index(['listing_type', 'property_type', 'is_featured'], 'idx_real_estate_type_featured');
            $table->index(['province_code', 'ward_code'], 'idx_real_estate_location');
            $table->index(['organization_id', 'listing_type'], 'idx_real_estate_org_type');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('real_estate_listings');
    }
};
