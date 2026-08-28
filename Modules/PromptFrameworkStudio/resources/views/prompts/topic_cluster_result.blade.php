@extends('layouts.backend')
@section('title', 'Duyệt kết quả — '.$prompt->label)

{{-- (2026-08-28, phản hồi review spec/TopicClusterGenerator.md) — dán kết quả AI (Markdown) đã
     chạy từ prompt `topiccluster` → hệ thống parse thành Pillar/Cluster (xem
     ParseTopicClusterAiResultAction) → biên tập viên tick chọn từng mục muốn giữ → đẩy sang
     Modules\ContentOutlines làm bản nháp Content Outline. KHÔNG tự động — mọi bước đều cần người
     bấm, đúng tinh thần "gợi ý không quyết định thay" xuyên suốt module này. --}}
@section('content')

@foreach(['success','error'] as $type)
    @if(session($type))
    <div class="alert alert-{{ $type }} mb-4 text-sm">
        <span>{{ session($type) }}</span>
    </div>
    @endif
@endforeach

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Duyệt kết quả AI &amp; đẩy sang Content Outlines</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Prompt: <b>{{ $prompt->label }}</b></p>
    </div>
    <a href="{{ route('backend.promptstudio.prompts.show', $prompt) }}" class="btn btn-ghost btn-sm">Xem prompt</a>
</div>

<div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
    <div class="card-body p-5 space-y-2">
        <h2 class="card-title text-base">1. Dán kết quả AI đã chạy</h2>
        <p class="text-xs text-base-content/50">
            Chạy prompt ở ChatGPT/Claude như bình thường, rồi dán TOÀN BỘ câu trả lời (bao gồm cả khối mã
            <code>PILLAR: ... | ...</code> / <code>CLUSTER: ... | ...</code> ở cuối) vào ô bên dưới.
        </p>
        <form method="POST" action="{{ route('backend.promptstudio.prompts.topic-cluster-result.save', $prompt) }}">
            @csrf
            <textarea name="ai_result_raw" rows="10" required maxlength="100000"
                      class="textarea textarea-bordered w-full font-mono text-xs @error('ai_result_raw') textarea-error @enderror"
                      placeholder="Dán nguyên văn câu trả lời của AI vào đây...">{{ old('ai_result_raw') }}</textarea>
            @error('ai_result_raw')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            <button type="submit" class="btn btn-primary btn-sm mt-2">Phân tích</button>
        </form>
    </div>
</div>

@if($result)
    @php($structured = $result->structured)
    @php($pillar = $structured['pillar'] ?? null)
    @php($clusters = $structured['clusters'] ?? [])

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-5 space-y-3">
            <h2 class="card-title text-base">2. Duyệt từng mục rồi đẩy sang Content Outlines</h2>

            @if(!$pillar)
                <p class="text-sm text-warning">Chưa parse được Pillar/Cluster nào — kiểm tra bạn đã dán đủ khối mã "PILLAR:/CLUSTER:" ở cuối kết quả AI.</p>
            @else
                <form method="POST" action="{{ route('backend.promptstudio.prompts.topic-cluster-result.push', $prompt) }}" class="space-y-2">
                    @csrf
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th class="w-8"></th>
                                    <th>Vai trò</th>
                                    <th>Tiêu đề</th>
                                    <th>Từ khóa mục tiêu</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        @if($pillar['content_outline_uuid'])
                                            <input type="checkbox" class="checkbox checkbox-sm" disabled>
                                        @else
                                            <input type="checkbox" name="selected[]" value="pillar" class="checkbox checkbox-sm" checked>
                                        @endif
                                    </td>
                                    <td><span class="badge badge-primary badge-sm">Pillar</span></td>
                                    <td class="font-medium">{{ $pillar['title'] }}</td>
                                    <td class="text-xs text-base-content/60">{{ $pillar['target_keyword'] }}</td>
                                    <td>
                                        @if($pillar['content_outline_uuid'])
                                            <a href="{{ route('backend.contentoutlines.show', $pillar['content_outline_uuid']) }}" class="link link-success text-xs" target="_blank" rel="noopener">✓ Đã đẩy — xem Content Outline</a>
                                        @else
                                            <span class="text-xs text-base-content/40">Chưa đẩy</span>
                                        @endif
                                    </td>
                                </tr>
                                @foreach($clusters as $i => $cluster)
                                <tr>
                                    <td>
                                        @if($cluster['content_outline_uuid'])
                                            <input type="checkbox" class="checkbox checkbox-sm" disabled>
                                        @else
                                            <input type="checkbox" name="selected[]" value="cluster:{{ $i }}" class="checkbox checkbox-sm" checked>
                                        @endif
                                    </td>
                                    <td><span class="badge badge-outline badge-sm">Cluster</span></td>
                                    <td class="font-medium">{{ $cluster['title'] }}</td>
                                    <td class="text-xs text-base-content/60">{{ $cluster['target_keyword'] }}</td>
                                    <td>
                                        @if($cluster['content_outline_uuid'])
                                            <a href="{{ route('backend.contentoutlines.show', $cluster['content_outline_uuid']) }}" class="link link-success text-xs" target="_blank" rel="noopener">✓ Đã đẩy — xem Content Outline</a>
                                        @else
                                            <span class="text-xs text-base-content/40">Chưa đẩy</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @can('content_outlines.use')
                        <button type="submit" class="btn btn-primary btn-sm">Đẩy các mục đã chọn sang Content Outlines</button>
                    @else
                        <p class="text-xs text-base-content/40">Bạn cần quyền dùng Content Outlines để đẩy mục sang module đó.</p>
                    @endcan
                </form>
            @endif
        </div>
    </div>
@endif

@endsection
