<?php

use App\Shared\Traceability\Models\QrIdentity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Modules\Product\Models\Product;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('qr_identities')) {
            return;
        }

        Product::withTrashed()
            ->whereDoesntHave('qrIdentities')
            ->chunkById(200, function ($products): void {
                foreach ($products as $product) {
                    QrIdentity::issueFor($product);
                }
            });
    }

    public function down(): void {}
};
