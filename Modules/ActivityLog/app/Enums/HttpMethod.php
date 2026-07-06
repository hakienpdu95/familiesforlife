<?php

namespace Modules\ActivityLog\Enums;

enum HttpMethod: int
{
    case GET     = 1;
    case POST    = 2;
    case PUT     = 3;
    case PATCH   = 4;
    case DELETE  = 5;
    case HEAD    = 6;
    case OPTIONS = 7;

    public static function fromString(string $m): self
    {
        return match(strtoupper($m)) {
            'GET'    => self::GET,
            'POST'   => self::POST,
            'PUT'    => self::PUT,
            'PATCH'  => self::PATCH,
            'DELETE' => self::DELETE,
            'HEAD'   => self::HEAD,
            default  => self::OPTIONS,
        };
    }
}
