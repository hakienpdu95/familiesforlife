{{-- spec/N8n_Integration_Technical_Specification.md §3.2/§8 — KHÔNG có UI hiển thị lại secret
     cũ dưới mọi hình thức. Chỉ hiện placeholder che (Model::maskSecret()) + nút "Xoay" riêng
     từng field, gọi AJAX tới route rotate — kết quả plaintext hiện ngay bên dưới, đúng 1 lần,
     mất khi rời trang. --}}
<div class="card bg-base-100 shadow-sm border border-base-200" x-data="n8nSecretsPanel({{ Js::from([
    'rotateUrl' => route('backend.n8n.connections.rotate', $connection),
]) }})">
    <div class="card-body">
        <h2 class="card-title text-base mb-1">
            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 10-8 0v4h8z"/></svg>
            Token &amp; Secret
        </h2>
        <p class="text-xs text-base-content/50 mb-4">Giá trị đầy đủ chỉ hiển thị đúng 1 lần ngay sau khi tạo/xoay — sau đó luôn bị che.</p>

        <div class="space-y-3">

            {{-- Inbound token = URL webhook --}}
            <div class="rounded-lg border border-base-200 p-3">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <p class="text-sm font-medium">Webhook URL (inbound token)</p>
                        <p class="font-mono text-xs text-base-content/60 mt-0.5" x-text="reveal.inbound_token ? fullInboundUrl(reveal.inbound_token) : '{{ url('api/n8n/in/' . $connection->maskedInboundToken()) }}'"></p>
                    </div>
                    <button type="button" class="btn btn-xs btn-outline shrink-0" @click="confirmRotate('rotate_inbound_token', 'Xoay token sẽ ĐỔI URL nhận webhook — URL cũ ngừng nhận request ngay lập tức, phải cập nhật lại URL mới trong n8n.')">Xoay token</button>
                </div>
                <p class="text-xs text-error mt-1.5" x-show="reveal.inbound_token">⚠ Đã đổi URL — cập nhật ngay trong n8n, sao chép trước khi rời trang.</p>
                <button type="button" class="btn btn-2xs btn-ghost mt-1" x-show="reveal.inbound_token" @click="copy(fullInboundUrl(reveal.inbound_token))">Copy URL</button>
            </div>

            {{-- Inbound secret --}}
            <div class="rounded-lg border border-base-200 p-3">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <p class="text-sm font-medium">Inbound secret (ký HMAC chiều nhận)</p>
                        <p class="font-mono text-xs text-base-content/60 mt-0.5" x-text="reveal.inbound_secret || '{{ $connection->maskedInboundSecret() ?? 'Chưa cấu hình' }}'"></p>
                    </div>
                    <button type="button" class="btn btn-xs btn-outline shrink-0" @click="confirmRotate('rotate_inbound_secret', 'Xoay inbound secret — n8n phải cập nhật lại chữ ký gửi kèm request, giá trị cũ mất hiệu lực ngay.')">Xoay secret</button>
                </div>
                <button type="button" class="btn btn-2xs btn-ghost mt-1" x-show="reveal.inbound_secret" @click="copy(reveal.inbound_secret)">Copy secret</button>
            </div>

            {{-- Outbound secret --}}
            <div class="rounded-lg border border-base-200 p-3">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <p class="text-sm font-medium">
                            Outbound secret (ký HMAC chiều gọi ra)
                            @if($connection->sendsUnsignedOutbound())
                                <span class="badge badge-warning badge-xs align-middle">chưa ký</span>
                            @endif
                        </p>
                        <p class="font-mono text-xs text-base-content/60 mt-0.5" x-text="reveal.outbound_secret || '{{ $connection->maskedOutboundSecret() ?? 'Chưa cấu hình — gửi không ký' }}'"></p>
                    </div>
                    <button type="button" class="btn btn-xs btn-outline shrink-0" @click="confirmRotate('rotate_outbound_secret', 'Xoay outbound secret — n8n phía nhận phải cập nhật lại HMAC kỳ vọng, giá trị cũ mất hiệu lực ngay.')">Xoay secret</button>
                </div>
                <button type="button" class="btn btn-2xs btn-ghost mt-1" x-show="reveal.outbound_secret" @click="copy(reveal.outbound_secret)">Copy secret</button>
            </div>

        </div>
    </div>
</div>
