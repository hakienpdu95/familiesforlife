<?php
namespace Modules\WorkflowAutomation\Data;

use Spatie\LaravelData\Data;

class ActionResult extends Data
{
    public function __construct(
        public readonly bool    $success,
        public readonly ?string $errorMessage = null,
        public readonly int     $durationMs   = 0,
        public readonly array   $meta         = [],
        // v2: output stored in RunContext under step_output_key
        public readonly ?array  $output       = null,
    ) {}

    public static function ok(int $ms = 0, array $meta = [], ?array $output = null): self
    {
        return new self(success: true, durationMs: $ms, meta: $meta, output: $output);
    }

    public static function fail(string $error, int $ms = 0): self
    {
        return new self(success: false, errorMessage: $error, durationMs: $ms);
    }

    // v2 aliases
    public static function success(?array $output = null, int $ms = 0): self
    {
        return new self(success: true, durationMs: $ms, output: $output);
    }

    public static function failure(string $error, int $ms = 0): self
    {
        return new self(success: false, errorMessage: $error, durationMs: $ms);
    }
}
