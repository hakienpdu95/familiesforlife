<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\MigrationHelpers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class GenerateMigration extends Command
{
    use MigrationHelpers;

    protected $signature = 'migration:generate
        {--from=render_migration_file.json : JSON file tại project root}
        {--fresh : Sau khi generate, chạy migrate:fresh (chỉ dùng ở local/staging)}
        {--seed  : Sau migrate:fresh, chạy db:seed}
        {--force : Bỏ qua xác nhận}';

    protected $description = 'Generate migrations từ JSON — chỉ ghi vào migrations/generated/';

    private const DIR_VENDOR     = 'vendor';
    private const DIR_GENERATED  = 'generated';
    private const DIR_EXTENSIONS = 'extensions';

    // ──────────────────────────────────────────────────────────────

    public function handle(): int
    {
        $isFresh = $this->option('fresh');

        // --fresh chỉ cho phép ở local/staging
        if ($isFresh && !app()->environment('local', 'staging') && !$this->option('force')) {
            $this->error('--fresh chỉ dùng được ở local/staging. Thêm --force để bỏ qua.');
            return self::FAILURE;
        }

        // Xác nhận nếu --fresh ở staging
        if ($isFresh && app()->environment('staging') && !$this->option('force')) {
            if (!$this->confirm('Staging: --fresh sẽ xóa toàn bộ DB. Tiếp tục?')) {
                return self::SUCCESS;
            }
        }

        // 1. Đọc + validate JSON
        $jsonPath = base_path($this->option('from'));
        if (!File::exists($jsonPath)) {
            $this->error("File không tồn tại: $jsonPath");
            return self::FAILURE;
        }

        $raw      = File::get($jsonPath);
        $cleaned  = $this->sanitizeJson($raw);
        $json     = json_decode($cleaned, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('JSON không hợp lệ: ' . json_last_error_msg());
            // Tìm dòng lỗi gần đúng
            $lines = explode("\n", $cleaned);
            foreach ($lines as $i => $line) {
                $testJson = json_decode($line);
                if ($line !== '' && json_last_error() !== JSON_ERROR_NONE && str_starts_with(trim($line), '"')) {
                    $this->line('<fg=red>  → Dòng ' . ($i + 1) . ' có thể lỗi: ' . mb_substr($line, 0, 120) . '</>');
                }
            }
            $this->line('<fg=yellow>Gợi ý: trailing comma hoặc ký tự ẩn trong file JSON</>');
            return self::FAILURE;
        }

        if (!$this->validateSchema($json)) {
            return self::FAILURE;
        }

        // 2. Chuẩn bị thư mục
        $root           = database_path('migrations');
        $generatedPath  = "$root/" . self::DIR_GENERATED;
        $vendorPath     = "$root/" . self::DIR_VENDOR;
        $extensionsPath = "$root/" . self::DIR_EXTENSIONS;

        $this->ensureDirectories($root, $vendorPath, $extensionsPath);

        // 3. Xóa + tạo lại generated/ (CHỈ generated/)
        $deleted = $this->cleanDir($generatedPath);
        $this->line("<fg=yellow>Đã xóa $deleted file cũ trong generated/</>");

        // 4. Topo sort
        $sorted = $this->topologicalSort($json);
        if ($sorted === null) {
            $this->error('Phát hiện circular dependency!');
            return self::FAILURE;
        }

        // 5. Load template
        $templatePath = database_path('templates/create_base_table.php');
        if (!File::exists($templatePath)) {
            $this->error("Template không tồn tại: $templatePath");
            return self::FAILURE;
        }
        $template  = File::get($templatePath);
        $timestamp = Carbon::now();
        $count     = 0;

        // 6. Generate vào generated/
        foreach ($sorted as $tableData) {
            $count++;
            $tableInfo = explode('///', array_shift($tableData));
            $tableName = trim($tableInfo[0]);
            $className = 'Create' . Str::studly($tableName) . 'Table';

            [$fields, $indexes, $initialData] = $this->buildTableBody($tableData, $tableName);

            $fileName = sprintf(
                '%s_%06d_create_%s_table.php',
                $timestamp->format('Y_m_d_His'),
                $count,
                $tableName
            );

            $content = strtr($template, [
                '__CLASS_NAME__'   => $className,
                '__TABLE_NAME__'   => $tableName,
                '__FIELDS__'       => implode("\n            ", $fields),
                '__INDEXES__'      => $indexes
                    ? "\n\n            // Indexes\n            " . implode("\n            ", $indexes)
                    : '',
                '__INITIAL_DATA__' => $initialData
                    ? "\n\n        // Initial data\n        " . implode("\n        ", $initialData)
                    : '',
            ]);

            File::put("$generatedPath/$fileName", $content);
            $this->line("<fg=green>  ✓ generated/$fileName</>");
        }

        $this->info("\nĐã tạo $count migration(s).");

        // 6b. Tự động chạy extension:generate nếu file tồn tại
        $extJson = base_path('render_extension_file.json');
        if (File::exists($extJson)) {
            $this->newLine();
            $this->line('<fg=cyan>Chạy extension:generate...</>');
            $extCode = $this->call('extension:generate', array_filter([
                '--from'     => 'render_extension_file.json',
                '--start-at' => $count + 1, // Đảm bảo extensions sort SAU tất cả generated
                '--force'    => $this->option('force') ?: null,
            ]));
            if ($extCode !== 0) {
                $this->error('extension:generate thất bại.');
                return self::FAILURE;
            }
        } else {
            $this->line('<fg=gray>  render_extension_file.json không tồn tại — bỏ qua extensions.</>');
        }

        Log::info('migration:generate', [
            'actor'     => auth()->user()?->email ?? 'console',
            'generated' => $count,
            'deleted'   => $deleted,
            'fresh'     => $isFresh,
        ]);

        // 7. Nếu --fresh → chạy migrate:fresh
        if ($isFresh) {
            $this->newLine();
            $this->line('<fg=cyan>Chạy migrate:fresh...</>');

            // Xóa schema dump nếu có — tránh conflict khi migrate:fresh --path load
            // cả dump lẫn generated migrations (tạo trùng bảng)
            $schemaDump = database_path('schema/mysql-schema.sql');
            if (File::exists($schemaDump)) {
                File::delete($schemaDump);
                $this->line('<fg=yellow>  Đã xóa schema dump để tránh duplicate table.</> ');
            }

            $seedOption = $this->option('seed') ? ['--seed' => true] : [];
            $exitCode   = $this->call('migrate:fresh', array_merge(
                ['--path' => [
                    'database/migrations/' . self::DIR_VENDOR,
                    'database/migrations/' . self::DIR_GENERATED,
                    'database/migrations/' . self::DIR_EXTENSIONS,
                ]],
                $seedOption,
                $this->option('force') ? ['--force' => true] : []
            ));

            if ($exitCode === 0) {
                $this->info('migrate:fresh thành công — DB đã được tạo lại hoàn toàn.');
            } else {
                $this->error('migrate:fresh thất bại. Kiểm tra lỗi bên trên.');
                return self::FAILURE;
            }
        } else {
            // Không --fresh → hướng dẫn bước tiếp theo
            $this->newLine();
            $this->line('<fg=cyan>Bước tiếp theo:</>');
            $this->line('  DEV   → <fg=white>php artisan migration:generate --fresh</>  (xóa DB + tạo lại)');
            $this->line('  PROD  → <fg=white>php artisan migrate</>                     (chỉ chạy file mới)');
        }

        return self::SUCCESS;
    }

    // ──────────────────────────────────────────────────────────────
    // DIRECTORY MANAGEMENT
    // ──────────────────────────────────────────────────────────────

    private function ensureDirectories(string $root, string $vendor, string $extensions): void
    {
        foreach ([$vendor, $extensions] as $dir) {
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
                File::put("$dir/.gitkeep", '');
                $this->line('<fg=yellow>  Tạo: migrations/' . basename($dir) . '/</> ');
            }
        }

        if (!File::exists("$root/README.md")) {
            File::put("$root/README.md", $this->readmeContent());
        }
    }

    // ──────────────────────────────────────────────────────────────
    // BUILD TABLE BODY
    // ──────────────────────────────────────────────────────────────

    /** @return array{string[], string[], string[]} */
    private function buildTableBody(array $rows, string $tableName): array
    {
        $fields        = [];
        $indexes       = [];
        $initialData   = [];
        $hasCreatedAt  = false;
        $hasUpdatedAt  = false;
        $hasSoftDelete = false;

        foreach ($rows as $row) {
            $p = explode('///', $row);
            while (count($p) < 7) $p[] = '__';
            [$colName, $colType, $colLen, $colNull, $colDefault, $colMod, $colComment] = $p;

            if ($colName === '__index') {
                $this->buildIndexDirective($colMod, $colType, $indexes);
                continue;
            }
            if ($colName === '__primary') {
                $cols = array_map(fn($c) => "'" . trim($c) . "'", explode(',', $colMod));
                $fields[] = '$table->primary([' . implode(', ', $cols) . ']);';
                continue;
            }
            if ($colName === '__initial_data') {
                $this->buildInitialData($colMod, $tableName, $initialData);
                continue;
            }

            if ($colName === 'created_at' && $colType === 'timestamp') { $hasCreatedAt  = true; continue; }
            if ($colName === 'updated_at' && $colType === 'timestamp') { $hasUpdatedAt  = true; continue; }
            if ($colName === 'deleted_at' && $colType === 'timestamp') { $hasSoftDelete = true; continue; }

            // Bỏ qua dòng id trong JSON — id/uuid/order_column được thêm tự động bên dưới
            if ($colName === 'id') {
                continue;
            }

            $fields[] = $this->buildColumn($colName, $colType, $colLen, $colNull, $colDefault, $colMod, $colComment);
        }

        // Luôn tự động prepend 3 cột đầu chuẩn Laravel 13 — không phụ thuộc JSON:
        //   1. id()           — bigIncrements + primary key
        //   2. uuid()         — public UUID để expose ra ngoài (không phải PK)
        //   3. order_column   — Spatie Sortable / ORDER BY
        array_unshift(
            $fields,
            '$table->id();',
            "\$table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');",
            "\$table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');"
        );

        if ($hasCreatedAt && $hasUpdatedAt) {
            $fields[] = '$table->timestamps();';
        } elseif ($hasCreatedAt) {
            $fields[] = '$table->timestamp(\'created_at\')->nullable();';
        } elseif ($hasUpdatedAt) {
            $fields[] = '$table->timestamp(\'updated_at\')->nullable();';
        }
        if ($hasSoftDelete) {
            $fields[] = '$table->softDeletes();';
        }

        return [$fields, $indexes, $initialData];
    }

    // ──────────────────────────────────────────────────────────────
    // BUILD COLUMN
    // ──────────────────────────────────────────────────────────────

    private function buildColumn(
        string $name, string $type, string $len,
        string $null, string $default,
        string $mod, string $comment
    ): string {
        $nullable = $null === '_NULL' || $null === 'NULL';
        $noLen    = in_array($type, self::NO_LENGTH_TYPES);

        if ($type === 'unsignedBigInteger'
            && $mod !== '__'
            && str_contains($mod, 'constrained')
            && !str_contains($mod, 'references(')
        ) {
            return $this->buildForeignId($name, $nullable, $mod, $comment);
        }

        if (in_array($type, ['char', 'string', 'uuid'])
            && $mod !== '__'
            && str_contains($mod, 'references(')
        ) {
            return $this->buildCustomFk($name, $type, $len, $nullable, $default, $mod, $comment);
        }

        $params = '';
        if (!$noLen && $len !== '__' && $len !== '') {
            $params = $this->formatParams($type, $len);
        }

        if (in_array($type, ['enum', 'set'])) {
            $def = "\$table->$type('$name', $params)";
        } elseif ($params !== '') {
            $def = "\$table->$type('$name', $params)";
        } else {
            $def = "\$table->$type('$name')";
        }

        if ($nullable)         $def .= '->nullable()';
        if ($default !== '__') $def .= $this->formatDefault($type, $default);

        // timestamp NOT NULL không có default → MySQL strict mode từ chối (SQLSTATE 1067)
        // useCurrent() thêm DEFAULT CURRENT_TIMESTAMP; app vẫn override được khi insert
        if ($type === 'timestamp' && !$nullable && $default === '__') {
            $def .= '->useCurrent()';
        }

        if ($mod !== '__'
            && !str_contains($mod, 'constrained')
            && !str_contains($mod, 'references(')
        ) {
            $def .= $mod;
        }

        if ($comment !== '__') {
            $def .= "->comment('" . addslashes($comment) . "')";
        }

        return $def . ';';
    }

    private function buildForeignId(string $name, bool $nullable, string $mod, string $comment): string
    {
        $def = "\$table->foreignId('$name')";
        if ($nullable) $def .= '->nullable()';
        $def .= $this->normalizeOnDelete($mod);
        if ($comment !== '__') $def .= "->comment('" . addslashes($comment) . "')";
        return $def . ';';
    }

    private function buildCustomFk(
        string $name, string $type, string $len,
        bool $nullable, string $default,
        string $mod, string $comment
    ): string {
        preg_match("/->references\('([^']+)'\)->constrained\('([^']+)'\)(.*)/", $mod, $m);

        if (!isset($m[1], $m[2])) {
            return $this->buildColumn($name, $type, $len, $nullable ? '_NULL' : 'NOT_NULL', $default, '__', $comment);
        }

        $params = ($len !== '__' && $len !== '') ? $this->formatParams($type, $len) : '';
        $col    = "\$table->$type('$name'" . ($params ? ", $params" : '') . ')';
        if ($nullable)         $col .= '->nullable()';
        if ($default !== '__') $col .= $this->formatDefault($type, $default);
        if ($comment !== '__') $col .= "->comment('" . addslashes($comment) . "')";
        $col .= ';';

        $extra = $this->normalizeOnDelete(trim($m[3] ?? ''));
        $fk    = "\$table->foreign('$name')->references('{$m[1]}')->on('{$m[2]}')$extra;";

        return $col . "\n            " . $fk;
    }

    private function buildInitialData(string $colMod, string $tableName, array &$out): void
    {
        foreach (explode(';', rtrim($colMod, ';')) as $record) {
            $record = trim($record);
            if ($record === '') continue;

            $data = []; $uniqueKey = null; $uniqueVal = null;

            foreach (explode(',', $record) as $pair) {
                [$k, $v] = explode(':', $pair, 2);
                $k = trim($k); $v = trim($v);
                $phpVal = match (true) {
                    $v === 'now'      => 'Carbon::now()',
                    $k === 'password' => "Hash::make('$v')",
                    $v === 'true'     => 'true',
                    $v === 'false'    => 'false',
                    is_numeric($v)    => $v,
                    default           => "'$v'",
                };
                $data[] = "'$k' => $phpVal";
                foreach (['id', 'email', 'name', 'slug'] as $uk) {
                    if ($k === $uk && $uniqueKey === null) {
                        $uniqueKey = $k; $uniqueVal = $phpVal;
                    }
                }
            }

            $dataStr = implode(', ', $data);
            $out[] = $uniqueKey
                ? "DB::table('$tableName')->updateOrInsert(\n            ['$uniqueKey' => $uniqueVal],\n            [$dataStr]\n        );"
                : "DB::table('$tableName')->insert([$dataStr]);";
        }
    }

    // ──────────────────────────────────────────────────────────────
    // VALIDATE SCHEMA
    // ──────────────────────────────────────────────────────────────

    private function validateSchema(array $json): bool
    {
        $tableNames = [];
        foreach ($json as $i => $table) {
            if (!is_array($table) || empty($table)) {
                $this->error("Table[$i]: rỗng."); return false;
            }
            $info  = explode('///', $table[0]);
            $tName = trim($info[0] ?? '');
            if (!$tName) { $this->error("Table[$i]: thiếu tên."); return false; }
            if (isset($tableNames[$tName])) { $this->error("Trùng tên bảng: '$tName'"); return false; }
            $tableNames[$tName] = true;

            foreach (array_slice($table, 1) as $j => $field) {
                $p = explode('///', $field);
                if (count($p) < 7) { $this->error("$tName[$j]: cần 7 phần, nhận " . count($p)); return false; }
                if (in_array($p[0], self::SPECIAL_DIRECTIVES)) continue;
                if (!in_array($p[1], self::VALID_TYPES)) { $this->error("$tName.{$p[0]}: type không hợp lệ '{$p[1]}'"); return false; }
                if (in_array($p[1], ['enum', 'set']) && !preg_match('/^\[.+\]$/', $p[2])) {
                    $this->error("$tName.{$p[0]}: enum/set cần [val1,val2,...]"); return false;
                }
            }
        }
        return true;
    }

    // ──────────────────────────────────────────────────────────────
    // TOPOLOGICAL SORT (Kahn's algorithm)
    // ──────────────────────────────────────────────────────────────

    private function topologicalSort(array $json): ?array
    {
        $tables = []; $indeg = []; $adjList = [];

        foreach ($json as $t) {
            $name           = trim(explode('///', $t[0])[0]);
            $tables[$name]  = $t;
            $indeg[$name]   ??= 0;
            $adjList[$name] ??= [];
        }
        foreach ($json as $t) {
            $name = trim(explode('///', $t[0])[0]);
            foreach (array_slice($t, 1) as $f) {
                $p = explode('///', $f);
                if (count($p) < 6 || $p[5] === '__' || !str_contains($p[5], 'constrained')) continue;

                // Case 1: constrained('explicit_table_name')
                preg_match("/constrained\('([^']+)'\)/", $p[5], $m);
                $dep = $m[1] ?? null;

                // Case 2: constrained() không tham số → suy ra từ tên cột
                // Laravel convention: deployment_target_id → deployment_targets
                if ($dep === null && preg_match('/constrained\(\)/', $p[5])) {
                    $colName = trim($p[0]);
                    if (str_ends_with($colName, '_id')) {
                        $dep = Str::plural(substr($colName, 0, -3));
                    }
                }

                if ($dep && isset($tables[$dep]) && $dep !== $name) {
                    $adjList[$dep][] = $name;
                    $indeg[$name]++;
                }
            }
        }

        $queue  = array_keys(array_filter($indeg, fn($d) => $d === 0));
        $sorted = [];
        while (!empty($queue)) {
            $node     = array_shift($queue);
            $sorted[] = $tables[$node];
            foreach ($adjList[$node] as $nb) {
                if (--$indeg[$nb] === 0) $queue[] = $nb;
            }
        }

        return count($sorted) === count($tables) ? $sorted : null;
    }

    // ──────────────────────────────────────────────────────────────
    // README
    // ──────────────────────────────────────────────────────────────

    private function readmeContent(): string
    {
        return <<<'MD'
        # database/migrations — 3 vùng tách biệt

        ## vendor/
        Chứa migration của package (Spatie Permission, ActivityLog, MediaLibrary, Laravel default).
        **KHÔNG chỉnh sửa, KHÔNG xóa.**
        Cách publish: `php artisan vendor:publish --provider="..."`

        ## generated/
        Tự sinh bởi `php artisan migration:generate`.
        **Xóa + tạo lại mỗi lần chạy lệnh.**
        Nguồn dữ liệu: `render_migration_file.json`

        ## extensions/
        Viết tay để mở rộng bảng vendor hoặc thêm migration đặc biệt.
        **KHÔNG xóa bao giờ.**
        Dùng `Schema::table()` (ALTER), không dùng `Schema::create()`.

        ---

        ## Quy trình dev hàng ngày

        ```bash
        # Sửa JSON → generate + fresh DB (local)
        php artisan migration:generate --fresh

        # Sửa JSON → generate + fresh + seed (local)
        php artisan migration:generate --fresh --seed

        # Thêm cột vào bảng vendor → tạo file extensions/ → chạy migrate
        php artisan make:migration add_dept_to_users_table --path=database/migrations/extensions
        php artisan migrate

        # Production: chỉ chạy file mới
        php artisan migrate
        ```

        ## Quy tắc đặt tên extensions/
        - Thêm cột : `add_{column}_to_{table}_table.php`
        - Xóa cột  : `drop_{column}_from_{table}_table.php`
        - Sửa cột  : `change_{column}_in_{table}_table.php`
        - Data seed: `seed_{table}_initial_data.php`
        MD;
    }
}