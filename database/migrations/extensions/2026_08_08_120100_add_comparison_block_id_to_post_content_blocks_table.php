<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** GEO đợt 6 (2026-08-08) — xem 2026_08_08_120000_create_post_comparison_blocks_table.php. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_content_blocks', function (Blueprint $table): void {
            if (! Schema::hasColumn('post_content_blocks', 'comparison_block_id')) {
                $table->foreignId('comparison_block_id')
                    ->nullable()
                    ->after('howto_block_id')
                    ->constrained('post_comparison_blocks')
                    ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('post_content_blocks', function (Blueprint $table): void {
            if (Schema::hasColumn('post_content_blocks', 'comparison_block_id')) {
                $table->dropConstrainedForeignId('comparison_block_id');
            }
        });
    }
};
