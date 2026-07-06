@extends('layouts.backend')

@section('title', $candidate->full_name . ' — Ứng viên')


@section('content')
<div x-data="rcCandidateShow" class="p-6 space-y-5 max-w-5xl">

    {{-- Header --}}
    <div class="flex items-start justify-between">
        <div class="flex items-center gap-4">
            <div class="avatar placeholder">
                <div class="bg-primary text-primary-content rounded-full w-14">
                    <span class="text-xl font-bold">{{ mb_substr($candidate->full_name, 0, 1) }}</span>
                </div>
            </div>
            <div>
                <h1 class="text-xl font-bold">{{ $candidate->full_name }}</h1>
                <p class="text-sm opacity-60">{{ $candidate->current_title ?? 'Chưa có chức danh' }}
                    @if($candidate->current_company)
                    <span class="mx-1">·</span>{{ $candidate->current_company }}
                    @endif
                </p>
                <div class="flex items-center gap-2 mt-1">
                    @php
                        $statusBadge = match($candidate->status?->value) {
                            'active'      => 'badge-success',
                            'hired'       => 'badge-info',
                            'blacklisted' => 'badge-error',
                            default       => 'badge-ghost',
                        };
                    @endphp
                    <span class="badge {{ $statusBadge }} badge-sm">{{ $candidate->status?->label() }}</span>
                    <span class="badge badge-outline badge-sm">{{ $candidate->source?->label() }}</span>
                </div>
            </div>
        </div>
        <div class="flex gap-2">
            @can('create', \Modules\Recruitment\Models\RcApplication::class)
            <a href="{{ route('backend.recruitment.applications.create', ['candidate_id' => $candidate->id]) }}"
               class="btn btn-primary btn-sm">Tạo đơn ứng tuyển</a>
            @endcan
            @can('update', $candidate)
            <a href="{{ route('backend.recruitment.candidates.edit', $candidate) }}" class="btn btn-ghost btn-sm">Chỉnh sửa</a>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-3 gap-5">

        {{-- Left: Info --}}
        <div class="col-span-1 space-y-4">

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4 space-y-3">
                    <h3 class="font-semibold text-sm uppercase tracking-wide opacity-60">Thông tin liên hệ</h3>

                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 opacity-40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <a href="mailto:{{ $candidate->email }}" class="link link-hover">{{ $candidate->email }}</a>
                    </div>

                    @if($candidate->phone)
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 opacity-40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <span>{{ $candidate->phone }}</span>
                    </div>
                    @endif

                    @if($candidate->linkedin_url)
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 opacity-40 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        <a href="{{ $candidate->linkedin_url }}" target="_blank" class="link link-hover truncate">LinkedIn</a>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4 space-y-2">
                    <h3 class="font-semibold text-sm uppercase tracking-wide opacity-60">Chi tiết</h3>

                    @if($candidate->years_experience !== null)
                    <div class="flex justify-between text-sm">
                        <span class="opacity-60">Kinh nghiệm</span>
                        <span class="font-medium">{{ $candidate->years_experience }} năm</span>
                    </div>
                    @endif

                    @if($candidate->gender)
                    <div class="flex justify-between text-sm">
                        <span class="opacity-60">Giới tính</span>
                        <span class="font-medium">{{ match($candidate->gender) { 'male' => 'Nam', 'female' => 'Nữ', default => 'Khác' } }}</span>
                    </div>
                    @endif

                    <div class="flex justify-between text-sm">
                        <span class="opacity-60">Ngày thêm</span>
                        <span>{{ $candidate->created_at?->format('d/m/Y') }}</span>
                    </div>

                    @if($candidate->createdBy)
                    <div class="flex justify-between text-sm">
                        <span class="opacity-60">Thêm bởi</span>
                        <span>{{ $candidate->createdBy->name }}</span>
                    </div>
                    @endif
                </div>
            </div>

            @if($candidate->skills)
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <h3 class="font-semibold text-sm uppercase tracking-wide opacity-60 mb-2">Kỹ năng</h3>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach(array_filter(array_map('trim', explode(',', $candidate->skills))) as $skill)
                        <span class="badge badge-outline badge-sm">{{ $skill }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Right: Tabs --}}
        <div class="col-span-2">
            <div class="tabs tabs-bordered mb-4">
                <button class="tab tab-active" @click="activeTab = 'applications'" :class="{'tab-active': activeTab === 'applications'}">
                    Đơn ứng tuyển ({{ $candidate->applications->count() }})
                </button>
                <button class="tab" @click="activeTab = 'notes'" :class="{'tab-active': activeTab === 'notes'}">
                    Ghi chú ({{ $candidate->notes->count() }})
                </button>
                <button class="tab" @click="activeTab = 'attachments'" :class="{'tab-active': activeTab === 'attachments'}">
                    Tài liệu ({{ $candidate->attachments->count() }})
                </button>
            </div>

            {{-- Tab: Applications --}}
            <div x-show="activeTab === 'applications'">
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-semibold">Đơn ứng tuyển</h3>
                            @can('create', \Modules\Recruitment\Models\RcApplication::class)
                            <a href="{{ route('backend.recruitment.applications.create', ['candidate_id' => $candidate->id]) }}"
                               class="btn btn-ghost btn-xs">+ Tạo đơn mới</a>
                            @endcan
                        </div>

                        @forelse($candidate->applications as $app)
                        <div class="border border-base-200 rounded-lg p-3 mb-3 hover:bg-base-50">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        @if($app->jp_job_post_id)
                                        <span class="text-xs opacity-50 font-mono">{{ $app->jp_job_post_id }}</span>
                                        @else
                                        <span class="text-sm opacity-60">Đơn trực tiếp</span>
                                        @endif
                                        @php
                                            $stageBadge = match($app->status?->value) {
                                                'active'    => 'badge-primary',
                                                'hired'     => 'badge-success',
                                                'rejected'  => 'badge-error',
                                                'withdrawn' => 'badge-ghost',
                                                default     => 'badge-outline',
                                            };
                                        @endphp
                                        <span class="badge {{ $stageBadge }} badge-xs">{{ $app->status?->label() }}</span>
                                        @if($app->is_disqualified)
                                        <span class="badge badge-warning badge-xs">Disqualified</span>
                                        @endif
                                    </div>
                                    @if($app->currentStage)
                                    <div class="flex items-center gap-1 mt-1">
                                        @if($app->currentStage->color_hex)
                                        <span class="w-2 h-2 rounded-full inline-block" style="background: {{ $app->currentStage->color_hex }}"></span>
                                        @endif
                                        <span class="text-xs">Stage: {{ $app->currentStage->name }}</span>
                                    </div>
                                    @endif
                                    <p class="text-xs opacity-50 mt-1">Nộp: {{ $app->applied_at?->format('d/m/Y') }} · {{ $app->apply_source?->label() }}</p>
                                </div>
                                <a href="{{ route('backend.recruitment.applications.show', $app) }}"
                                   class="btn btn-ghost btn-xs">Xem chi tiết →</a>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-8 text-sm opacity-50">Chưa có đơn ứng tuyển nào</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Tab: Notes --}}
            <div x-show="activeTab === 'notes'">
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-semibold">Ghi chú</h3>
                            @can('update', $candidate)
                            <button class="btn btn-ghost btn-xs" @click="showNoteForm = !showNoteForm">+ Thêm ghi chú</button>
                            @endcan
                        </div>

                        @can('update', $candidate)
                        <div x-show="showNoteForm" class="border border-base-200 rounded-lg p-3 mb-4 bg-base-50">
                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <div class="form-control">
                                    <label class="label py-0"><span class="label-text text-xs">Loại ghi chú</span></label>
                                    <select x-model="noteForm.note_type" class="select select-bordered select-sm">
                                        @foreach($noteTypes as $type)
                                        <option value="{{ $type['value'] }}">{{ $type['text'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-control">
                                    <label class="label py-0"><span class="label-text text-xs">Riêng tư</span></label>
                                    <label class="label cursor-pointer justify-start gap-2 mt-1">
                                        <input type="checkbox" class="checkbox checkbox-sm" x-model="noteForm.is_private">
                                        <span class="text-xs opacity-60">Chỉ mình tôi và HR Admin thấy</span>
                                    </label>
                                </div>
                            </div>
                            <div class="form-control mb-3">
                                <textarea x-model="noteForm.content" class="textarea textarea-bordered textarea-sm w-full" rows="3"
                                          placeholder="Nội dung ghi chú..."></textarea>
                            </div>
                            <div class="flex gap-2">
                                <button class="btn btn-primary btn-xs" @click="submitNote()">Lưu ghi chú</button>
                                <button class="btn btn-ghost btn-xs" @click="showNoteForm = false">Hủy</button>
                            </div>
                        </div>
                        @endcan

                        <div class="space-y-3" id="notes-list">
                            @forelse($candidate->notes->sortByDesc('created_at') as $note)
                            <div class="border border-base-200 rounded-lg p-3" id="note-{{ $note->id }}">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            @php
                                                $noteBadge = match($note->note_type?->value) {
                                                    'concern'        => 'badge-warning',
                                                    'interview_note' => 'badge-info',
                                                    'follow_up'      => 'badge-primary',
                                                    default          => 'badge-ghost',
                                                };
                                            @endphp
                                            <span class="badge {{ $noteBadge }} badge-xs">{{ $note->note_type?->label() }}</span>
                                            @if($note->is_private)
                                            <span class="badge badge-outline badge-xs">Riêng tư</span>
                                            @endif
                                        </div>
                                        <p class="text-sm whitespace-pre-wrap">{{ $note->content }}</p>
                                        <p class="text-xs opacity-40 mt-1">{{ $note->createdBy?->name }} · {{ $note->created_at?->format('d/m/Y H:i') }}</p>
                                    </div>
                                    @if($note->created_by === auth()->id() || auth()->user()->hasRole('HR_Admin'))
                                    <button class="btn btn-ghost btn-xs text-error shrink-0"
                                            @click="deleteNote({{ $note->id }})">Xóa</button>
                                    @endif
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-8 text-sm opacity-50" id="notes-empty">Chưa có ghi chú nào</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab: Attachments --}}
            <div x-show="activeTab === 'attachments'">
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-semibold">Tài liệu đính kèm</h3>
                            @can('update', $candidate)
                            <button class="btn btn-ghost btn-xs" @click="showAttachmentForm = !showAttachmentForm">+ Tải lên</button>
                            @endcan
                        </div>

                        @can('update', $candidate)
                        <div x-show="showAttachmentForm" class="border border-base-200 rounded-lg p-3 mb-4 bg-base-50">
                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <div class="form-control">
                                    <label class="label py-0"><span class="label-text text-xs">Loại file</span></label>
                                    <select x-model="attachForm.file_type" class="select select-bordered select-sm">
                                        @foreach($fileTypes as $type)
                                        <option value="{{ $type['value'] }}">{{ $type['text'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-control">
                                    <label class="label py-0"><span class="label-text text-xs">File (tối đa 10 MB)</span></label>
                                    <input type="file" class="file-input file-input-bordered file-input-sm w-full"
                                           @change="attachForm.file = $event.target.files[0]">
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button class="btn btn-primary btn-xs" @click="submitAttachment()">Tải lên</button>
                                <button class="btn btn-ghost btn-xs" @click="showAttachmentForm = false">Hủy</button>
                            </div>
                        </div>
                        @endcan

                        <div class="space-y-2" id="attachments-list">
                            @forelse($candidate->attachments->sortByDesc('uploaded_at') as $att)
                            <div class="flex items-center gap-3 border border-base-200 rounded-lg p-3" id="att-{{ $att->id }}">
                                <div class="text-2xl opacity-40">
                                    @php
                                        $icon = match($att->file_type?->value) {
                                            'cv', 'cover_letter' => '📄',
                                            'portfolio'          => '🖼️',
                                            'test_result'        => '📊',
                                            'certificate'        => '🏆',
                                            default              => '📎',
                                        };
                                    @endphp
                                    {{ $icon }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <a href="{{ $att->file_url }}" target="_blank"
                                       class="text-sm font-medium link link-hover truncate block">{{ $att->file_name }}</a>
                                    <p class="text-xs opacity-40">
                                        {{ $att->file_type?->label() }} · {{ $att->fileSizeFormatted() }} · {{ $att->uploadedBy?->name }} · {{ $att->uploaded_at?->format('d/m/Y') }}
                                    </p>
                                </div>
                                @can('update', $candidate)
                                <button class="btn btn-ghost btn-xs text-error shrink-0"
                                        @click="deleteAttachment({{ $att->id }})">Xóa</button>
                                @endcan
                            </div>
                            @empty
                            <div class="text-center py-8 text-sm opacity-50" id="attachments-empty">Chưa có tài liệu nào</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- /col-span-2 --}}
    </div>{{-- /grid --}}
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', function() {
    var CANDIDATE_ID    = {{ $candidate->id }};
    var NOTE_STORE_URL  = '{{ route('backend.recruitment.candidates.notes.store', $candidate) }}';
    var NOTE_DELETE_URL = '{{ url('dashboard/recruitment/candidates/' . $candidate->id . '/notes') }}';
    var ATT_STORE_URL   = '{{ route('backend.recruitment.candidates.attachments.store', $candidate) }}';
    var ATT_DELETE_URL  = '{{ url('dashboard/recruitment/candidates/' . $candidate->id . '/attachments') }}';
    var CSRF = '{{ csrf_token() }}';

    Alpine.data('rcCandidateShow', function() {
        return {
            activeTab: 'applications',
            showNoteForm: false,
            showAttachmentForm: false,

            noteForm: { content: '', note_type: 'general', is_private: false },
            attachForm: { file_type: 'cv', file: null },

            submitNote: function() {
                var self = this;
                if (!self.noteForm.content.trim()) { alert('Vui lòng nhập nội dung'); return; }

                fetch(NOTE_STORE_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify(self.noteForm),
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var list = document.getElementById('notes-list');
                    var emptyEl = document.getElementById('notes-empty');
                    if (emptyEl) emptyEl.remove();

                    var n = data.note;
                    var typeBadge = n.note_type === 'concern' ? 'badge-warning' : n.note_type === 'interview_note' ? 'badge-info' : n.note_type === 'follow_up' ? 'badge-primary' : 'badge-ghost';
                    var html = '<div class="border border-base-200 rounded-lg p-3" id="note-' + n.id + '">'
                        + '<div class="flex items-start justify-between gap-2"><div class="flex-1 min-w-0">'
                        + '<div class="flex items-center gap-2 mb-1"><span class="badge ' + typeBadge + ' badge-xs">' + n.note_label + '</span>'
                        + (n.is_private ? '<span class="badge badge-outline badge-xs">Riêng tư</span>' : '')
                        + '</div><p class="text-sm whitespace-pre-wrap">' + n.content.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</p>'
                        + '<p class="text-xs opacity-40 mt-1">' + (n.created_by || '') + ' · ' + (n.created_at || '') + '</p>'
                        + '</div><button class="btn btn-ghost btn-xs text-error shrink-0" onclick="deleteNoteGlobal(' + n.id + ')">Xóa</button></div></div>';

                    list.insertAdjacentHTML('afterbegin', html);
                    self.noteForm = { content: '', note_type: 'general', is_private: false };
                    self.showNoteForm = false;
                })
                .catch(function(e) { console.error(e); alert('Lỗi khi lưu ghi chú'); });
            },

            deleteNote: function(noteId) {
                if (!confirm('Xóa ghi chú này?')) return;
                fetch(NOTE_DELETE_URL + '/' + noteId, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
                })
                .then(function(r) { return r.json(); })
                .then(function() {
                    var el = document.getElementById('note-' + noteId);
                    if (el) el.remove();
                })
                .catch(function(e) { console.error(e); });
            },

            submitAttachment: function() {
                var self = this;
                if (!self.attachForm.file) { alert('Vui lòng chọn file'); return; }

                var fd = new FormData();
                fd.append('file', self.attachForm.file);
                fd.append('file_type', self.attachForm.file_type);
                fd.append('_token', CSRF);

                fetch(ATT_STORE_URL, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var list = document.getElementById('attachments-list');
                    var emptyEl = document.getElementById('attachments-empty');
                    if (emptyEl) emptyEl.remove();

                    var a = data.attachment;
                    var icons = { cv: '📄', cover_letter: '📄', portfolio: '🖼️', test_result: '📊', certificate: '🏆' };
                    var icon = icons[a.file_type] || '📎';
                    var html = '<div class="flex items-center gap-3 border border-base-200 rounded-lg p-3" id="att-' + a.id + '">'
                        + '<div class="text-2xl opacity-40">' + icon + '</div>'
                        + '<div class="flex-1 min-w-0"><a href="' + a.file_url + '" target="_blank" class="text-sm font-medium link link-hover truncate block">' + a.file_name + '</a>'
                        + '<p class="text-xs opacity-40">' + a.file_label + ' · ' + a.file_size + ' · ' + (a.uploaded_by || '') + ' · ' + (a.uploaded_at || '') + '</p></div>'
                        + '<button class="btn btn-ghost btn-xs text-error shrink-0" onclick="deleteAttachmentGlobal(' + a.id + ')">Xóa</button></div>';

                    list.insertAdjacentHTML('afterbegin', html);
                    self.attachForm.file = null;
                    self.showAttachmentForm = false;
                    document.querySelector('input[type=file]') && (document.querySelector('input[type=file]').value = '');
                })
                .catch(function(e) { console.error(e); alert('Lỗi khi tải lên file'); });
            },

            deleteAttachment: function(attId) {
                if (!confirm('Xóa file đính kèm này?')) return;
                fetch(ATT_DELETE_URL + '/' + attId, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
                })
                .then(function(r) { return r.json(); })
                .then(function() {
                    var el = document.getElementById('att-' + attId);
                    if (el) el.remove();
                })
                .catch(function(e) { console.error(e); });
            },
        };
    });

    // Global helpers cho dynamically inserted elements
    window.deleteNoteGlobal = function(noteId) {
        Alpine.$data(document.querySelector('[x-data="rcCandidateShow"]')).deleteNote(noteId);
    };
    window.deleteAttachmentGlobal = function(attId) {
        Alpine.$data(document.querySelector('[x-data="rcCandidateShow"]')).deleteAttachment(attId);
    };
});
</script>
@endpush
