<?php

use App\Shared\Traceability\Models\QrIdentity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Modules\Heritage\Models\HeritageSite;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('qr_identities')) {
            return;
        }

        HeritageSite::withTrashed()
            ->with('media')
            ->whereDoesntHave('qrIdentities')
            ->chunkById(200, function ($sites): void {
                foreach ($sites as $site) {
                    QrIdentity::issueFor($site);
                }
            });
    }

    public function down(): void {}
};
