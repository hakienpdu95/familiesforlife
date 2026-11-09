<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Product;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('media')
            ->whereIn('model_type', [Product::class, 'product'])
            ->update(['model_type' => (new Product)->getMorphClass()]);

        DB::table('media')
            ->where('model_type', (new Product)->getMorphClass())
            ->where('collection_name', 'cover')
            ->update(['collection_name' => Product::GALLERY]);
    }

    public function down(): void {}
};
