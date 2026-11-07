<?php

use App\Shared\Traceability\Models\QrIdentity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Modules\Post\Models\PostArticle;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('qr_identities')) {
            return;
        }

        PostArticle::withTrashed()
            ->with('media')
            ->whereDoesntHave('qrIdentities')
            ->chunkById(200, function ($articles): void {
                foreach ($articles as $article) {
                    QrIdentity::issueFor($article);
                }
            });
    }

    public function down(): void {}
};
