<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ocop_products', function (Blueprint $table) {
            $table->foreignId('ocop_subject_id')->nullable()->after('category_id')
                ->constrained('ocop_subjects')->restrictOnDelete();
            $table->string('producer_name', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ocop_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ocop_subject_id');
            $table->string('producer_name', 150)->nullable()->change();
        });
    }
};
