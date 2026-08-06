{{-- Dùng chung create/edit — spec/N8n_Integration_Technical_Specification.md §7.4.
     $connection = null ở create. KHÔNG có field nào để tự nhập inbound_token/inbound_secret/
     outbound_secret (§3.2) — 3 giá trị này CHỈ sinh qua nút hành động, hiển thị plaintext 1
     LẦN DUY NHẤT (edit.blade.php xử lý phần "Xoay"/reveal riêng). --}}
<div class="grid grid-cols-1 xl:grid-cols-[1fr_320px] gap-6 items-start">

    {{-- ── Card chính ──────────────────────────────────────────────── --}}
    <div class="space-y-5">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">

                <h2 class="card-title text-base mb-5">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Thông tin kết nối
                </h2>

                <div class="space-y-4">

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tên kết nối <span class="text-error">*</span></span>
                            <span class="label-text-alt text-xs text-base-content/40">Định danh vĩnh viễn, không tái sử dụng được sau khi xoá</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $connection?->name) }}"
                               class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                               maxlength="150" placeholder="VD: n8n-crm-notify" required
                               {{ $connection ? '' : '' }}>
                        @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Ghi chú mục đích</span>
                        </label>
                        <textarea name="purpose_note" rows="2" maxlength="500"
                                  class="textarea textarea-bordered textarea-sm w-full @error('purpose_note') textarea-error @enderror"
                                  placeholder="VD: Báo lead mới sang Zalo OA qua n8n"
                        >{{ old('purpose_note', $connection?->purpose_note) }}</textarea>
                        @error('purpose_note')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="divider my-1">Chiều nhận (n8n &rarr; app)</div>

                    <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                        <input type="hidden" name="inbound_enabled" value="0">
                        <input type="checkbox" name="inbound_enabled" value="1"
                               class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                               {{ old('inbound_enabled', $connection?->inbound_enabled ?? false) ? 'checked' : '' }}>
                        <div>
                            <span class="text-sm font-medium group-hover:text-primary transition-colors">Bật nhận webhook</span>
                            <p class="text-xs text-base-content/50 mt-0.5">URL nhận webhook luôn tồn tại ngay từ khi tạo — tắt chỉ khoá xử lý, không xoá URL</p>
                        </div>
                    </label>

                    <div class="divider my-1">Chiều gọi ra (app &rarr; n8n)</div>

                    {{-- x-data bao cả checkbox + field URL trong CÙNG 1 scope — $refs chỉ resolve
                         được ref là con/cháu của chính x-data đang gọi nó, không resolve được ref
                         khai báo ở phần tử anh em thuộc 1 x-data khác/không có x-data. --}}
                    <div x-data="{ outboundEnabled: {{ old('outbound_enabled', $connection?->outbound_enabled ?? false) ? 'true' : 'false' }} }">
                        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                            <input type="hidden" name="outbound_enabled" value="0">
                            <input type="checkbox" name="outbound_enabled" value="1" x-model="outboundEnabled"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0">
                            <div>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Bật gọi ra</span>
                                <p class="text-xs text-base-content/50 mt-0.5">Cho phép N8n::send()/N8nOutboundService gọi tới kết nối này</p>
                            </div>
                        </label>

                        <div class="form-control mt-4" :class="{ 'opacity-40': !outboundEnabled }">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Outbound webhook URL</span>
                                <span class="label-text-alt text-xs text-base-content/40">URL trigger webhook của n8n — bắt buộc nếu bật gọi ra</span>
                            </label>
                            <input type="url" name="outbound_webhook_url" value="{{ old('outbound_webhook_url', $connection?->outbound_webhook_url) }}"
                                   class="input input-bordered input-sm w-full @error('outbound_webhook_url') input-error @enderror"
                                   maxlength="2000" placeholder="https://n8n.example.com/webhook/xxxx">
                            @error('outbound_webhook_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="divider my-1">Bảo mật &amp; giới hạn</div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">IP allowlist (inbound)</span>
                            <span class="label-text-alt text-xs text-base-content/40">1 dải CIDR/dòng, để trống = không giới hạn</span>
                        </label>
                        <textarea name="allowed_ip_cidrs_text" rows="3"
                                  class="textarea textarea-bordered textarea-sm w-full font-mono text-xs @error('allowed_ip_cidrs') textarea-error @enderror @error('allowed_ip_cidrs.*') textarea-error @enderror"
                                  placeholder="203.0.113.0/24&#10;2001:db8::/32"
                        >{{ old('allowed_ip_cidrs_text', $connection?->allowed_ip_cidrs ? implode("\n", $connection->allowed_ip_cidrs) : '') }}</textarea>
                        @error('allowed_ip_cidrs')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        @error('allowed_ip_cidrs.*')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Rate limit (request/phút)</span>
                            <span class="label-text-alt text-xs text-base-content/40">Để trống = dùng mặc định hệ thống ({{ config('n8n.default_rate_limit_per_minute') }}/phút)</span>
                        </label>
                        <input type="number" name="rate_limit_per_minute" min="1" max="6000"
                               value="{{ old('rate_limit_per_minute', $connection?->rate_limit_per_minute) }}"
                               class="input input-bordered input-sm w-full @error('rate_limit_per_minute') input-error @enderror">
                        @error('rate_limit_per_minute')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                </div>
            </div>
        </div>

        @if($connection)
            @include('n8n::connections._secrets-panel', ['connection' => $connection])
        @else
        <div class="alert py-3 px-4 text-sm bg-info/10 border border-info/30">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Token &amp; secret inbound sẽ được hệ thống tự sinh ngay sau khi tạo — hiển thị plaintext <b>đúng 1 lần</b> trên trang danh sách, không lưu lại và không hiển thị lại được. Outbound secret để trống (gửi không ký) cho tới khi bạn chủ động "Xoay" ở trang sửa.</span>
        </div>
        @endif
    </div>

    {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
    <div class="xl:sticky xl:top-4 space-y-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-3">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Xuất bản</p>
                <div class="flex gap-2">
                    <a href="{{ route('backend.n8n.connections.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                    <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ $connection ? 'Lưu thay đổi' : 'Tạo kết nối' }}
                    </button>
                </div>
                <p class="text-center text-xs text-base-content/30 mt-2.5"><span class="text-error">*</span> là trường bắt buộc</p>
            </div>
        </div>
    </div>

</div>
