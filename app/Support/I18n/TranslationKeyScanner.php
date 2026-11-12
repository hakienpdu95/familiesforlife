<?php

namespace App\Support\I18n;

use Illuminate\Support\Facades\File;
use ReflectionClassConstant;
use Symfony\Component\Finder\SplFileInfo;

class TranslationKeyScanner
{
    private const PHP_PATTERN = '/(?<![\w>$:])(?:__|trans|trans_choice|Lang::get|@lang|@choice)\(\s*(?:\'((?:\\\\.|[^\'\\\\])*)\'|"((?:\\\\.|[^"\\\\$])*)")\s*[,)]/u';

    private const JS_PATTERN = '/(?<![\w$.])t\(\s*(?:\'((?:\\\\.|[^\'\\\\])*)\'|"((?:\\\\.|[^"\\\\])*)")\s*[,)]/u';

    /** @return array<string, list<string>> */
    public function phpKeys(): array
    {
        $keys = [];
        foreach ($this->files(config('i18n.scan_paths'), fn (string $name) => str_ends_with($name, '.php')) as $file) {
            foreach ($this->extract(self::PHP_PATTERN, $file->getContents()) as $key) {
                $keys[$key][] = $file->getRelativePathname();
            }
        }

        foreach ($this->constantKeys() as $key => $source) {
            $keys[$key][] = $source;
        }

        foreach ($this->configKeys() as $key => $source) {
            $keys[$key][] = $source;
        }

        return $keys;
    }

    /** @return array<string, list<string>> */
    public function jsKeys(): array
    {
        $keys = [];
        foreach ($this->files(config('i18n.js_paths'), fn (string $name) => preg_match('/\.(js|mjs|ts|vue)$/', $name) === 1) as $file) {
            foreach ($this->extract(self::JS_PATTERN, $file->getContents()) as $key) {
                $keys[$key][] = $file->getRelativePathname();
            }
        }

        return $keys;
    }

    /** @return array<string, string> */
    public function constantKeys(): array
    {
        $keys = [];
        foreach (config('i18n.translatable_constants', []) as $ref) {
            [$constant, $paths] = array_pad(explode('|', $ref, 2), 2, null);
            [$class, $name] = explode('::', $constant, 2);
            if (! class_exists($class) || ! (new \ReflectionClass($class))->hasConstant($name)) {
                continue;
            }
            $value = (new ReflectionClassConstant($class, $name))->getValue();
            if ($paths !== null) {
                $value = array_map(fn (string $path) => data_get($value, trim($path)), explode(',', $paths));
            }
            array_walk_recursive($value, function ($item) use (&$keys, $ref) {
                if (is_string($item) && self::isSourceText($item)) {
                    $keys[$item] = $ref;
                }
            });
        }

        return $keys;
    }

    /** @return array<string, string> */
    public function configKeys(): array
    {
        $keys = [];
        foreach (config('i18n.translatable_config', []) as $path) {
            $value = data_get(config()->all(), $path);
            $value = is_array($value) ? $value : [$value];
            array_walk_recursive($value, function ($item) use (&$keys, $path) {
                if (is_string($item) && self::isSourceText($item)) {
                    $keys[$item] = 'config:'.$path;
                }
            });
        }

        return $keys;
    }

    public static function isSourceText(string $value): bool
    {
        return preg_match('/[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/iu', $value) === 1;
    }

    public static function isContextKey(string $key): bool
    {
        return preg_match('/\S_[A-Z][A-Za-z]+$/u', $key) === 1 && self::isSourceText($key);
    }

    public static function stripContext(string $key): string
    {
        return preg_replace('/_[A-Z][A-Za-z]+$/u', '', $key);
    }

    public static function isGroupKey(string $key): bool
    {
        return preg_match('/^[\w\-]+(::[\w\-]+)?(\.[\w\-]+)+$/', $key) === 1;
    }

    /** @return list<string> */
    private function extract(string $pattern, string $contents): array
    {
        preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER);

        $keys = [];
        foreach ($matches as $m) {
            $single = $m[1] ?? '';
            $key = $single !== '' ? str_replace(["\\'", '\\\\'], ["'", '\\'], $single) : stripcslashes($m[2] ?? '');
            if ($key === '' || self::isGroupKey($key)) {
                continue;
            }
            $keys[] = $key;
        }

        return $keys;
    }

    /** @return iterable<SplFileInfo> */
    private function files(array $paths, callable $accept): iterable
    {
        $exclude = config('i18n.exclude', []);
        foreach ($paths as $path) {
            $root = base_path($path);
            if (! is_dir($root)) {
                continue;
            }
            foreach (File::allFiles($root) as $file) {
                $relative = $path.'/'.str_replace('\\', '/', $file->getRelativePathname());
                foreach ($exclude as $ex) {
                    if (str_starts_with($relative, $ex.'/') || str_contains($relative, '/'.$ex.'/')) {
                        continue 2;
                    }
                }
                if ($accept($file->getFilename())) {
                    yield new SplFileInfo($file->getPathname(), dirname($relative), $relative);
                }
            }
        }
    }
}
