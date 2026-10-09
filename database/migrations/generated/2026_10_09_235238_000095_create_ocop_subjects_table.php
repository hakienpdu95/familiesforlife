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
        if (Schema::hasTable('ocop_subjects')) {
            return;
        }

        Schema::create('ocop_subjects', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('name', 255);
            $table->string('name_en', 255)->nullable();
            $table->string('tax_code', 20)->nullable()->index();
            $table->string('organization_type', 20)->nullable();
            $table->string('legal_representative', 150)->nullable();
            $table->string('position', 100)->nullable();
            $table->string('address', 255)->nullable();
            $table->char('province_code', 2);
            $table->string('province_name', 255)->nullable();
            $table->char('ward_code', 5);
            $table->string('ward_name', 255)->nullable();
            $table->string('gps_coordinates', 60)->nullable();
            $table->string('factory_code', 50)->nullable();
            $table->boolean('is_food_business')->default(false);
            $table->unsignedTinyInteger('ocop_star')->nullable();
            $table->date('ocop_cert_expiry')->nullable();
            $table->string('hotline', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('website', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['province_code', 'organization_type'], 'idx_ocop_subject_province_type');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('ocop_subjects');
    }
};