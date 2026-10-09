{{-- Nhóm menu sidebar — tự ẩn cả tiêu đề khi không còn mục nào hiển thị sau khi qua @can bên trong slot. --}}
@props(['title', 'first' => false])

@if(trim((string) $slot) !== '')
<p class="section-title" @unless($first) style="margin-top:16px;" @endunless>{{ $title }}</p>
{{ $slot }}
@endif
