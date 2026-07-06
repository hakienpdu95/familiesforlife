<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\MigrationHelpers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class GenerateExtension extends Command
{
    use MigrationHelpers;

    protected $signature = 'extension:generate
        {--from=render_extension_file.json : JSON file tại project root}
        {--start-at=1 : Số thứ tự bắt đầu — truyền (generated_count+1) để tránh sort trước generated}
        {--force : Bỏ qua xác nhận}';

    protected $description = 'Generate ALTER TABLE migrations từ JSON vào migrations/extensions/';

    private const DIR_EXTENSIONS = 'extensions';

    // ──────────────────────────────────────────────────────────────

    public function handle(): int
    {
        // 1. Đọc JSON
        $jsonPath = base_path($this->option('from'));

        if (!File::exists($jsonPath)) {
            $this->warn("File không tồn tại: $jsonPath — bỏ qua extension generation.");
            return self::SUCCESS; // Không phải lỗi — file có thể chưa có
        }

        $raw  = File::get($jsonPath);
        $json = json_decode($this->sanitizeJson($raw), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('JSON không hợp lệ: ' . json_last_error_msg());
            $this->line('<fg=yellow>Gợi ý: kiểm tra trailing comma (dấu phẩy thừa) sau phần tử cuối array</>');
            return self::FAILURE;
        }

        if (empty($json)) {
            $this->warn('render_extension_file.json rỗng — không có gì để generate.');
            return self::SUCCESS;
        }

        // 2. Validate
        if (!$this->validateSchema($json)) {
            return self::FAILURE;
        }

        // 3. Xóa + tạo lại extensions/
        $extensionsPath = database_path('migrations/' . self::DIR_EXTENSIONS);
        $deleted        = $this->cleanDir($extensionsPath);
        $this->line("<fg=yellow>Đã xóa $deleted file cũ trong extensions/</>");

        // 4. Load template ALTER
        $templatePath = database_path('templates/alter_table.php');
        $template     = File::exists($templatePath)
            ? File::get($templatePath)
            : $this->defaultTemplate();

        // 5. Generate
        $timestamp = Carbon::now();
        $startAt   = max(1, (int) $this->option('start-at'));
        $count     = 0;

        foreach ($json as $blockData) {
            $count++;
            $header      = explode('///', array_shift($blockData));
            $tableName   = trim($header[0]);
            $action      = strtolower(trim($header[1] ?? 'add'));
            $afterColumn = trim($header[2] ?? '__'); // __ = không dùng after()
            $blockDesc   = trim($header[3] ?? '');

            // Class name ví dụ: AddDeptAndOrgIdToUsersTable
            $colNames  = $this->extractColumnNames($blockData);
            $colSlug   = Str::studly(implode('_and_', array_slice($colNames, 0, 3)));
            $className = Str::studly($action) . $colSlug . 'To' . Str::studly($tableName) . 'Table';

            // Sinh up() body
            [$upLines, $downLines] = $this->buildBody($action, $tableName, $blockData, $afterColumn);

            $fileName = sprintf(
                '%s_%06d_%s_%s_to_%s_table.php',
                $timestamp->format('Y_m_d_His'),
                $startAt + $count - 1,
                $action,
                Str::snake(implode('_and_', array_slice($colNames, 0, 3))),
                $tableName
            );

            $content = strtr($template, [
                '__CLASS_NAME__' => $className,
                '__TABLE_NAME__' => $tableName,
                '__ACTION__'     => strtoupper($action),
                '__COMMENT__'    => $blockDesc,
                '__UP_BODY__'    => implode("\n            ", $upLines),
                '__DOWN_BODY__'  => implode("\n            ", $downLines),
            ]);

            File::put("$extensionsPath/$fileName", $content);
            $this->line("<fg=green>  ✓ extensions/$fileName</>");
        }

        $this->info("\nĐã tạo $count extension migration(s).");

        Log::info('extension:generate', [
            'actor'   => auth()->user()?->email ?? 'console',
            'count'   => $count,
            'deleted' => $deleted,
        ]);

        return self::SUCCESS;
    }

    // ──────────────────────────────────────────────────────────────
    // BUILD BODY — sinh up() và down()
    // ──────────────────────────────────────────────────────────────

    /**
     * @return array{string[], string[]}  [upLines, downLines]
     */
    private function buildBody(
        string $action,
        string $tableName,
        array  $rows,
        string $firstAfter
    ): array {
        return match ($action) {
            'add'    => $this->buildAddBody($rows, $firstAfter, $tableName),
            'drop'   => $this->buildDropBody($rows, $tableName),
            'change' => $this->buildChangeBody($rows),
            default  => [['// TODO'], ['// TODO']],
        };
    }

    // ── ADD ────────────────────────────────────────────────────────

    private function buildAddBody(array $rows, string $firstAfter, string $tableName): array
    {
        $upLines     = [];
        $fkCols      = [];
        $prevColName = $firstAfter !== '__' ? $firstAfter : null;

        foreach ($rows as $row) {
            $p = explode('///', $row);
            while (count($p) < 7) $p[] = '__';
            [$colName, $colType, $colLen, $colNull, $colDefault, $colMod, $colComment] = $p;

            if (in_array($colName, self::SPECIAL_DIRECTIVES)) {
                $this->buildIdempotentIndexDirective($colMod, $colType, $tableName, $upLines);
                continue;
            }

            $afterClause = $prevColName ? "->after('$prevColName')" : '';
            $prevColName = $colName;

            // Wrap mỗi cột trong hasColumn check để migration idempotent khi chạy lại
            // FK columns (buildCustomFk) trả về multi-line string → cần split + re-indent
            $colDef    = $this->buildColumnDef(
                $colName, $colType, $colLen,
                $colNull, $colDefault, $colMod, $colComment,
                $afterClause
            );
            $colDefLines = array_filter(array_map('trim', preg_split('/\n/', $colDef)), fn($l) => $l !== '');
            $upLines[] = "if (!Schema::hasColumn('$tableName', '$colName')) {";
            foreach ($colDefLines as $defLine) {
                $upLines[] = "    $defLine";
            }
            $upLines[] = "}";

            if ($this->isForeignKey($colType, $colMod)) {
                $fkCols[] = $colName;
            }
        }

        // Down: xóa FK trước, rồi xóa cột (chỉ khi cột tồn tại)
        $allNames = array_values(array_filter(
            array_map(fn($r) => trim(explode('///', $r)[0]), $rows),
            fn($n) => !in_array($n, self::SPECIAL_DIRECTIVES)
        ));
        $existingCols = implode(', ', array_map(fn($n) => "'$n'", $allNames));
        $downLines = [];
        foreach ($fkCols as $fkCol) {
            $downLines[] = "if (Schema::hasColumn('$tableName', '$fkCol')) \$table->dropForeign(['$fkCol']);";
        }
        $downLines[] = "\$cols = array_filter([$existingCols], fn(\$c) => Schema::hasColumn('$tableName', \$c));";
        $downLines[] = "if (!empty(\$cols)) \$table->dropColumn(array_values(\$cols));";

        return [$upLines, $downLines];
    }

    // ── IDEMPOTENT INDEX ──────────────────────────────────────────
    // Giống buildIndexDirective nhưng wrap mỗi index trong Schema::hasIndex check
    // để tránh "Duplicate key name" khi migration chạy lại trên DB đã có index.

    private function buildIdempotentIndexDirective(string $colMod, string $indexType, string $tableName, array &$lines): void
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

            $name = null;
            if (str_contains($entry, '|')) {
                [$entry, $name] = explode('|', $entry, 2);
                $entry = trim($entry);
                $name  = trim($name);
            }

            $colList = array_map('trim', explode(',', $entry));
            $cols    = array_map(fn($c) => "'" . $c . "'", $colList);
            $nameArg = $name ? ", '$name'" : '';
            $line    = count($cols) > 1
                ? "\$table->$method([" . implode(', ', $cols) . "]$nameArg);"
                : "\$table->$method({$cols[0]}$nameArg);";

            // Tính tên index: custom name ưu tiên, fallback về tên Laravel auto-generate
            $idxName = $name ?? ($tableName . '_' . implode('_', $colList) . '_' . strtolower($method));

            $lines[] = "if (!Schema::hasIndex('$tableName', '$idxName')) {";
            $lines[] = "    $line";
            $lines[] = "}";
        }
    }

    // ── DROP INDEX (dùng trong block action=drop) ───────────────────
    // Directive: "__index///<unique|index|fulltext|spatial>///__///__///__///<name1;name2>///comment"
    // colMod chứa danh sách tên index cần xoá (phân cách ';'), KHÔNG phải danh sách cột —
    // vì dropUnique()/dropIndex() chỉ cần tên index đã tồn tại.

    private function buildDropIndexDirective(string $colMod, string $indexType, string $tableName, array &$lines): void
    {
        $method = match (strtolower($indexType)) {
            'fulltext' => 'dropFullText',
            'unique'   => 'dropUnique',
            'spatial'  => 'dropSpatialIndex',
            default    => 'dropIndex',
        };

        foreach (explode(';', $colMod) as $name) {
            $name = trim($name);
            if ($name === '' || $name === '__') continue;

            $lines[] = "if (Schema::hasIndex('$tableName', '$name')) {";
            $lines[] = "    \$table->$method('$name');";
            $lines[] = "}";
        }
    }

    // ── DROP ───────────────────────────────────────────────────────

    private function buildDropBody(array $rows, string $tableName): array
    {
        $upLines    = [];
        $downLines  = [];
        $fkCols     = [];
        $allNames   = [];
        $indexLines = [];

        foreach ($rows as $row) {
            $p = explode('///', $row);
            while (count($p) < 7) $p[] = '__';
            [$colName, $colType, , , , $colMod] = $p;

            // __index trong block drop = xoá index/unique theo tên (không phải cột thật)
            if ($colName === '__index') {
                $this->buildDropIndexDirective($colMod, $colType, $tableName, $indexLines);
                continue;
            }

            // Skip special directives — không phải cột thật
            if (in_array($colName, self::SPECIAL_DIRECTIVES)) continue;

            $allNames[] = $colName;
            if ($this->isForeignKey($colType, $colMod)) {
                $fkCols[] = $colName;
            }
        }

        // Up: drop FK trước (MySQL không cho xoá index đang backing một FK — lỗi 1553),
        // rồi drop index/unique (nếu 2+ cột sắp xoá cùng thuộc 1 composite key, drop cột
        // trực tiếp mà không dọn index sẽ gây lỗi 1072 "Key column ... doesn't exist" vì
        // key bị chỉnh sửa dở dang ngay trong cùng câu ALTER TABLE), rồi mới drop cột.
        foreach ($fkCols as $fk) {
            $upLines[] = "if (Schema::hasColumn('$tableName', '$fk')) \$table->dropForeign(['$fk']);";
        }
        foreach ($indexLines as $line) {
            $upLines[] = $line;
        }
        $namesArg  = implode(', ', array_map(fn($n) => "'$n'", $allNames));
        $upLines[] = "\$cols = array_filter([$namesArg], fn(\$c) => Schema::hasColumn('$tableName', \$c));";
        $upLines[] = "if (!empty(\$cols)) \$table->dropColumn(array_values(\$cols));";

        // Down: add lại (skeleton — cần điền type)
        foreach ($rows as $row) {
            $p       = explode('///', $row);
            $colName = trim($p[0]);
            if (in_array($colName, self::SPECIAL_DIRECTIVES)) continue;
            $colType    = trim($p[1] ?? 'string');
            $downLines[] = "// TODO: \$table->$colType('$colName')->...; // add lại '$colName'";
        }

        return [$upLines, $downLines];
    }

    // ── CHANGE ─────────────────────────────────────────────────────

    private function buildChangeBody(array $rows): array
    {
        $upLines   = [];
        $downLines = [];

        foreach ($rows as $row) {
            $p = explode('///', $row);
            while (count($p) < 7) $p[] = '__';
            [$colName, $colType, $colLen, $colNull, $colDefault, $colMod, $colComment] = $p;

            if (in_array($colName, self::SPECIAL_DIRECTIVES)) continue;

            $def = $this->buildColumnDef(
                $colName, $colType, $colLen,
                $colNull, $colDefault, $colMod, $colComment,
                '' // không dùng after() khi change
            );

            // Thêm ->change() trước dấu ;
            $upLines[]   = rtrim($def, ';') . '->change();';
            $downLines[] = "// TODO: rollback change cho '$colName'";
        }

        return [$upLines, $downLines];
    }

    // ──────────────────────────────────────────────────────────────
    // BUILD COLUMN DEF
    // ──────────────────────────────────────────────────────────────

    private function buildColumnDef(
        string $colName, string $colType,
        string $colLen,  string $colNull,
        string $colDefault, string $colMod, string $colComment,
        string $afterClause
    ): string {
        $nullable = $colNull === '_NULL' || $colNull === 'NULL';
        $noLen    = in_array($colType, self::NO_LENGTH_TYPES);

        // foreignId shorthand
        if ($colType === 'unsignedBigInteger'
            && $colMod !== '__'
            && str_contains($colMod, 'constrained')
            && !str_contains($colMod, 'references(')
        ) {
            $def = "\$table->foreignId('$colName')";
            if ($nullable)       $def .= '->nullable()';
            $def .= $this->normalizeOnDelete($colMod);
            $def .= $afterClause;
            if ($colComment !== '__') $def .= "->comment('" . addslashes($colComment) . "')";
            return $def . ';';
        }

        // Custom FK: char/string/uuid + references()
        if (in_array($colType, ['char', 'string', 'uuid'])
            && $colMod !== '__'
            && str_contains($colMod, 'references(')
        ) {
            preg_match("/->references\('([^']+)'\)->constrained\('([^']+)'\)(.*)/", $colMod, $m);
            if (isset($m[1], $m[2])) {
                $params = (!$noLen && $colLen !== '__') ? ', ' . $this->formatParams($colType, $colLen) : '';
                $col    = "\$table->$colType('$colName'$params)";
                if ($nullable)            $col .= '->nullable()';
                if ($colDefault !== '__') $col .= $this->formatDefault($colType, $colDefault);
                $col .= $afterClause;
                if ($colComment !== '__') $col .= "->comment('" . addslashes($colComment) . "')";
                $col .= ';';
                $extra = $this->normalizeOnDelete(trim($m[3] ?? ''));
                $fk    = "\$table->foreign('$colName')->references('{$m[1]}')->on('{$m[2]}')$extra;";
                return $col . "\n            " . $fk;
            }
        }

        // Normal column
        $params = '';
        if (!$noLen && $colLen !== '__' && $colLen !== '') {
            $params = $this->formatParams($colType, $colLen);
        }

        $def = in_array($colType, ['enum', 'set'])
            ? "\$table->$colType('$colName', $params)"
            : ($params !== ''
                ? "\$table->$colType('$colName', $params)"
                : "\$table->$colType('$colName')");

        if ($nullable)            $def .= '->nullable()';
        if ($colDefault !== '__') $def .= $this->formatDefault($colType, $colDefault);

        // timestamp NOT NULL không có default → MySQL strict mode từ chối (SQLSTATE 1067)
        if ($colType === 'timestamp' && !$nullable && $colDefault === '__') {
            $def .= '->useCurrent()';
        }

        if ($colMod !== '__'
            && !str_contains($colMod, 'constrained')
            && !str_contains($colMod, 'references(')
        ) {
            $def .= $colMod;
        }

        $def .= $afterClause;

        if ($colComment !== '__') {
            $def .= "->comment('" . addslashes($colComment) . "')";
        }

        return $def . ';';
    }

    // ──────────────────────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────────────────────

    private function isForeignKey(string $type, string $mod): bool
    {
        return ($type === 'unsignedBigInteger' && str_contains($mod, 'constrained'))
            || (in_array($type, ['char', 'string', 'uuid']) && str_contains($mod, 'references('));
    }

    /**
     * Lấy danh sách tên cột thật (bỏ qua special directives).
     */
    private function extractColumnNames(array $rows): array
    {
        return array_values(array_filter(
            array_map(fn($r) => trim(explode('///', $r)[0]), $rows),
            fn($n) => !in_array($n, self::SPECIAL_DIRECTIVES)
        ));
    }

    // ──────────────────────────────────────────────────────────────
    // VALIDATE
    // ──────────────────────────────────────────────────────────────

    private function validateSchema(array $json): bool
    {
        $validActions = ['add', 'drop', 'change'];

        foreach ($json as $i => $block) {
            if (!is_array($block) || empty($block)) {
                $this->error("Block[$i]: rỗng."); return false;
            }
            $header = explode('///', $block[0]);
            $table  = trim($header[0] ?? '');
            $action = strtolower(trim($header[1] ?? ''));

            if (!$table) {
                $this->error("Block[$i]: thiếu tên bảng."); return false;
            }
            if (!in_array($action, $validActions)) {
                $this->error("Block[$i] ($table): action '$action' không hợp lệ. Dùng: " . implode(', ', $validActions));
                return false;
            }

            foreach (array_slice($block, 1) as $j => $field) {
                $p = explode('///', $field);
                if (count($p) < 7) {
                    $this->error("$table[$j]: cần 7 phần, nhận " . count($p) . " → '$field'");
                    return false;
                }

                if (in_array($p[0], self::SPECIAL_DIRECTIVES)) continue;

                // drop action: không cần validate type
                if ($action === 'drop') continue;

                if (!in_array($p[1], self::VALID_TYPES)) {
                    $this->error("$table.{$p[0]}: type không hợp lệ '{$p[1]}'");
                    return false;
                }
            }
        }
        return true;
    }

    // ──────────────────────────────────────────────────────────────
    // DEFAULT TEMPLATE (dùng khi không có file templates/alter_table.php)
    // ──────────────────────────────────────────────────────────────

    private function defaultTemplate(): string
    {
        return <<<'PHP'
        <?php

        use Illuminate\Database\Migrations\Migration;
        use Illuminate\Database\Schema\Blueprint;
        use Illuminate\Support\Facades\Schema;

        /**
         * __ACTION__: __TABLE_NAME__
         * __COMMENT__
         */
        return new class extends Migration {
            public function up(): void
            {
                Schema::table('__TABLE_NAME__', function (Blueprint $table) {
                    __UP_BODY__
                });
            }

            public function down(): void
            {
                Schema::table('__TABLE_NAME__', function (Blueprint $table) {
                    __DOWN_BODY__
                });
            }
        };
        PHP;
    }
}
