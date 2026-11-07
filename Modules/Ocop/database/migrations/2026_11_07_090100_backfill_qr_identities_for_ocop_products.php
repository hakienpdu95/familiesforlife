<?php

use App\Shared\Traceability\Models\QrIdentity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Modules\Ocop\Models\OcopProduct;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('qr_identities')) {
            return;
        }

        OcopProduct::withTrashed()
            ->with('media')
            ->whereDoesntHave('qrIdentities')
            ->chunkById(200, function ($products): void {
                foreach ($products as $product) {
                    QrIdentity::issueFor($product);
                }
            });
    }

    public function down(): void {}
};
