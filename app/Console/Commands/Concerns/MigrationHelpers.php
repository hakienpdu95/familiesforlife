<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Support\Facades\File;

/**
 * Shared helpers dùng chung giữa GenerateMigration và GenerateExtension.
 * Đặt tất cả logic tái sử dụng ở đây để đảm bảo nhất quán 100%.
 */
trait MigrationHelpers
{
    // ──────────────────────────────────────────────────────────────
    // CONSTANTS
    // ──────────────────────────────────────────────────────────────

    private const VALID_TYPES = [
        'increments', 'bigIncrements',
        'tinyInteger', 'smallInteger', 'mediumInteger', 'integer', 'bigInteger',
        'unsignedTinyInteger', 'unsignedSmallInteger', 'unsignedMediumInteger',
        'unsignedInteger', 'unsignedBigInteger',
        'uuid', 'ulid',
        'float', 'double', 'decimal',
        'string', 'char', 'binary',
        'text', 'mediumText', 'longText', 'tinyText',
        'date', 'dateTime', 'timestamp', 'time', 'year',
        'boolean', 'enum', 'set', 'json', 'jsonb', 'ip',
    ];

    // Types không nhận tham số length — đồng nhất cho cả create và alter
    private const NO_LENGTH_TYPES = [
        'boolean', 'text', 'mediumText', 'longText', 'tinyText',
        'date', 'dateTime', 'timestamp', 'time', 'year',
        'json', 'jsonb', 'ip', 'uuid', 'ulid',
        'unsignedBigInteger', 'unsignedInteger', 'unsignedSmallInteger',
        'unsignedMediumInteger', 'unsignedTinyInteger',
        'bigInteger', 'integer', 'mediumInteger',
        'increments', 'bigIncrements',
    ];

    // Các directive đặc biệt trong JSON — không phải cột thật
    private const SPECIAL_DIRECTIVES = ['__index', '__primary', '__initial_data', '__fk'];

    // ──────────────────────────────────────────────────────────────
    // JSON SANITIZE — best-of-both từ GenerateMigration + GenerateExtension
    // ──────────────────────────────────────────────────────────────

    private function sanitizeJson(string $raw): string
    {
        // 1. BOM: UTF-8 (EF BB BF) hoặc UTF-16 (FF FE / FE FF)
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        } elseif (str_starts_with($raw, "\xFF\xFE") || str_starts_with($raw, "\xFE\xFF")) {
            $raw = mb_convert_encoding($raw, 'UTF-8', 'UTF-16');
        }

        // 2. Chuẩn hóa line ending
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);

        // 3. Xóa control characters 0x00–0x1F trừ tab(0x09) và LF(0x0A); xóa DEL(0x7F)
        $raw = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $raw);

        // 4. Xóa zero-width space (U+200B) và non-breaking space (U+00A0)
        $raw = str_replace(["\xE2\x80\x8B", "\xC2\xA0"], ['', ' '], $raw);

        // 5. Xóa trailing comma trước ] hoặc }
        // NOTE: KHÔNG xóa // comment vì /// là delimiter nội dung trong JSON string
        $raw = preg_replace('/,(\s*[\]\}])/', '$1', $raw);

        return $raw;
    }

    // ──────────────────────────────────────────────────────────────
    // COLUMN HELPERS
    // ──────────────────────────────────────────────────────────────

    private function normalizeOnDelete(string $str): string
    {
        return str_replace(
            ["->onDelete('cascade')", "->onDelete('set null')", "->onDelete('restrict')", "->onDelete('no action')"],
            ['->cascadeOnDelete()',   '->nullOnDelete()',       '->restrictOnDelete()',   '->noActionOnDelete()'],
            $str
        );
    }

    private function formatParams(string $type, string $raw): string
    {
        $raw = trim(str_replace(['(', ')'], '', $raw));
        if (in_array($type, ['enum', 'set']) && preg_match('/^\[(.+)\]$/', $raw, $m)) {
            $vals = array_map(fn($v) => "'" . trim($v) . "'", explode(',', $m[1]));
            return '[' . implode(', ', $vals) . ']';
        }
        if ($type === 'decimal' && str_contains($raw, ',')) {
            [$p, $s] = explode(',', $raw, 2);
            return trim($p) . ', ' . trim($s);
        }
        return is_numeric($raw) ? $raw : "'$raw'";
    }

    private function formatDefault(string $type, string $value): string
    {
        if (strtolower($value) === 'null') return '';
        if (in_array(strtolower($value), ['true', 'false'])) return '->default(' . strtolower($value) . ')';
        if (is_numeric($value)) return "->default($value)";
        if (str_starts_with($value, "'") && str_ends_with($value, "'")) return "->default($value)";
        return "->default('$value')";
    }

    // ──────────────────────────────────────────────────────────────
    // INDEX DIRECTIVE
    // ──────────────────────────────────────────────────────────────

    private function buildIndexDirective(string $colMod, string $indexType, array &$lines): void
    {
        $method = match (strtolower($indexType)) {
            'fulltext' => 'fullText',
            'unique'   => 'unique',
            'spatial'  => 'spatialIndex',
            default    => 'index',
        };

        foreach (explode(';', $colMod) as $entry) {
            $entry = trim($entry);
            if ($entry === '' || $entry === '__') continue;

            // Optional named index: "col1,col2|index_name"
            $name = null;
            if (str_contains($entry, '|')) {
                [$entry, $name] = explode('|', $entry, 2);
                $entry = trim($entry);
                $name  = trim($name);
            }

            $cols    = array_map(fn($c) => "'" . trim($c) . "'", explode(',', $entry));
            $nameArg = $name ? ", '$name'" : '';
            $lines[] = count($cols) > 1
                ? "\$table->$method([" . implode(', ', $cols) . "]$nameArg);"
                : "\$table->$method({$cols[0]}$nameArg);";
        }
    }

    // ──────────────────────────────────────────────────────────────
    // DIRECTORY MANAGEMENT
    // ──────────────────────────────────────────────────────────────

    private function cleanDir(string $path): int
    {
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
            File::put("$path/.gitkeep", '');
            return 0;
        }
        $count = 0;
        foreach (File::files($path) as $file) {
            if ($file->getFilename() === '.gitkeep') continue;
            File::delete($file->getPathname());
            $count++;
        }
        return $count;
    }
}
