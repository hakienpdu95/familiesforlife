<?php

namespace App\Console\Commands;

use App\Models\Province;
use App\Models\Region;
use App\Models\Ward;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportProvincesAndWards extends Command
{
    protected $signature = 'import:provinces-wards
        {--chunk=500 : Số records mỗi lần bulk insert}
        {--force : Truncate toàn bộ 3 bảng rồi import lại từ đầu}';

    protected $description = 'Import regions, provinces, wards từ datafiles/provinces.json (idempotent)';

    public function handle(): int
    {
        // 1. Đọc + parse JSON
        $jsonFile = base_path('datafiles/provinces.json');
        if (!File::exists($jsonFile)) {
            $this->error('File không tồn tại: datafiles/provinces.json');
            return self::FAILURE;
        }

        $jsonData = json_decode(File::get($jsonFile), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('JSON không hợp lệ: ' . json_last_error_msg());
            return self::FAILURE;
        }

        // Trích xuất dữ liệu 3 bảng từ JSON
        $byName    = collect($jsonData)->where('type', 'table')->keyBy('name');
        $regions   = $byName->get('regions')['data']   ?? [];
        $provinces = $byName->get('provinces')['data'] ?? [];
        $wards     = $byName->get('wards')['data']     ?? [];

        if (!$regions || !$provinces || !$wards) {
            $this->error('File JSON thiếu dữ liệu bảng regions / provinces / wards.');
            return self::FAILURE;
        }

        $chunkSize = max(1, (int) $this->option('chunk'));
        $now       = Carbon::now();

        // 2. --force: truncate toàn bộ rồi import lại
        if ($this->option('force')) {
            if (!$this->confirm('Sẽ xóa toàn bộ dữ liệu trong regions, provinces, wards. Tiếp tục?')) {
                $this->info('Đã hủy.');
                return self::SUCCESS;
            }

            $this->disableForeignKeys();
            Ward::truncate();
            Province::truncate();
            Region::truncate();
            $this->enableForeignKeys();
            $this->info('Đã truncate 3 bảng.');
        }

        // 3. Import theo thứ tự dependency: regions → provinces → wards
        try {
            $this->importRegions($regions, $chunkSize, $now);
            $this->importProvinces($provinces, $chunkSize, $now);
            $this->importWards($wards, $chunkSize, $now);
        } catch (\Throwable $e) {
            $this->error('Import thất bại: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('✓ Import hoàn tất.');
        return self::SUCCESS;
    }

    // ──────────────────────────────────────────────────────────────
    // IMPORT REGIONS
    // ──────────────────────────────────────────────────────────────

    private function importRegions(array $data, int $chunkSize, Carbon $now): void
    {
        $this->info('Importing ' . count($data) . ' regions...');

        $rows = array_map(fn($r) => [
            'id'         => (int) $r['id'],
            'name'       => $r['name'],
            'created_at' => $now,
            'updated_at' => $now,
        ], $data);

        DB::transaction(function () use ($rows, $chunkSize) {
            foreach (array_chunk($rows, $chunkSize) as $chunk) {
                Region::upsert($chunk, ['id'], ['name', 'updated_at']);
            }
        });

        $this->line('  <fg=green>✓ ' . count($rows) . ' regions.</>');
    }

    // ──────────────────────────────────────────────────────────────
    // IMPORT PROVINCES
    // ──────────────────────────────────────────────────────────────

    private function importProvinces(array $data, int $chunkSize, Carbon $now): void
    {
        $this->info('Importing ' . count($data) . ' provinces...');

        // Cache region IDs để validate FK — tránh N+1
        $validRegionIds = Region::pluck('id')->flip()->all(); // [id => index]

        $rows    = [];
        $skipped = 0;

        foreach ($data as $item) {
            $regionId = (int) $item['region_id'];

            if (!array_key_exists($regionId, $validRegionIds)) {
                $this->warn("  ⚠ Region {$regionId} không tồn tại — bỏ qua province '{$item['name']}'.");
                $skipped++;
                continue;
            }

            $rows[] = [
                'id'            => (int) $item['id'],
                'province_code' => $item['province_code'],
                'name'          => $item['name'],
                'short_name'    => $item['short_name'],
                'place_type'    => $this->mapProvinceType($item['place_type']),
                'region_id'     => $regionId,
                'country'       => $item['country'] ?? 'VN',
                'is_active'     => true,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }

        DB::transaction(function () use ($rows, $chunkSize) {
            foreach (array_chunk($rows, $chunkSize) as $chunk) {
                Province::upsert(
                    $chunk,
                    ['province_code'],
                    ['name', 'short_name', 'place_type', 'region_id', 'country', 'is_active', 'updated_at']
                );
            }
        });

        $msg = count($rows) . ' provinces';
        if ($skipped) $msg .= " ($skipped skipped)";
        $this->line("  <fg=green>✓ {$msg}.</>");
    }

    // ──────────────────────────────────────────────────────────────
    // IMPORT WARDS
    // ──────────────────────────────────────────────────────────────

    private function importWards(array $data, int $chunkSize, Carbon $now): void
    {
        $total = count($data);
        $this->info("Importing {$total} wards...");

        // Cache province_codes — tránh N+1 query
        $validCodes = Province::pluck('province_code')->flip()->all(); // [code => index]

        $rows    = [];
        $skipped = 0;

        foreach ($data as $item) {
            $code = $item['province_code'];

            if (!array_key_exists($code, $validCodes)) {
                $this->warn("  ⚠ Province '{$code}' không tồn tại — bỏ qua ward '{$item['name']}'.");
                $skipped++;
                continue;
            }

            $rows[] = [
                'id'            => (int) $item['id'],
                'ward_code'     => $item['ward_code'],
                'name'          => $item['name'],
                'place_type'    => $this->inferWardType($item['name']),
                'province_code' => $code,
                'is_active'     => true,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }

        $chunks = array_chunk($rows, $chunkSize);

        DB::transaction(function () use ($chunks) {
            $this->withProgressBar($chunks, function (array $chunk) {
                Ward::upsert(
                    $chunk,
                    ['ward_code'],
                    ['name', 'place_type', 'province_code', 'is_active', 'updated_at']
                );
            });
        });

        $this->newLine();
        $msg = count($rows) . ' wards';
        if ($skipped) $msg .= " ($skipped skipped)";
        $this->line("  <fg=green>✓ {$msg}.</>");
    }

    // ──────────────────────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────────────────────

    /**
     * Map place_type từ dữ liệu JSON sang enum DB.
     * JSON: "Thành phố Trung Ương" | "Tỉnh"
     * DB:   "thanh-pho"            | "tinh"
     */
    private function mapProvinceType(string $type): string
    {
        return $type === 'Tỉnh' ? 'tinh' : 'thanh-pho';
    }

    /**
     * Suy luận place_type của phường/xã từ tiền tố tên.
     * DB enum: phuong | xa | dac-khu
     */
    private function inferWardType(string $name): string
    {
        if (str_starts_with($name, 'Phường')) return 'phuong';
        if (str_starts_with($name, 'Xã'))    return 'xa';
        return 'dac-khu';
    }

    private function disableForeignKeys(): void
    {
        match (DB::connection()->getDriverName()) {
            'mysql'  => DB::statement('SET FOREIGN_KEY_CHECKS=0'),
            'sqlite' => DB::statement('PRAGMA foreign_keys = OFF'),
            default  => null,
        };
    }

    private function enableForeignKeys(): void
    {
        match (DB::connection()->getDriverName()) {
            'mysql'  => DB::statement('SET FOREIGN_KEY_CHECKS=1'),
            'sqlite' => DB::statement('PRAGMA foreign_keys = ON'),
            default  => null,
        };
    }
}
