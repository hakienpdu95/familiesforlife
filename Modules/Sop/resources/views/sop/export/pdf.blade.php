<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8"/>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #1a1a1a; background: #fff; padding: 20px; }
  h1 { font-size: 16px; font-weight: bold; margin-bottom: 4px; color: #1a1a1a; }
  h2 { font-size: 12px; font-weight: bold; margin: 14px 0 6px; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 3px; }
  .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
  .meta-table td { padding: 3px 8px; vertical-align: top; font-size: 10px; }
  .meta-table td:first-child { color: #666; width: 140px; }
  .badge { display: inline-block; padding: 1px 8px; border-radius: 99px; font-size: 9px; font-weight: 600; }
  .badge-draft    { background: #e5e7eb; color: #374151; }
  .badge-approved { background: #dcfce7; color: #166534; }
  .badge-pending  { background: #fef9c3; color: #854d0e; }
  .badge-rejected { background: #fee2e2; color: #991b1b; }
  .badge-archived { background: #f3f4f6; color: #6b7280; }
  .steps-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
  .steps-table th { background: #f1f5f9; font-size: 9px; font-weight: 600; padding: 4px 8px; text-align: left; border: 1px solid #e2e8f0; color: #475569; }
  .steps-table td { padding: 5px 8px; border: 1px solid #e2e8f0; vertical-align: top; font-size: 9px; }
  .steps-table tr:nth-child(even) td { background: #f8fafc; }
  .type-badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 8px; font-weight: 600; }
  .type-start, .type-end   { background: #E1F5EE; color: #1D9E75; }
  .type-action             { background: #E6F1FB; color: #378ADD; }
  .type-decision           { background: #FAEEDA; color: #EF9F27; }
  .type-sub_sop            { background: #E1F5EE; color: #1D9E75; }
  .type-notification       { background: #EEEDFE; color: #7F77DD; }
  .type-wait               { background: #F1EFE8; color: #888780; }
  .raci-badge { display: inline-block; padding: 1px 5px; border-radius: 2px; font-size: 8px; font-weight: 700; margin-right: 2px; }
  .raci-R { background: #dbeafe; color: #1d4ed8; }
  .raci-A { background: #fef3c7; color: #92400e; }
  .raci-C { background: #d1fae5; color: #065f46; }
  .raci-I { background: #f3f4f6; color: #374151; }
  .footer { margin-top: 14px; padding-top: 6px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #94a3b8; text-align: right; }
  .page-break { page-break-before: always; }
  .no-steps { color: #94a3b8; font-style: italic; padding: 10px 0; }
</style>
</head>
<body>

{{-- Header --}}
<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
  <div>
    <div style="font-size:9px;color:#94a3b8;font-family:monospace;margin-bottom:3px;">{{ $sop->code }}</div>
    <h1>{{ $sop->title }}</h1>
    @if($sop->description)
    <p style="font-size:9px;color:#64748b;margin-top:4px;max-width:600px;">{{ $sop->description }}</p>
    @endif
  </div>
  <div style="text-align:right;">
    @php
      $badgeClass = match($sop->status?->value) {
        'approved'       => 'badge-approved',
        'pending_review' => 'badge-pending',
        'rejected'       => 'badge-rejected',
        'archived'       => 'badge-archived',
        default          => 'badge-draft',
      };
    @endphp
    <span class="badge {{ $badgeClass }}">{{ $sop->status?->label() }}</span>
    @if($sop->version > 0)
    <div style="font-size:9px;color:#64748b;margin-top:3px;">Phiên bản {{ $sop->version }}</div>
    @endif
  </div>
</div>

{{-- Metadata table --}}
<table class="meta-table">
  <tr>
    <td>Loại quy trình</td>
    <td>{{ $sop->type?->label() ?? $sop->type }}</td>
    <td>Người phụ trách</td>
    <td>{{ $sop->owner?->name ?? '—' }}</td>
  </tr>
  <tr>
    <td>Chi nhánh</td>
    <td>{{ $sop->branch?->name ?? 'Toàn tổ chức' }}</td>
    <td>Phòng ban</td>
    <td>{{ $sop->department?->name ?? '—' }}</td>
  </tr>
  @if($sop->effective_date || $sop->expired_date)
  <tr>
    <td>Ngày hiệu lực</td>
    <td>{{ $sop->effective_date?->format('d/m/Y') ?? '—' }}</td>
    <td>Ngày hết hạn</td>
    <td>{{ $sop->expired_date?->format('d/m/Y') ?? '—' }}</td>
  </tr>
  @endif
  @if($sop->approved_at)
  <tr>
    <td>Người duyệt</td>
    <td>{{ $sop->approvedBy?->name ?? '—' }}</td>
    <td>Ngày duyệt</td>
    <td>{{ $sop->approved_at->format('d/m/Y H:i') }}</td>
  </tr>
  @endif
</table>

{{-- Steps table --}}
<h2>Các bước quy trình</h2>

@if($steps->isEmpty())
  <p class="no-steps">Chưa có bước nào trong quy trình này.</p>
@else
<table class="steps-table">
  <thead>
    <tr>
      <th style="width:30px;">#</th>
      <th style="width:60px;">Loại</th>
      <th style="width:180px;">Tên bước</th>
      <th>Mô tả / Kết quả đầu ra</th>
      <th style="width:140px;">RACI</th>
      <th style="width:40px;">Thời gian</th>
    </tr>
  </thead>
  <tbody>
    @foreach($steps as $step)
    @php $step = (array)$step; @endphp
    <tr>
      <td style="text-align:center;color:#94a3b8;">{{ $step['position'] }}</td>
      <td>
        <span class="type-badge type-{{ $step['step_type'] }}">
          {{ match($step['step_type']) {
            'start'        => 'Bắt đầu',
            'end'          => 'Kết thúc',
            'action'       => 'Hành động',
            'decision'     => 'Quyết định',
            'sub_sop'      => 'Sub-SOP',
            'notification' => 'Thông báo',
            'wait'         => 'Chờ',
            default        => $step['step_type'],
          } }}
        </span>
      </td>
      <td>
        <strong>{{ $step['title'] }}</strong>
        @if(!empty($step['warning_note']))
        <div style="color:#dc2626;font-size:8px;margin-top:2px;">⚠ {{ $step['warning_note'] }}</div>
        @endif
        @if($step['step_type'] === 'sub_sop' && !empty($step['ref_sop_code']))
        <div style="color:#0891b2;font-size:8px;margin-top:2px;">↗ {{ $step['ref_sop_code'] }}: {{ $step['ref_sop_title'] ?? '' }}</div>
        @endif
      </td>
      <td>
        {{ $step['description'] ?? '' }}
        @if(!empty($step['expected_output']))
        <div style="color:#64748b;margin-top:3px;font-size:8px;">→ {{ $step['expected_output'] }}</div>
        @endif
      </td>
      <td>
        @foreach(collect($step['raci'] ?? []) as $r)
        @php $r = (array)$r; @endphp
        <span class="raci-badge raci-{{ $r['raci_type'] }}">{{ $r['raci_type'] }}</span>
        <span style="font-size:8px;color:#374151;">{{ $r['assignee_name'] ?? '' }}</span><br>
        @endforeach
      </td>
      <td style="text-align:center;color:#64748b;">
        {{ $step['duration_minutes'] ? $step['duration_minutes'] . ' ph' : '—' }}
      </td>
    </tr>
    @endforeach
  </tbody>
</table>

{{-- Summary row --}}
@php
  $totalDuration  = collect($steps)->sum(fn($s) => ((array)$s)['duration_minutes'] ?? 0);
  $mandatoryCount = collect($steps)->where('is_mandatory', true)->count();
@endphp
<table style="width:100%;margin-top:6px;">
  <tr>
    <td style="font-size:9px;color:#64748b;">
      Tổng: <strong>{{ $steps->count() }}</strong> bước
      · <strong>{{ $mandatoryCount }}</strong> bắt buộc
      @if($totalDuration > 0)
      · Tổng thời gian: <strong>{{ $totalDuration }}</strong> phút
      @endif
    </td>
  </tr>
</table>
@endif

{{-- Footer --}}
<div class="footer">
  Xuất ngày {{ now()->format('d/m/Y H:i') }} · {{ config('app.name') }}
  @if($sop->code) · {{ $sop->code }}@endif
  @if($sop->version > 0) · v{{ $sop->version }}@endif
</div>

</body>
</html>
