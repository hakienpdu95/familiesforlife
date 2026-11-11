<?php

namespace App\Console\Commands;

use App\Support\I18n\JsTranslations;
use App\Support\I18n\TranslationKeyScanner;
use Illuminate\Console\Command;

class I18nMissingCommand extends Command
{
    protected $signature = 'i18n:missing
        {--write : Thêm key còn thiếu vào lang/{locale}.json (giá trị tạm = câu gốc tiếng Việt)}
        {--locale=* : Chỉ kiểm tra các locale này (mặc định config i18n.target_locales)}
        {--untranslated : Liệt kê key có giá trị vẫn là tiếng Việt}
        {--unused : Liệt kê key tiếng Việt trong file JSON không còn được dùng}
        {--prune : Xoá key tiếng Việt không còn được dùng khỏi file JSON}';

    protected $description = 'Quét __()/trans()/@lang/t() và hằng số khai báo trong config/i18n.php, đối chiếu với lang/{locale}.json';

    public function handle(TranslationKeyScanner $scanner): int
    {
        $keys = $scanner->phpKeys();
        foreach ($scanner->jsKeys() as $key => $files) {
            $keys[$key] = array_merge($keys[$key] ?? [], $files);
        }
        $keys = array_filter($keys, fn ($files, $key) => TranslationKeyScanner::isSourceText((string) $key), ARRAY_FILTER_USE_BOTH);
        ksort($keys, SORT_STRING);

        $this->line(sprintf('Đã quét <info>%d</info> key nguồn (tiếng Việt).', count($keys)));

        $locales = $this->option('locale') ?: config('i18n.target_locales', ['en']);
        $exit = self::SUCCESS;

        foreach ($locales as $locale) {
            $path = lang_path($locale.'.json');
            $dictionary = is_file($path) ? json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR) : [];

            $missing = array_diff_key($keys, $dictionary);
            $untranslated = array_filter($dictionary, fn ($value, $key) => TranslationKeyScanner::isSourceText((string) $key) && $value === $key, ARRAY_FILTER_USE_BOTH);
            $unused = array_filter(array_diff_key($dictionary, $keys), fn ($value, $key) => TranslationKeyScanner::isSourceText((string) $key), ARRAY_FILTER_USE_BOTH);

            $this->newLine();
            $this->line("<comment>[{$locale}]</comment> {$path}");
            $this->line(sprintf('  Thiếu: <fg=%s>%d</>  ·  Chưa dịch: %d  ·  Không còn dùng: %d', $missing ? 'red' : 'green', count($missing), count($untranslated) + ($this->option('write') ? count($missing) : 0), count($unused)));

            if ($missing && ! $this->option('write')) {
                foreach (array_slice($missing, 0, 50, true) as $key => $files) {
                    $this->line('  - '.$key.'  <fg=gray>('.$files[0].')</>');
                }
                if (count($missing) > 50) {
                    $this->line('  … và '.(count($missing) - 50).' key khác.');
                }
                $exit = self::FAILURE;
            }

            if ($this->option('untranslated')) {
                foreach (array_keys($untranslated) as $key) {
                    $this->line('  ~ '.$key);
                }
            }

            if ($this->option('unused')) {
                foreach (array_keys($unused) as $key) {
                    $this->line('  x '.$key);
                }
            }

            $changed = false;
            if ($missing && $this->option('write')) {
                foreach (array_keys($missing) as $key) {
                    $dictionary[$key] = $key;
                }
                $changed = true;
                $this->info(sprintf('  Đã thêm %d key vào %s.', count($missing), basename($path)));
            }

            if ($unused && $this->option('prune')) {
                $dictionary = array_diff_key($dictionary, $unused);
                $changed = true;
                $this->info(sprintf('  Đã xoá %d key không còn dùng khỏi %s.', count($unused), basename($path)));
            }

            if ($changed) {
                file_put_contents($path, json_encode($dictionary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);
            }
        }

        JsTranslations::flush();

        return $exit;
    }
}
