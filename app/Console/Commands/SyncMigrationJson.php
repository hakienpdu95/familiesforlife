<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\MigrationHelpers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Quét migration files → đồng bộ vào render_migration_file.json và render_extension_file.json.
 *
 * Phạm vi:
 *   - database/migrations/generated/
 *   - Modules/*\/database/migrations/
 *   Bỏ qua: database/migrations/vendor/
 */
class SyncMigrationJson extends Command
{
    use MigrationHelpers;

    protected $signature = 'migration:sync
        {--dry-run : Chỉ in ra diff, không ghi file}
        {--migration-json=render_migration_file.json : File JSON migration}
        {--extension-json=render_extension_file.json : File JSON extension}';

    protected $description = 'Sync migration files ngược vào render_migration_file.json và render_extension_file.json';

    private const DROPPED_TABLES = [
        'feature_weight_history', 'feature_weights',
        'tuning_cycles', 'tuning_schedule_config', 'feedback_sources_config',
        'result_job_positions', 'job_positions',
        'survey_results',
    ];

    private const AUTO_COLUMNS = ['id', 'order_column'];

    // ──────────────────────────────────────────────────────────────────────────

    public function handle(): int
    {
        $migrationJsonPath = base_path($this->option('migration-json'));
        $extensionJsonPath = base_path($this->option('extension-json'));

        $migrationJson = $this->loadJsonFile($migrationJsonPath);
        $extensionJson = $this->loadJsonFile($extensionJsonPath);
        if ($migrationJson === null || $extensionJson === null) return self::FAILURE;

        $existingTables     = $this->buildTableNameSet($migrationJson);
        $existingExtMap     = $this->buildExtensionMap($extensionJson);
        $baseColumnMap      = $this->buildBaseColumnMap($migrationJson);

        $files = $this->collectMigrationFiles();
        $this->line('Scanning <fg=cyan>' . count($files) . '</> migration files...');

        $newTables    = [];   // tableName → entry
        $allExtBlocks = [];   // tableName → [(body, file), ...]
        $skipped      = [];

        foreach ($files as $file) {
            $upContent = $this->extractUpContent(File::get($file));
            $basename  = basename($file);

            // Schema::create (PHP)
            foreach ($this->extractCreateBlocks($upContent) as [$tableName, $body]) {
                if (in_array($tableName, self::DROPPED_TABLES)) { $skipped[$tableName] = true; continue; }
                if (isset($existingTables[$tableName], $newTables[$tableName])) continue;
                if (isset($existingTables[$tableName]) || isset($newTables[$tableName])) continue;
                $entry = $this->buildMigrationEntry($tableName, $body);
                if ($entry) $newTables[$tableName] = $entry;
            }

            // DB::statement("CREATE TABLE ...") — raw SQL
            foreach ($this->extractSqlCreateBlocks($upContent) as [$tableName, $sqlBody]) {
                if (in_array($tableName, self::DROPPED_TABLES)) { $skipped[$tableName] = true; continue; }
                if (isset($existingTables[$tableName]) || isset($newTables[$tableName])) continue;
                $entry = $this->buildMigrationEntryFromSql($tableName, $sqlBody);
                if ($entry) $newTables[$tableName] = $entry;
            }

            // Schema::table — collect tất cả, sẽ merge sau
            foreach ($this->extractTableBlocks($upContent) as [$tableName, $body]) {
                if (in_array($tableName, self::DROPPED_TABLES)) continue;
                $allExtBlocks[$tableName][] = [$body, $basename];
            }
        }

        // Process extensions: merge tất cả blocks cho mỗi table
        $newExtensions     = [];  // tableName → entry
        $updatedExtensions = [];  // tableName → [existingIdx, updatedEntry]
        $baseTableUpdates  = [];  // tableName → [entryIdx, rowIdx, colName, old, new]
        $extColumnUpdates  = [];  // tableName → [colName => newRow]  (change() trên cột extension đã sync trước đó)

        foreach ($allExtBlocks as $tableName => $blocks) {
            $baseCols        = $baseColumnMap[$tableName]['cols'] ?? [];
            $existingExtCols = $existingExtMap[$tableName]['colNames'] ?? [];

            [$mergedCols, $mergedIndexes, $baseChanges, $extChanges] =
                $this->mergeExtensionBlocks($blocks, $baseCols, $existingExtCols);

            // ->change() trên cột thuộc bảng gốc (có trong render_migration_file.json)
            // → patch trực tiếp row của bảng gốc, không đưa vào extension.
            foreach ($baseChanges as $colName => $row) {
                $entryIdx = $baseColumnMap[$tableName]['entryIndex'];
                $rowIdx   = $baseCols[$colName];
                $oldRow   = $migrationJson[$entryIdx][$rowIdx];
                $row      = $this->preserveUnstatedModifiers($oldRow, $row);
                if ($oldRow !== $row) {
                    $baseTableUpdates[$tableName][] = [
                        'entryIdx' => $entryIdx, 'rowIdx' => $rowIdx,
                        'colName'  => $colName,
                        'old'      => $oldRow, 'new' => $row,
                    ];
                }
            }

            // ->change() trên cột đã tồn tại trong extension JSON (từ lần sync trước)
            // nhưng không được ADD lại trong lượt scan này.
            foreach ($extChanges as $colName => $row) {
                $oldRow = null;
                foreach ($extensionJson[$existingExtMap[$tableName]['index'] ?? -1] ?? [] as $r) {
                    if (explode('///', $r)[0] === $colName) { $oldRow = $r; break; }
                }
                $extColumnUpdates[$tableName][$colName] = $oldRow
                    ? $this->preserveUnstatedModifiers($oldRow, $row)
                    : $row;
            }

            $allMergedRows = array_values($mergedCols) + [];
            foreach ($mergedIndexes as $idxRow) $allMergedRows[] = $idxRow;

            if (empty($mergedCols) && empty($mergedIndexes)) continue;

            if (isset($existingExtMap[$tableName])) {
                // Table đã có extension → chỉ thêm cột CHƯA có
                $existingIdx      = $existingExtMap[$tableName]['index'];
                $existingColNames = $existingExtMap[$tableName]['colNames'];
                $existingIdxKeys  = $existingExtMap[$tableName]['indexKeys'];

                $newCols = array_filter(
                    $mergedCols,
                    fn($row, $colName) => !isset($existingColNames[$colName]),
                    ARRAY_FILTER_USE_BOTH
                );
                $newIdxRows = array_filter(
                    $mergedIndexes,
                    fn($row, $key) => !isset($existingIdxKeys[$key]),
                    ARRAY_FILTER_USE_BOTH
                );

                if (!empty($newCols) || !empty($newIdxRows)) {
                    $updatedEntry = $extensionJson[$existingIdx];
                    foreach ($newCols    as $row) $updatedEntry[] = $row;
                    foreach ($newIdxRows as $row) $updatedEntry[] = $row;
                    $updatedExtensions[$tableName] = [$existingIdx, $updatedEntry];
                }
            } else {
                // Table chưa có extension → tạo mới
                $entry = $this->buildExtensionEntryFromMerged($tableName, $mergedCols, $mergedIndexes);
                if ($entry) $newExtensions[$tableName] = $entry;
            }
        }

        // Report
        $this->printResults($newTables, $newExtensions, $updatedExtensions, $skipped, $baseTableUpdates, $extColumnUpdates);

        $hasChanges = !empty($newTables) || !empty($newExtensions) || !empty($updatedExtensions)
            || !empty($baseTableUpdates) || !empty($extColumnUpdates);
        if (!$hasChanges) {
            $this->info('Nothing new to add.');
            return self::SUCCESS;
        }

        if (!$this->option('dry-run')) {
            foreach ($newTables as $entry)      $migrationJson[] = $entry;
            foreach ($updatedExtensions as [$idx, $entry]) $extensionJson[$idx] = $entry;
            foreach ($newExtensions as $entry)  $extensionJson[] = $entry;

            foreach ($baseTableUpdates as $updates) {
                foreach ($updates as $upd) {
                    $migrationJson[$upd['entryIdx']][$upd['rowIdx']] = $upd['new'];
                }
            }
            foreach ($extColumnUpdates as $tableName => $cols) {
                $idx = $existingExtMap[$tableName]['index'];
                foreach ($extensionJson[$idx] as $i => $row) {
                    $colName = explode('///', $row)[0];
                    if (isset($cols[$colName])) $extensionJson[$idx][$i] = $cols[$colName];
                }
            }

            File::put($migrationJsonPath, $this->encodeJson($migrationJson));
            File::put($extensionJsonPath, $this->encodeJson($extensionJson));
            $this->info("\nFiles updated successfully.");
        } else {
            $this->warn("\n--dry-run: no files written");
        }

        return self::SUCCESS;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // FILE COLLECTION
    // ──────────────────────────────────────────────────────────────────────────

    private function collectMigrationFiles(): array
    {
        $files = [];

        $generatedPath = database_path('migrations/generated');
        if (File::exists($generatedPath)) {
            foreach (File::files($generatedPath) as $f) {
                if ($f->getExtension() === 'php') $files[] = $f->getPathname();
            }
        }

        $modulesPath = base_path('Modules');
        if (File::exists($modulesPath)) {
            foreach (File::directories($modulesPath) as $moduleDir) {
                // NWIDART dùng Database/Migrations (uppercase) — thử cả 2 casing
                foreach (['Database/Migrations', 'database/migrations'] as $subDir) {
                    $dir = "$moduleDir/$subDir";
                    if (!File::exists($dir)) continue;
                    foreach (File::files($dir) as $f) {
                        if ($f->getExtension() === 'php') $files[] = $f->getPathname();
                    }
                }
            }
        }

        sort($files);

        // Guard: warn if any Schema::create is found in extensions/
        // extensions/ is for Schema::table only — new tables belong in generated/ or a Module.
        $this->warnMisplacedCreateMigrations();

        return $files;
    }

    /**
     * Detect Schema::create blocks inside database/migrations/extensions/ and warn.
     * extensions/ is strictly for Schema::table (add columns). New tables placed there
     * are NOT scanned by migration:sync and will be missing from the JSON.
     */
    private function warnMisplacedCreateMigrations(): void
    {
        $extensionsPath = database_path('migrations/extensions');
        if (! File::exists($extensionsPath)) {
            return;
        }

        foreach (File::files($extensionsPath) as $f) {
            if ($f->getExtension() !== 'php') continue;
            $content = File::get($f->getPathname());
            if (preg_match('/Schema\s*::\s*create\s*\(/i', $content)) {
                $this->warn(
                    '⚠  Misplaced Schema::create in extensions/: ' . $f->getFilename() . PHP_EOL
                    . '   → Move to database/migrations/generated/ or a Module migration folder.'
                );
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // UP() EXTRACTION
    // ──────────────────────────────────────────────────────────────────────────

    private function extractUpContent(string $content): string
    {
        if (!preg_match('/public\s+function\s+up\s*\(\s*\)[^{]*\{/s', $content, $m, PREG_OFFSET_CAPTURE)) {
            return $content;
        }

        $matchEnd = $m[0][1] + strlen($m[0][0]);
        $depth = 1; $pos = $matchEnd; $length = strlen($content);
        while ($pos < $length && $depth > 0) {
            $ch = $content[$pos];
            if ($ch === '{') $depth++; elseif ($ch === '}') $depth--;
            $pos++;
        }
        return substr($content, $matchEnd, $pos - $matchEnd - 1);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // JSON HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    private function loadJsonFile(string $path): ?array
    {
        if (!File::exists($path)) { $this->error("File not found: $path"); return null; }
        $data = json_decode($this->sanitizeJson(File::get($path)), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Invalid JSON in $path: " . json_last_error_msg());
            return null;
        }
        return $data;
    }

    private function buildTableNameSet(array $json): array
    {
        $set = [];
        foreach ($json as $entry) {
            $set[trim(explode('///', $entry[0])[0])] = true;
        }
        return $set;
    }

    /**
     * Returns: tableName → ['entryIndex' => i, 'cols' => [colName => rowIndexInEntry]]
     * Dùng để patch trực tiếp row của bảng gốc khi phát hiện ->change() trên
     * cột thuộc bảng gốc (không phải cột được thêm qua extension).
     */
    private function buildBaseColumnMap(array $json): array
    {
        $map = [];
        foreach ($json as $i => $entry) {
            $tableName = trim(explode('///', $entry[0])[0]);
            $cols = [];
            foreach (array_slice($entry, 1) as $offset => $row) {
                $colName = explode('///', $row)[0];
                if ($colName === '__index') continue;
                $cols[$colName] = $offset + 1;
            }
            $map[$tableName] = ['entryIndex' => $i, 'cols' => $cols];
        }
        return $map;
    }

    /**
     * ->change() thường không nhắc lại ->index()/->unique() hay ->comment() của cột —
     * ở DB thật, MODIFY COLUMN không tự xoá index/comment đang có. Nếu row mới (từ
     * change()) không khai báo mod/comment (giá trị '__'), giữ nguyên giá trị cũ thay
     * vì để trống — tránh JSON "quên" mất index khi dùng để generate lại migration.
     */
    private function preserveUnstatedModifiers(string $oldRow, string $newRow): string
    {
        $old = explode('///', $oldRow);
        $new = explode('///', $newRow);
        if (count($old) < 7 || count($new) < 7) return $newRow;

        if ($new[5] === '__' && $old[5] !== '__') $new[5] = $old[5]; // mod (->index()/->unique()/...)
        if ($new[6] === '__' && $old[6] !== '__') $new[6] = $old[6]; // comment

        return implode('///', $new);
    }

    /** Returns: tableName → ['index' => i, 'colNames' => [...], 'indexKeys' => [...]] */
    private function buildExtensionMap(array $json): array
    {
        $map = [];
        foreach ($json as $i => $entry) {
            $name      = trim(explode('///', $entry[0])[0]);
            $colNames  = [];
            $indexKeys = [];
            foreach (array_slice($entry, 1) as $row) {
                $p = explode('///', $row);
                if (($p[0] ?? '') === '__index') {
                    $key = ($p[1] ?? '') . ':' . ($p[5] ?? '');
                    $indexKeys[$key] = true;
                } elseif (($p[0] ?? '') !== '') {
                    $colNames[$p[0]] = true;
                }
            }
            $map[$name] = ['index' => $i, 'colNames' => $colNames, 'indexKeys' => $indexKeys];
        }
        return $map;
    }

    private function encodeJson(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    }

    // ──────────────────────────────────────────────────────────────────────────
    // SCHEMA BLOCK EXTRACTION
    // ──────────────────────────────────────────────────────────────────────────

    private function extractCreateBlocks(string $content): array
    {
        return $this->extractSchemaBlocks($content, 'create');
    }

    private function extractTableBlocks(string $content): array
    {
        return $this->extractSchemaBlocks($content, 'table');
    }

    private function extractSchemaBlocks(string $content, string $method): array
    {
        $results = [];
        $pattern = '/Schema::' . $method . '\(\s*\'([^\']+)\'\s*,\s*function[^{]*/';
        $offset  = 0;

        while (preg_match($pattern, $content, $m, PREG_OFFSET_CAPTURE, $offset)) {
            $tableName = $m[1][0];
            $matchEnd  = $m[0][1] + strlen($m[0][0]);
            $bracePos  = strpos($content, '{', $matchEnd - 1);
            if ($bracePos === false) { $offset = $m[0][1] + 1; continue; }

            $depth = 1; $pos = $bracePos + 1; $len = strlen($content);
            while ($pos < $len && $depth > 0) {
                $ch = $content[$pos];
                if ($ch === '{') $depth++; elseif ($ch === '}') $depth--;
                $pos++;
            }
            $results[] = [$tableName, substr($content, $bracePos + 1, $pos - $bracePos - 2)];
            $offset = $pos;
        }
        return $results;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // DB::statement CREATE TABLE PARSER
    // ──────────────────────────────────────────────────────────────────────────

    /** @return array<array{string, string}> list of [tableName, sqlBody] */
    private function extractSqlCreateBlocks(string $content): array
    {
        $results = [];
        // Match DB::statement("...") or DB::statement('...')
        // The string may span multiple lines
        if (!preg_match_all('/DB::statement\s*\(\s*"(.*?)"\s*\)/si', $content, $matches)) {
            if (!preg_match_all("/DB::statement\s*\(\s*'(.*?)'\s*\)/si", $content, $matches)) {
                return $results;
            }
        }

        foreach ($matches[1] as $sql) {
            $sql = trim($sql);
            if (!preg_match('/^\s*CREATE\s+TABLE\s+`?(\w+)`?\s*\(/si', $sql, $m)) continue;
            $tableName = $m[1];

            // Extract the column/constraint block between the outer parens
            $parenStart = strpos($sql, '(');
            if ($parenStart === false) continue;
            $depth = 1; $pos = $parenStart + 1; $len = strlen($sql);
            while ($pos < $len && $depth > 0) {
                $ch = $sql[$pos];
                if ($ch === '(') $depth++; elseif ($ch === ')') $depth--;
                $pos++;
            }
            $sqlBody = substr($sql, $parenStart + 1, $pos - $parenStart - 2);
            $results[] = [$tableName, $sqlBody];
        }
        return $results;
    }

    private function buildMigrationEntryFromSql(string $tableName, string $sqlBody): ?array
    {
        $lines = array_map('trim', explode("\n", $sqlBody));

        $cols         = [];   // colName → row string
        $indexes      = [];   // type:cols → row string
        $foreignKeys  = [];   // colName → ['table' => ..., 'onDelete' => ...]
        $virtualCols  = [];   // set of virtual/generated column names to skip in indexes
        $hasCrAt      = false;
        $hasUpAt      = false;

        foreach ($lines as $line) {
            // Remove trailing comma
            $line = rtrim(trim($line), ',');
            if ($line === '' || str_starts_with($line, '--') || str_starts_with($line, '#')) continue;

            // CONSTRAINT ... FOREIGN KEY (col) REFERENCES table(ref) ON DELETE action
            if (preg_match('/CONSTRAINT\s+\w+\s+FOREIGN\s+KEY\s*\((\w+)\)\s+REFERENCES\s+(\w+)\s*\(\w+\)\s*(?:ON\s+DELETE\s+(\w+))?/si', $line, $m)) {
                $foreignKeys[$m[1]] = [
                    'table'    => $m[2],
                    'onDelete' => strtolower($m[3] ?? 'restrict'),
                ];
                continue;
            }

            // UNIQUE KEY name (col1, col2, ...)
            if (preg_match('/^(?:UNIQUE\s+KEY|UNIQUE\s+INDEX)\s+(\w+)\s*\(([^)]+)\)/si', $line, $m)) {
                $idxName = $m[1]; $cols_ = $this->parseSqlIndexCols($m[2]);
                if ($cols_) {
                    $colStr = implode(',', $cols_) . "|$idxName";
                    $key = 'unique:' . implode(',', $cols_);
                    if (!isset($indexes[$key])) {
                        $indexes[$key] = "__index///unique///__///__///__///$colStr///__";
                    }
                }
                continue;
            }

            // INDEX name (col1, col2, ...)
            if (preg_match('/^(?:KEY|INDEX)\s+(\w+)\s*\(([^)]+)\)/si', $line, $m)) {
                $idxName = $m[1]; $cols_ = $this->parseSqlIndexCols($m[2]);
                if ($cols_) {
                    $colStr = implode(',', $cols_) . "|$idxName";
                    $key = 'index:' . implode(',', $cols_);
                    if (!isset($indexes[$key])) {
                        $indexes[$key] = "__index///index///__///__///__///$colStr///__";
                    }
                }
                continue;
            }

            // PRIMARY KEY (col) standalone
            if (preg_match('/^PRIMARY\s+KEY\s*\(([^)]+)\)/si', $line)) continue;

            // Skip ENGINE=... and similar options
            if (preg_match('/^(?:ENGINE|DEFAULT\s+CHARSET|COLLATE|ROW_FORMAT|COMMENT)\s*=/si', $line)) continue;

            // Column definition: col_name TYPE [constraints...]
            $parsed = $this->parseSqlColumn($line);
            if ($parsed === null) continue;

            $colName = $parsed['name'];

            // Virtual/generated column — track name, skip from output
            if (($parsed['type'] ?? '') === '__virtual') {
                $virtualCols[$colName] = true;
                continue;
            }

            if ($colName === 'id' || $colName === 'order_column') continue;
            if ($colName === 'uuid') continue;

            if ($colName === 'created_at') { $hasCrAt = true; continue; }
            if ($colName === 'updated_at') { $hasUpAt = true; continue; }
            if ($colName === 'deleted_at') { continue; }

            $cols[$colName] = $parsed;
        }

        // Apply FK constraints to columns
        foreach ($foreignKeys as $colName => $fk) {
            if (isset($cols[$colName])) {
                $onDelete = match($fk['onDelete']) {
                    'cascade'    => 'cascade',
                    'set null', 'null' => 'set null',
                    'no action'  => 'no action',
                    default      => 'restrict',
                };
                $cols[$colName]['mod'] = "->constrained('{$fk['table']}')->onDelete('$onDelete')";
            }
        }

        // Filter out indexes that reference virtual/generated columns
        if (!empty($virtualCols)) {
            $indexes = array_filter($indexes, function (string $row) use ($virtualCols): bool {
                $colsField = explode('///', $row)[5] ?? '';
                // Strip name suffix (col1,col2|name)
                $colsPart  = explode('|', $colsField)[0];
                foreach (explode(',', $colsPart) as $col) {
                    if (isset($virtualCols[trim($col)])) return false;
                }
                return true;
            });
        }

        if (empty($cols) && !$hasCrAt) return null;

        $header = "$tableName///__///__///";
        $entry  = [$header];

        foreach ($cols as $col) {
            $entry[] = implode('///', [
                $col['name'], $col['type'], $col['len'],
                $col['null'], $col['default'], $col['mod'], $col['comment'],
            ]);
        }

        if ($hasCrAt) $entry[] = 'created_at///timestamp///__///_NULL///NULL///__///Thời gian tạo';
        if ($hasUpAt) $entry[] = 'updated_at///timestamp///__///_NULL///NULL///__///Thời gian cập nhật';

        foreach ($indexes as $row) $entry[] = $row;

        return $entry;
    }

    private function parseSqlColumn(string $line): ?array
    {
        // Skip SQL constraint/keyword lines — use \b to avoid matching column names like 'primary_lock'
        if (preg_match('/^(?:CONSTRAINT|FOREIGN|UNIQUE|KEY|INDEX|ENGINE|COLLATE)\b/si', $line)) return null;
        if (preg_match('/^PRIMARY\s+KEY/si', $line)) return null;

        // Column name (with optional backticks)
        if (!preg_match('/^`?(\w+)`?\s+(.+)$/s', $line, $m)) return null;

        $colName    = $m[1];
        $definition = trim($m[2]);

        // Virtual/generated columns — return marker so caller can track the name
        if (preg_match('/\bAS\s*\(/si', $definition)) {
            return ['name' => $colName, 'type' => '__virtual'];
        }

        // Extract SQL type
        if (!preg_match('/^(\w+(?:\s+UNSIGNED)?)\s*(?:\(([^)]+)\))?(.*)/si', $definition, $tm)) return null;

        $sqlType  = strtoupper(trim($tm[1]));
        $typeArgs = trim($tm[2] ?? '');
        $rest     = trim($tm[3] ?? '');

        // Map SQL type → Laravel type
        [$laravelType, $len] = $this->mapSqlType($sqlType, $typeArgs);
        if ($laravelType === null) return null;

        // Nullable
        $nullable = (bool) preg_match('/\bNULL\b/i', $rest) && !preg_match('/\bNOT\s+NULL\b/i', $rest);

        // Default value
        $default = null;
        if (preg_match("/DEFAULT\s+'([^']*)'/i", $rest, $dm)) {
            $default = "'$dm[1]'";
        } elseif (preg_match('/DEFAULT\s+(\S+)/i', $rest, $dm)) {
            $val = strtoupper($dm[1]);
            if ($val === 'NULL') {
                $default = null; // handled by nullable
            } elseif ($val === 'CURRENT_TIMESTAMP') {
                $default = null; // skip default timestamps
            } else {
                $rawVal = $dm[1];
                $default = is_numeric($rawVal) ? $rawVal : "'$rawVal'";
            }
        }

        // Boolean: TINYINT(1) DEFAULT 0 → boolean false; DEFAULT 1 → true
        if ($laravelType === 'boolean' && $default !== null) {
            $default = ($default === '0' || $default === "'0'") ? 'false' : 'true';
        }

        $nullability = $nullable ? '_NULL' : 'NOT_NULL';
        $defaultVal  = ($nullable && $default === null) ? 'NULL' : ($default ?? '__');

        return [
            'name'    => $colName,
            'type'    => $laravelType,
            'len'     => $len,
            'null'    => $nullability,
            'default' => $defaultVal,
            'mod'     => '__',
            'comment' => '__',
        ];
    }

    private function mapSqlType(string $sqlType, string $args): array
    {
        // Handle UNSIGNED suffix
        $unsigned = str_contains($sqlType, 'UNSIGNED');
        $baseType = trim(str_replace('UNSIGNED', '', $sqlType));

        $map = [
            'BIGINT'      => $unsigned ? 'unsignedBigInteger'   : 'bigInteger',
            'INT'         => $unsigned ? 'unsignedInteger'      : 'integer',
            'INTEGER'     => $unsigned ? 'unsignedInteger'      : 'integer',
            'MEDIUMINT'   => $unsigned ? 'unsignedMediumInteger': 'mediumInteger',
            'SMALLINT'    => $unsigned ? 'unsignedSmallInteger' : 'smallInteger',
            'TINYINT'     => ($args === '1') ? 'boolean' : ($unsigned ? 'unsignedTinyInteger' : 'tinyInteger'),
            'VARCHAR'     => 'string',
            'CHAR'        => 'char',
            'TEXT'        => 'text',
            'MEDIUMTEXT'  => 'mediumText',
            'LONGTEXT'    => 'longText',
            'TINYTEXT'    => 'tinyText',
            'DATE'        => 'date',
            'DATETIME'    => 'dateTime',
            'TIMESTAMP'   => 'timestamp',
            'TIME'        => 'time',
            'YEAR'        => 'year',
            'DECIMAL'     => 'decimal',
            'NUMERIC'     => 'decimal',
            'FLOAT'       => 'float',
            'DOUBLE'      => 'double',
            'ENUM'        => 'enum',
            'SET'         => 'set',
            'JSON'        => 'json',
            'BLOB'        => 'binary',
            'BINARY'      => 'binary',
            'VARBINARY'   => 'binary',
            'BIT'         => 'boolean',
            'BOOL'        => 'boolean',
            'BOOLEAN'     => 'boolean',
        ];

        $laravelType = $map[$baseType] ?? null;
        if ($laravelType === null) return [null, '__'];

        // Length/params
        $len = '__';
        if (in_array($laravelType, ['string', 'char', 'binary'])) {
            $len = $args !== '' ? $args : '__';
        } elseif (in_array($laravelType, ['decimal', 'float', 'double'])) {
            $len = $args !== '' ? str_replace(' ', '', $args) : '__';
        } elseif ($laravelType === 'enum' || $laravelType === 'set') {
            preg_match_all("/'([^']*)'/", $args, $vals);
            $len = '[' . implode(',', $vals[1]) . ']';
        } elseif ($laravelType === 'boolean') {
            $len = '__'; // TINYINT(1) length not needed
        }

        return [$laravelType, $len];
    }

    private function parseSqlIndexCols(string $colStr): array
    {
        $cols = [];
        foreach (explode(',', $colStr) as $c) {
            $c = preg_replace('/\s*\(\d+\)\s*$/', '', trim($c)); // remove (prefix_length)
            $c = trim($c, '`\'" ');
            if ($c !== '') $cols[] = $c;
        }
        return $cols;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // BUILD MIGRATION ENTRY (PHP Schema::create)
    // ──────────────────────────────────────────────────────────────────────────

    private function buildMigrationEntry(string $tableName, string $body): ?array
    {
        $stmts   = $this->extractStatements($body);
        $rows    = [];
        $hasCrAt = $hasUpAt = $hasDelAt = false;

        foreach ($stmts as $stmt) {
            $parsed = $this->parseStatement($stmt);
            if ($parsed === null) continue;

            if ($parsed === ['__timestamps__'])  { $hasCrAt = $hasUpAt = true; continue; }
            if ($parsed === ['__softdeletes__']) { $hasDelAt = true; continue; }
            if (($parsed['type'] ?? '') === '__drop') continue;

            if ($parsed['type'] === '__morphs') {
                foreach ($this->expandMorphs($parsed) as $row) $rows[] = $row;
                continue;
            }

            if ($parsed['type'] === '__index') {
                $rows[] = "__index///{$parsed['index_type']}///__///__///__///{$parsed['columns']}///{$parsed['comment']}";
                continue;
            }

            $rows[] = implode('///', [
                $parsed['name'], $parsed['type'], $parsed['len'],
                $parsed['null'], $parsed['default'], $parsed['mod'], $parsed['comment'],
            ]);
        }

        if (empty($rows) && !$hasCrAt) return null;

        $entry = ["$tableName///__///__///"];
        foreach ($rows as $row) $entry[] = $row;

        if ($hasCrAt) $entry[] = 'created_at///timestamp///__///_NULL///NULL///__///Thời gian tạo';
        if ($hasUpAt) $entry[] = 'updated_at///timestamp///__///_NULL///NULL///__///Thời gian cập nhật';
        if ($hasDelAt) $entry[] = 'deleted_at///timestamp///__///_NULL///NULL///__///Thời gian xóa mềm';

        return $entry;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // EXTENSION MERGING
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Merge nhiều Schema::table blocks cho cùng 1 bảng.
     *
     * $baseCols: colName → rowIndex — cột thuộc bảng gốc (render_migration_file.json).
     * $existingExtCols: colName set — cột đã tồn tại trong extension JSON từ lần sync trước.
     *
     * Returns [colName => rowString, ...], [indexKey => rowString, ...],
     *         $baseChanges (colName → rowString — ->change() trên cột bảng gốc),
     *         $extChanges  (colName → rowString — ->change() trên cột extension đã sync trước, không ADD lại trong lượt này)
     */
    private function mergeExtensionBlocks(array $blocks, array $baseCols = [], array $existingExtCols = []): array
    {
        $mergedCols    = [];   // colName → row string
        $mergedIndexes = [];   // "type:cols" → row string
        $droppedIdxNames = []; // set of dropped index names (from dropUnique/dropIndex)
        $baseChanges   = [];   // colName → row string (change() trên cột bảng gốc)
        $extChanges    = [];   // colName → row string (change() trên cột extension đã sync trước)

        foreach ($blocks as [$body, $basename]) {
            foreach ($this->extractStatements($body) as $stmt) {
                $parsed = $this->parseStatement($stmt);
                if ($parsed === null) continue;
                if ($parsed === ['__timestamps__']) continue;
                // softDeletes() trong ALTER → thêm deleted_at vào extension JSON
                if ($parsed === ['__softdeletes__']) {
                    if (!isset($mergedCols['deleted_at'])) {
                        $mergedCols['deleted_at'] = 'deleted_at///timestamp///__///_NULL///NULL///__///Thời gian xóa mềm';
                    }
                    continue;
                }

                if (($parsed['type'] ?? '') === '__drop') {
                    foreach ($parsed['names'] as $name) unset($mergedCols[$name]);
                    continue;
                }

                // Track dropped index names — used to cancel out re-adds of existing indexes
                if (($parsed['type'] ?? '') === '__drop_index') {
                    $droppedIdxNames[$parsed['name']] = true;
                    continue;
                }

                if ($parsed['type'] === '__morphs') {
                    foreach ($this->expandMorphs($parsed) as $row) {
                        $colName = explode('///', $row)[0];
                        if (!isset($mergedCols[$colName])) $mergedCols[$colName] = $row;
                    }
                    continue;
                }

                if ($parsed['type'] === '__index') {
                    $key = $parsed['index_type'] . ':' . $parsed['columns'];
                    // Extract name from "cols|name" format
                    $idxName = str_contains($parsed['columns'], '|')
                        ? explode('|', $parsed['columns'])[1] : null;

                    // If this index was previously dropped, it's a re-add of existing → skip
                    if ($idxName && isset($droppedIdxNames[$idxName])) {
                        unset($droppedIdxNames[$idxName]); // consumed
                        continue;
                    }

                    $mergedIndexes[$key] = "__index///{$parsed['index_type']}///__///__///__///{$parsed['columns']}///{$parsed['comment']}";
                    continue;
                }

                $colName = $parsed['name'];
                $row     = implode('///', [
                    $colName, $parsed['type'], $parsed['len'],
                    $parsed['null'], $parsed['default'], $parsed['mod'], $parsed['comment'],
                ]);

                // ->change() → xác định cột này thuộc về đâu để patch đúng chỗ:
                //   1. Cột của bảng gốc (base table)   → $baseChanges, patch trực tiếp render_migration_file.json
                //   2. Cột đã ADD trong CHÍNH lượt scan này → cập nhật $mergedCols luôn (giữ hành vi cũ)
                //   3. Cột extension đã sync từ trước, không ADD lại lượt này → $extChanges
                //   4. Không rõ nguồn gốc (cột mới, có thể do file cũ bị xoá khỏi lịch sử quét)
                //      → coi như phát hiện mới, thêm vào $mergedCols (best-effort, có đủ định nghĩa từ change())
                if ($parsed['is_change'] ?? false) {
                    if (isset($baseCols[$colName])) {
                        $baseChanges[$colName] = $row;
                    } elseif (isset($mergedCols[$colName])) {
                        $mergedCols[$colName] = $row;
                    } elseif (isset($existingExtCols[$colName])) {
                        $extChanges[$colName] = $row;
                    } else {
                        $mergedCols[$colName] = $row;
                    }
                } else {
                    // Add mới: first-seen wins cho ADD (sau đó chỉ change() mới update)
                    if (!isset($mergedCols[$colName])) {
                        $mergedCols[$colName] = $row;
                    }
                }
            }
        }

        return [$mergedCols, $mergedIndexes, $baseChanges, $extChanges];
    }

    private function buildExtensionEntryFromMerged(string $tableName, array $mergedCols, array $mergedIndexes): ?array
    {
        if (empty($mergedCols) && empty($mergedIndexes)) return null;

        // Use __ as anchor — safe default (no ->after() generated)
        // Caller can manually set a real existing column if ordering matters
        $entry = ["$tableName///add///__///"];

        foreach ($mergedCols    as $row) $entry[] = $row;
        foreach ($mergedIndexes as $row) $entry[] = $row;

        return $entry;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // STATEMENT EXTRACTION
    // ──────────────────────────────────────────────────────────────────────────

    private function extractStatements(string $body): array
    {
        $stmts   = [];
        $current = '';
        $depth   = 0;

        foreach (explode("\n", $body) as $rawLine) {
            $line = trim($rawLine);
            // Strip inline // comments (e.g. "->method(); // comment")
            // Use a simple approach: find ; and strip everything after // that follows
            if (str_contains($line, ';') && preg_match('/;\s*\/\//', $line)) {
                $line = preg_replace('/\s*\/\/.*$/', '', $line);
                $line = trim($line);
            }
            if ($current === '' && ($line === '' || str_starts_with($line, '//') || str_starts_with($line, '*') || str_starts_with($line, '#'))) continue;
            if ($current === '' && !str_contains($line, '$table->')) continue;

            $current = $current === '' ? $line : $current . ' ' . $line;
            // Count only the NEW line's paren delta — not the full accumulated string
            $depth += substr_count($line, '(') - substr_count($line, ')');

            if ($depth <= 0 && str_ends_with(rtrim($current), ';')) {
                $stmts[] = rtrim($current, '; ') . ';';
                $current = ''; $depth = 0;
            }
        }

        if (trim($current) !== '') $stmts[] = rtrim($current, '; ') . ';';
        return $stmts;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // STATEMENT PARSING
    // ──────────────────────────────────────────────────────────────────────────

    private function parseStatement(string $stmt): ?array
    {
        $stmt = trim($stmt);
        if (!str_starts_with($stmt, '$table->')) return null;

        $s = rtrim(substr($stmt, 8), ';');

        if (preg_match('/^timestamps\(\)/', $s))  return ['__timestamps__'];
        if (preg_match('/^softDeletes\(\)/', $s)) return ['__softdeletes__'];
        if (preg_match('/^(id|bigIncrements|increments)\(\)/', $s)) return null;
        if (preg_match('/^(primary|foreign)\(/', $s)) return null;

        // morphs('name') → expand to name_type + name_id
        if (preg_match('/^(nullable)?[Mm]orphs\([\'"]([^\'"]+)[\'"]\)/i', $s, $m)) {
            $nullable = $m[1] !== '';
            return ['type' => '__morphs', 'name' => $m[2], 'nullable' => $nullable];
        }

        // Drop operations
        if (preg_match('/^dropColumn\(/', $s)) {
            return ['type' => '__drop', 'names' => $this->extractDropColumnNames($s)];
        }
        // Track dropped index names so we can cancel out re-adds
        if (preg_match("/^(dropUnique|dropIndex)\(['\"]([^'\"]+)['\"]\)/", $s, $m)) {
            return ['type' => '__drop_index', 'name' => $m[2]];
        }
        if (preg_match('/^(dropIndex|dropUnique|dropForeign|dropPrimary)\(/', $s)) {
            return null;
        }

        // Index/unique table-level calls
        if (preg_match('/^(index|unique|fullText|spatialIndex)\(/', $s, $m)) {
            return $this->parseIndexCall($s, $m[1]);
        }

        return $this->parseColumn($s);
    }

    /**
     * Expand morphs('name') → [name_type row, name_id row, __index row]
     */
    private function expandMorphs(array $parsed): array
    {
        $name     = $parsed['name'];
        $nullable = $parsed['nullable'] ?? false;
        $null     = $nullable ? '_NULL' : 'NOT_NULL';
        $default  = $nullable ? 'NULL' : '__';

        return [
            "$name" . "_type///string///__///$null///$default///__///__",
            "$name" . "_id///unsignedBigInteger///__///$null///$default///__///__",
            "__index///index///__///__///__///{$name}_type,{$name}_id///__",
        ];
    }

    private function extractDropColumnNames(string $s): array
    {
        // dropColumn(['a', 'b'])
        if (preg_match('/^dropColumn\(\[([^\]]+)\]\)/', $s, $m)) {
            preg_match_all('/[\'"]([^\'"]+)[\'"]/', $m[1], $cols);
            return $cols[1];
        }
        // dropColumn('col') or dropColumn("col")
        if (preg_match('/^dropColumn\([\'"]([^\'"]+)[\'"]\)/', $s, $m)) {
            return [$m[1]];
        }
        return [];
    }

    private function parseIndexCall(string $s, string $indexType): ?array
    {
        if (!preg_match('/^' . $indexType . '\((.+)\)$/', $s, $m)) return null;

        [$cols, $name] = $this->extractIndexColumnsAndName($m[1]);
        if (empty($cols)) return null;

        $method  = match ($indexType) {
            'fullText'     => 'fulltext',
            'spatialIndex' => 'spatial',
            default        => $indexType,
        };

        // Encode name into columns field: "col1,col2|idx_name"
        $colsStr = implode(',', $cols);
        if ($name !== null) $colsStr .= "|$name";

        return ['type' => '__index', 'index_type' => $method, 'columns' => $colsStr, 'comment' => '__'];
    }

    /** Returns [columns[], name|null] */
    private function extractIndexColumnsAndName(string $argsStr): array
    {
        // Skip MySQL-specific prefix indexes (DB::raw)
        if (str_contains($argsStr, 'DB::raw') || str_contains($argsStr, 'raw(')) {
            return [[], null];
        }

        $name = null;

        // Extract optional name (last string argument after array or first string)
        // e.g. ['col1', 'col2'], 'idx_name'  OR  'col', 'idx_name'
        if (preg_match('/^\s*\[([^\]]+)\]\s*,\s*[\'"]([^\'"]+)[\'"]\s*$/', $argsStr, $m)) {
            preg_match_all('/[\'"]([^\'"]+)[\'"]/', $m[1], $colMatches);
            return [$colMatches[1], $m[2]];
        }
        if (preg_match('/^\s*\[([^\]]+)\]/', $argsStr, $m)) {
            preg_match_all('/[\'"]([^\'"]+)[\'"]/', $m[1], $colMatches);
            return [$colMatches[1], null];
        }
        // Single column with optional name: 'col', 'name'
        if (preg_match("/^\\s*'([^']+)'\\s*,\\s*'([^']+)'\\s*\$/", $argsStr, $m)) {
            return [[$m[1]], $m[2]];
        }
        if (preg_match('/^\s*[\'"]([^\'"]+)[\'"]/', $argsStr, $m)) {
            return [[$m[1]], null];
        }
        return [[], null];
    }

    private function extractIndexColumns(string $argsStr): array
    {
        return $this->extractIndexColumnsAndName($argsStr)[0];
    }

    private function parseColumn(string $s): ?array
    {
        if (!preg_match('/^(\w+)\((.*)/', $s, $m)) return null;

        $type = $m[1];
        [$argsStr, $afterParen] = $this->splitFirstCall($m[2]);
        $chain = ltrim($afterParen);

        [$colName, $len] = $this->parseTypeArgs($type, $argsStr);
        if ($colName === null) return null;

        if (in_array($colName, self::AUTO_COLUMNS)) return null;
        if ($colName === 'uuid') return null;

        $isChange = $this->chainHas($chain, 'change');

        [$type, $chain] = $this->normalizeType($type, $chain);

        $nullable  = $this->chainHas($chain, 'nullable');
        $default   = $this->extractChainDefault($chain);
        $comment   = $this->extractChainComment($chain);
        $modifiers = $this->extractChainModifiers($type, $chain, $colName);

        return [
            'name'      => $colName,
            'type'      => $type,
            'len'       => $len,
            'null'      => $nullable ? '_NULL' : 'NOT_NULL',
            'default'   => ($nullable && $default === null) ? 'NULL' : ($default ?? '__'),
            'mod'       => $modifiers,
            'comment'   => $comment ?? '__',
            'is_change' => $isChange,
        ];
    }

    private function splitFirstCall(string $s): array
    {
        $depth = 1; $i = 0; $length = strlen($s);
        while ($i < $length) {
            $ch = $s[$i];
            if ($ch === '(') $depth++; elseif ($ch === ')') { $depth--; if ($depth === 0) return [substr($s, 0, $i), substr($s, $i + 1)]; }
            $i++;
        }
        return [$s, ''];
    }

    private function parseTypeArgs(string $type, string $argsStr): array
    {
        $argsStr = trim($argsStr);

        if ($type === 'foreignId') {
            if (preg_match("/^['\"]([^'\"]+)['\"]/", $argsStr, $m)) return [$m[1], '__'];
            return [null, '__'];
        }

        if (in_array($type, ['timestamps', 'softDeletes', 'id', 'bigIncrements', 'increments', 'uuid', 'ulid'])) {
            return [$type, '__'];
        }

        if (!preg_match('/^[\'"]([^\'"]+)[\'"](.*)/', $argsStr, $m)) return [null, '__'];

        $colName = $m[1];
        $rest    = trim(ltrim($m[2], ', '));
        $len     = '__';

        if ($rest !== '') {
            if (in_array($type, ['enum', 'set']) && str_starts_with($rest, '[')) {
                preg_match_all('/[\'"]([^\'"]+)[\'"]/', $rest, $vals);
                $len = '[' . implode(',', $vals[1]) . ']';
            } elseif (in_array($type, ['decimal', 'float', 'double']) && preg_match('/^(\d+)\s*,\s*(\d+)/', $rest, $dm)) {
                $len = $dm[1] . ',' . $dm[2];
            } elseif (preg_match('/^(\d+)/', $rest, $dm)) {
                $len = $dm[1];
            }
        }
        return [$colName, $len];
    }

    private function normalizeType(string $type, string $chain): array
    {
        if ($type === 'foreignId') return ['unsignedBigInteger', $chain];

        if ($this->chainHas($chain, 'unsigned') && !str_starts_with($type, 'unsigned')) {
            $map = [
                'tinyInteger' => 'unsignedTinyInteger', 'smallInteger' => 'unsignedSmallInteger',
                'mediumInteger' => 'unsignedMediumInteger', 'integer' => 'unsignedInteger',
                'bigInteger' => 'unsignedBigInteger',
            ];
            $chain = preg_replace('/->unsigned\(\)/', '', $chain);
            return [$map[$type] ?? 'unsigned' . ucfirst($type), $chain];
        }
        return [$type, $chain];
    }

    private function chainHas(string $chain, string $method): bool
    {
        return (bool) preg_match('/->\\s*' . preg_quote($method, '/') . '\\s*\\(/', $chain);
    }

    private function extractChainDefault(string $chain): ?string
    {
        // \s* handles space injected when multi-line statement is joined
        if (!preg_match('/->default\((.+?)\)\s*(?:->|\)?\s*;|$)/', $chain, $m)) return null;

        $val = trim($m[1]);
        if ((str_starts_with($val, "'") && str_ends_with($val, "'")) || (str_starts_with($val, '"') && str_ends_with($val, '"'))) return $val;
        if (in_array(strtolower($val), ['true', 'false']) || is_numeric($val)) return $val;
        return "'$val'";
    }

    private function extractChainComment(string $chain): ?string
    {
        if (!preg_match('/->comment\(\'((?:[^\'\\\\]|\\\\.)*)\'\)/', $chain, $m)) {
            if (!preg_match('/->comment\("((?:[^"\\\\]|\\\\.)*)"\)/', $chain, $m)) return null;
        }
        return $m[1];
    }

    private function extractChainModifiers(string $type, string $chain, string $colName): string
    {
        if ($type === 'unsignedBigInteger') {
            $constrained = $this->extractConstrained($chain);
            if ($constrained !== null) {
                $onDelete = $this->extractDeleteAction($chain);
                return $constrained . ($onDelete ? "->onDelete('$onDelete')" : '');
            }
        }

        if (in_array($type, ['char', 'string'])) {
            $refMod = $this->extractReferencesFk($chain);
            if ($refMod !== null) return $refMod;
        }

        $mods = [];
        if ($this->chainHas($chain, 'index')  && !$this->chainHas($chain, 'constrained')) $mods[] = '->index()';
        if ($this->chainHas($chain, 'unique') && !$this->chainHas($chain, 'constrained')) $mods[] = '->unique()';

        return empty($mods) ? '__' : implode('', $mods);
    }

    private function extractConstrained(string $chain): ?string
    {
        if (!$this->chainHas($chain, 'constrained')) return null;
        if (preg_match("/->constrained\('([^']+)'\)/", $chain, $m)) return "->constrained('{$m[1]}')";
        if (preg_match('/->constrained\(\)/', $chain)) return '->constrained()';
        return null;
    }

    private function extractDeleteAction(string $chain): ?string
    {
        if ($this->chainHas($chain, 'cascadeOnDelete'))  return 'cascade';
        if ($this->chainHas($chain, 'nullOnDelete'))     return 'set null';
        if ($this->chainHas($chain, 'restrictOnDelete')) return 'restrict';
        if ($this->chainHas($chain, 'noActionOnDelete')) return 'no action';
        if (preg_match("/->onDelete\('([^']+)'\)/", $chain, $m)) return $m[1];
        return null;
    }

    private function extractReferencesFk(string $chain): ?string
    {
        if (!preg_match("/->references\('([^']+)'\)->(?:constrained|on)\('([^']+)'\)(.*)/", $chain, $m)) return null;
        $deleteAction = $this->extractDeleteAction($m[3] ?: $chain);
        return "->references('{$m[1]}')->constrained('{$m[2]}')" . ($deleteAction ? "->onDelete('$deleteAction')" : '');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PRINT RESULTS
    // ──────────────────────────────────────────────────────────────────────────

    private function printResults(
        array $newTables,
        array $newExtensions,
        array $updatedExtensions,
        array $skipped,
        array $baseTableUpdates = [],
        array $extColumnUpdates = []
    ): void {
        if (!empty($skipped)) {
            $this->newLine();
            $this->line('<fg=gray>=== SKIPPED (dropped tables) ===</>');
            foreach (array_keys($skipped) as $s) $this->line("<fg=gray>  - $s</>");
        }

        if (!empty($newTables)) {
            $this->newLine();
            $this->info('=== TABLES MỚI → render_migration_file.json ===');
            foreach ($newTables as $t => $entry) {
                $this->line('  <fg=green>+ ' . $t . '</> (' . (count($entry) - 1) . ' rows)');
            }
        }

        if (!empty($updatedExtensions)) {
            $this->newLine();
            $this->info('=== EXTENSIONS CẬP NHẬT → render_extension_file.json ===');
            foreach ($updatedExtensions as $t => [$idx, $entry]) {
                $orig  = count($entry);
                $this->line("  <fg=yellow>↑ $t</> (thêm cols vào entry hiện có)");
            }
        }

        if (!empty($newExtensions)) {
            $this->newLine();
            $this->info('=== EXTENSIONS MỚI → render_extension_file.json ===');
            foreach ($newExtensions as $t => $entry) {
                $this->line('  <fg=green>+ ' . $t . '</> (' . (count($entry) - 1) . ' cols)');
            }
        }

        if (!empty($baseTableUpdates)) {
            $this->newLine();
            $this->info('=== CỘT BẢNG GỐC ĐỔI ĐỊNH NGHĨA (->change()) → render_migration_file.json ===');
            foreach ($baseTableUpdates as $t => $updates) {
                foreach ($updates as $upd) {
                    $this->line("  <fg=yellow>~ {$t}.{$upd['colName']}</>");
                    $this->line("    <fg=gray>- {$upd['old']}</>");
                    $this->line("    <fg=green>+ {$upd['new']}</>");
                }
            }
        }

        if (!empty($extColumnUpdates)) {
            $this->newLine();
            $this->info('=== CỘT EXTENSION ĐỔI ĐỊNH NGHĨA (->change()) → render_extension_file.json ===');
            foreach ($extColumnUpdates as $t => $cols) {
                foreach (array_keys($cols) as $colName) {
                    $this->line("  <fg=yellow>~ {$t}.{$colName}</>");
                }
            }
        }
    }
}
