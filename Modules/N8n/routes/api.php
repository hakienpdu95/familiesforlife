<?php

use Illuminate\Support\Facades\Route;
use Modules\N8n\Features\InboundWebhook\Http\N8nInboundWebhookController;

// spec/N8n_Integration_Technical_Specification.md §5.1 — 1 endpoint nhận webhook DUY NHẤT cho
// MỌI mục đích, không sinh route mới theo từng use case. KHÔNG áp middleware 'auth'/'web'
// (CSRF) — n8n là server-to-server, không có session/cookie nào để mang theo. Bảo mật hoàn
// toàn dựa vào token định tuyến (§2.2) + HMAC (§5.3).
Route::post('n8n/in/{token}', [N8nInboundWebhookController::class, 'handle'])
    ->middleware(['throttle:n8n-inbound'])
    ->name('n8n.inbound');
