<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Exceptions;

/** Lỗi khi fetch URL thất bại (mạng, HTTP status, content-type, hoặc bị chặn SSRF) — luôn map thành extraction_confidence=low ở Controller, không lộ raw exception ra JSON. */
class UrlFetchException extends \RuntimeException {}
