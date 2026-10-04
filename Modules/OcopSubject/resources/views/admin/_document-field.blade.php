@php
    $files = $ocopSubject?->getMedia($collection) ?? collect();
    $urlService = app(\App\Services\Media\MediaUrlService::class);
@endphp
<div class="form-control">
    <label class="label py-0 pb-1.5">
        <span class="label-text font-medium">{{ $label }}</span>
        @isset($requiredWhen)
        <span x-show="{{ $requiredWhen }}" x-cloak class="label-text-alt text-xs text-error font-medium">Bắt buộc</span>
        @endisset
    </label>

    @if($files->isNotEmpty())
    <ul class="mb-2 divide-y divide-base-200 rounded-lg border border-base-300">
        @foreach($files as $file)
        <li class="flex items-center gap-3 px-3 py-2 text-sm">
            <svg class="w-4 h-4 shrink-0 text-base-content/50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            <a href="{{ $urlService->url($file) }}" target="_blank" rel="noopener" class="link link-hover truncate flex-1">{{ $file->file_name }}</a>
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

    <input type="file" name="documents[{{ $collection }}][]" multiple
           accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"
           class="file-input file-input-bordered file-input-sm w-full @if($errors->has("documents.$collection") || $errors->has("documents.$collection.*")) file-input-error @endif">

    @isset($hint)
    <small class="mt-1.5 block text-xs text-base-content/50 leading-relaxed">{{ $hint }}</small>
    @endisset
    <small class="block text-xs text-base-content/40">Có thể chọn nhiều tệp. PDF, JPG, PNG, WEBP — tối đa 20MB/tệp, {{ \Modules\OcopSubject\Models\OcopSubject::MAX_DOCUMENTS }} tệp mỗi loại.</small>
    @foreach(collect($errors->get("documents.$collection"))->merge($errors->get("documents.$collection.*"))->flatten()->unique() as $message)
    <p class="mt-1 text-xs text-error">{{ $message }}</p>
    @endforeach
</div>
