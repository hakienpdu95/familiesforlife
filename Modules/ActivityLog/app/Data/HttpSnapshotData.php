<?php

namespace Modules\ActivityLog\Data;

use Modules\ActivityLog\Enums\HttpMethod;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;

class HttpSnapshotData extends Data
{
    public function __construct(
        public readonly HttpMethod $method,
        #[Max(2000)]
        public readonly string    $url,
        public readonly ?string   $routeName,
        public readonly ?int      $statusCode,
        public readonly ?int      $durationMs,
        public readonly ?string   $userAgent,
    ) {}

    public static function fromRequest(\Illuminate\Http\Request $req): self
    {
        return new self(
            method:     HttpMethod::fromString($req->method()),
            url:        substr($req->fullUrl(), 0, 2000),
            routeName:  $req->route()?->getName(),
            statusCode: null,
            durationMs: null,
            userAgent:  $req->userAgent() ? substr($req->userAgent(), 0, 500) : null,
        );
    }
}
