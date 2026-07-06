<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gắn security headers vào mọi web response.
 *
 * Headers an toàn, không cần cấu hình thêm:
 *   X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy
 *
 * HSTS chỉ gắn khi request đến qua HTTPS (tránh vỡ local HTTP).
 *
 * CSP được điều chỉnh tự động:
 *   - Local/testing: nới rộng cho Vite HMR (localhost:*)
 *   - Production:    chỉ whitelist các domain cụ thể đã biết
 *
 * Khi muốn thêm external source mới (CDN, embed...) → cập nhật buildCsp().
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $h = $response->headers;

        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('X-Frame-Options',        'DENY');
        $h->set('Referrer-Policy',        'strict-origin-when-cross-origin');
        $h->set('Permissions-Policy',     'camera=(), microphone=(), geolocation=(), payment=(), usb=(), autoplay=()');
        $h->set('Content-Security-Policy', $this->buildCsp());

        // HSTS chỉ có nghĩa trên HTTPS — không gắn trên HTTP để tránh lock out local dev
        if ($request->isSecure()) {
            $h->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function buildCsp(): string
    {
        $dev = app()->isLocal() || app()->environment('testing');

        // script-src:
        //   unsafe-inline — inline <script> trong Blade (Toast, Alpine init)
        //   unsafe-eval   — Alpine.js v3 dùng new Function() để evaluate x-data/x-bind expressions
        $scriptSrc = implode(' ', array_filter([
            "'self'",
            "'unsafe-inline'",
            "'unsafe-eval'",
            'https://challenges.cloudflare.com',  // Turnstile widget
            'https://cdn.jsdelivr.net',            // ECharts (Survey module)
            $dev ? 'http://localhost:*' : null,    // Vite dev server JS assets
        ]));

        // style-src: unsafe-inline cần cho Tailwind utility + Alpine x-bind:style
        $styleSrc = implode(' ', [
            "'self'",
            "'unsafe-inline'",
            'https://fonts.bunny.net',       // Figtree font (module layouts)
            'https://fonts.googleapis.com',  // Inter font (Assessment passport)
        ]);

        // connect-src: ws://localhost cho Vite HMR chỉ trên dev
        $connectSrc = implode(' ', array_filter([
            "'self'",
            $dev ? 'ws://localhost:* wss://localhost:* http://localhost:*' : null,
        ]));

        return implode('; ', [
            "default-src 'self'",
            "script-src {$scriptSrc}",
            "style-src {$styleSrc}",
            "font-src 'self' https://fonts.bunny.net https://fonts.gstatic.com data:",
            "img-src 'self' data: blob: https://api.dicebear.com",
            "connect-src {$connectSrc}",
            "frame-src https://challenges.cloudflare.com",
            "frame-ancestors 'none'",
            "worker-src 'self' blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
    }
}
