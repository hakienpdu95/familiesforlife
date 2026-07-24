<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/RealEstateForSale_Technical_Specification.md §3 — 1 bảng dùng chung cho cả bán/thuê
 * (listing_type phân biệt, tiền lệ ArticleFormat). Trạng thái duyệt qua ApprovalSubject
 * (Modules\Approval, polymorphic) — KHÔNG có cột status trên bảng này. Mọi field đặc thù theo
 * property_type là cột SQL thật (§0 v1.2) — KHÔNG dùng JSON.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('real_estate_listings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();

            $table->string('listing_type', 10);   // ListingType enum
            $table->string('property_type', 20);  // PropertyType enum

            $table->string('title', 250);
            $table->string('slug', 270);
            $table->text('description')->nullable();

            $table->string('address_detail', 255)->nullable(); // số nhà/đường — tự do, chỉ province/ward mới bắt buộc (§0 v1.1)
            $table->char('province_code', 2);  // BẮT BUỘC — §0 v1.1
            $table->char('ward_code', 5);      // BẮT BUỘC — §0 v1.1

            $table->decimal('area', 10, 2)->nullable();
            $table->unsignedSmallInteger('bedrooms')->nullable();
            $table->unsignedSmallInteger('bathrooms')->nullable();
            $table->unsignedSmallInteger('floors')->nullable();
            $table->string('interior_status', 20)->nullable();

            $table->boolean('is_price_negotiable')->default(false); // "Giá thoả thuận" — cả sale + rent, §0 v1.1

            // CHỈ sale
            $table->decimal('price', 15, 0)->nullable();
            $table->boolean('is_urgent')->default(false);
            $table->unsignedSmallInteger('urgent_days')->nullable();

            // CHỈ rent (spec song sinh RealEstateForRent §3)
            $table->decimal('monthly_rent', 15, 0)->nullable();
            $table->decimal('deposit', 15, 0)->nullable();
            $table->unsignedSmallInteger('rental_period_months')->nullable();

            // ── Field đặc thù theo property_type — CỘT SQL THẬT, KHÔNG dùng JSON (§0 v1.2) ──
            $table->string('house_subtype', 20)->nullable();      // CHỈ house + sale
            $table->string('apartment_subtype', 20)->nullable();  // CHỈ apartment + sale
            $table->decimal('width', 8, 2)->nullable();           // CHỈ house/land + sale
            $table->decimal('length', 8, 2)->nullable();          // CHỈ house/land + sale
            $table->decimal('land_area', 10, 2)->nullable();      // CHỈ house/land + sale — breakdown chi tiết của `area`
            $table->decimal('usable_area', 10, 2)->nullable();    // CHỈ apartment — breakdown chi tiết của `area`
            $table->decimal('net_area', 10, 2)->nullable();       // CHỈ apartment + sale — breakdown chi tiết của `area`
            $table->string('legal_status', 20)->nullable();       // house/apartment/land + sale, apartment + rent
            $table->string('direction', 15)->nullable();          // house/apartment/land (dùng chung 1 cột — §0)
            $table->string('balcony_direction', 15)->nullable();  // CHỈ apartment
            $table->string('project_name', 150)->nullable();      // CHỈ apartment
            $table->string('apartment_address', 150)->nullable(); // CHỈ apartment
            $table->string('usage_status', 20)->nullable();       // apartment + sale, apartment + rent
            $table->decimal('front_road_width', 8, 2)->nullable(); // CHỈ land + sale
            $table->decimal('current_rental_income', 15, 0)->nullable(); // CHỈ sale — khác monthly_rent (chỉ rent)
            $table->decimal('management_fee', 15, 0)->nullable();  // apartment + rent, khả dụng cho apartment + sale nếu cần

            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

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
