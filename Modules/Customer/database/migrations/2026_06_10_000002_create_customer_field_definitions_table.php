<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_field_definitions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->string('field_key', 100);
            $table->string('label', 255);
            $table->tinyInteger('value_type')->default(1); // String=1,Int=2,Decimal=3,Boolean=4,Date=5
            $table->boolean('is_required')->default(false);
            $table->string('default_value', 500)->nullable();
            $table->string('placeholder', 255)->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->tinyInteger('applies_to')->default(0); // 0=Both,1=Individual,2=Business
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'field_key'], 'uq_cfd_org_key');
            $table->index(['organization_id', 'applies_to', 'is_active', 'sort_order'], 'idx_cfd_org');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_field_definitions');
    }
};
