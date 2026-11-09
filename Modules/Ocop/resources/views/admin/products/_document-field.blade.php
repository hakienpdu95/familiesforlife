@php
    $files = $product?->getMedia($collection) ?? collect();
    $urlService = app(\App\Services\Media\MediaUrlService::class);
    $maxMb = (int) (\Modules\Ocop\Models\OcopProduct::MAX_DOCUMENT_KB / 1024);
    $pendingUuids = array_filter((array) old("document_uuids.$collection", []), 'is_string');
    $pending = $pendingUuids
        ? \App\Models\Media::query()
            ->whereIn('uuid', $pendingUuids)
            ->where('collection_name', $collection)
            ->where('model_type', \App\Models\FilePondDraft::class)
            ->whereIn('model_id', \App\Models\FilePondDraft::query()->where('user_id', auth()->id())->select('id'))
            ->get()
        : collect();
    $errorKeys = ["document_uuids.$collection", "document_uuids.$collection.*"];
@endphp
<div class="form-control">
    <label class="label py-0 pb-1.5">
        <span class="label-text font-medium">{{ $label }}</span>
        @isset($badge)
        <span class="label-text-alt text-xs text-warning font-medium">{{ $badge }}</span>
        @endisset
    </label>

    @if($files->isNotEmpty())
    <ul class="mb-2 divide-y divide-base-200 rounded-lg border border-base-300">
        @foreach($files as $file)
        <li class="flex items-center gap-3 px-3 py-2 text-sm">
            <svg class="w-4 h-4 shrink-0 text-base-content/50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            <a href="{{ $urlService->url($file) }}" target="_blank" rel="noopener" class="link link-hover truncate flex-1">{{ $file->file_name }}</a>
            <span class="text-xs font-mono text-base-content/50 shrink-0" title="Mã tệp hiển thị trên trang truy xuất QR">{{ strtoupper(substr(str_replace('-', '', $file->uuid), 0, 8)) }}</span>
            <span class="text-xs text-base-content/40 shrink-0">{{ $file->human_readable_size }}</span>
            <label class="flex items-center gap-1 text-xs text-error cursor-pointer shrink-0">
                <input type="checkbox" name="remove_media_uuids[]" value="{{ $file->uuid }}" class="checkbox checkbox-error checkbox-xs"
                       @checked(in_array($file->uuid, old('remove_media_uuids', []), true))>
                Xoá
            </label>
        </li>
        @endforeach
    </ul>
    @endif

    <div data-doc-uploads data-collection="{{ $collection }}" data-max-files="{{ max(1, \Modules\Ocop\Models\OcopProduct::MAX_DOCUMENTS - $files->count() - $pending->count()) }}">
        @foreach($pending as $file)
        <div data-doc-pending class="mb-2 flex items-center gap-3 rounded-lg border border-dashed border-base-300 px-3 py-2 text-sm">
            <input type="hidden" name="document_uuids[{{ $collection }}][]" value="{{ $file->uuid }}">
            <span class="badge badge-ghost badge-sm shrink-0">Chờ lưu</span>
            <span class="truncate flex-1">{{ $file->file_name }}</span>
            <span class="text-xs text-base-content/40 shrink-0">{{ $file->human_readable_size }}</span>
            <button type="button" class="btn btn-ghost btn-xs text-error" onclick="this.closest('[data-doc-pending]').remove()">Bỏ</button>
        </div>
        @endforeach
        <div data-doc-pond></div>
    </div>

    <small class="mt-1.5 block text-xs text-base-content/50 leading-relaxed">{{ $hint }}</small>
    <small class="block text-xs text-base-content/40">PDF, JPG, PNG, WEBP — tối đa {{ $maxMb }}MB/tệp, {{ \Modules\Ocop\Models\OcopProduct::MAX_DOCUMENTS }} tệp mỗi loại. Tệp lớn được chia nhỏ khi tải lên và chỉ được thêm vào hồ sơ khi bấm Lưu.</small>
    @foreach(collect($errors->get($errorKeys[0]))->merge($errors->get($errorKeys[1]))->flatten()->unique() as $message)
    <p class="mt-1 text-xs text-error">{{ $message }}</p>
    @endforeach
</div>
