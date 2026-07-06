<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>{{ $sop->code }} — {{ $sop->title }}</title>
<style>
  /* ── Base ─────────────────────────────────────── */
  *, *::before, *::after { box-sizing: border-box; }
  body {
    margin: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
    font-size: 13px;
    color: #1a1a2e;
    background: #fff;
    padding: 32px 40px;
  }
  h1 { font-size: 22px; font-weight: 700; margin: 0 0 4px; }
  h2 { font-size: 14px; font-weight: 600; color: #374151; margin: 20px 0 8px; padding-bottom: 4px; border-bottom: 2px solid #e5e7eb; }
  a  { color: #2563eb; text-decoration: none; }

  /* ── Header ──────────────────────────────────── */
  .doc-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
  .doc-code   { font-family: monospace; font-size: 11px; color: #9ca3af; margin-bottom: 4px; }
  .badge {
    display: inline-block; padding: 2px 10px; border-radius: 99px;
    font-size: 11px; font-weight: 600;
  }
  .badge-draft    { background: #e5e7eb; color: #374151; }
  .badge-approved { background: #dcfce7; color: #166534; }
  .badge-pending  { background: #fef9c3; color: #854d0e; }
  .badge-rejected { background: #fee2e2; color: #991b1b; }
  .badge-archived { background: #f3f4f6; color: #6b7280; }

  /* ── Metadata grid ────────────────────────────── */
  .meta-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; }
  .meta-item label { display: block; font-size: 10px; color: #9ca3af; font-weight: 500; text-transform: uppercase; letter-spacing: .05em; }
  .meta-item span  { display: block; font-size: 12px; color: #1a1a2e; font-weight: 500; margin-top: 1px; }

  /* ── Flowchart ────────────────────────────────── */
  .flowchart-wrap { overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8f9fa; padding: 12px; margin-bottom: 20px; }
  .flowchart-wrap svg { display: block; max-width: 100%; height: auto; }

  /* ── Steps table ────────────────────────────────── */
  .steps-table { width: 100%; border-collapse: collapse; }
  .steps-table th { background: #f1f5f9; font-size: 11px; font-weight: 600; padding: 6px 10px; text-align: left; border: 1px solid #e2e8f0; color: #475569; }
  .steps-table td { padding: 6px 10px; border: 1px solid #e2e8f0; vertical-align: top; font-size: 12px; }
  .steps-table tr:nth-child(even) td { background: #f8fafc; }
  .type-chip { display: inline-block; padding: 1px 7px; border-radius: 3px; font-size: 10px; font-weight: 600; }
  .type-start, .type-end   { background: #E1F5EE; color: #1D9E75; }
  .type-action              { background: #E6F1FB; color: #378ADD; }
  .type-decision            { background: #FAEEDA; color: #EF9F27; }
  .type-sub_sop             { background: #E1F5EE; color: #1D9E75; }
  .type-notification        { background: #EEEDFE; color: #7F77DD; }
  .type-wait                { background: #F1EFE8; color: #888780; }
  .raci-badge { display: inline-block; padding: 1px 5px; border-radius: 2px; font-size: 10px; font-weight: 700; }
  .raci-R { background: #dbeafe; color: #1d4ed8; }
  .raci-A { background: #fef3c7; color: #92400e; }
  .raci-C { background: #d1fae5; color: #065f46; }
  .raci-I { background: #f3f4f6; color: #374151; }
  .raci-line { display: flex; align-items: center; gap: 4px; margin-bottom: 2px; }
  .warning-note { color: #dc2626; font-size: 11px; margin-top: 2px; }
  .sub-ref      { color: #0891b2; font-size: 11px; margin-top: 2px; }

  /* ── Footer ──────────────────────────────────── */
  .doc-footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; font-size: 11px; color: #9ca3af; }

  /* ── Print overrides ──────────────────────────── */
  @media print {
    body { padding: 12mm 14mm; }
    .no-print { display: none !important; }
    h2 { page-break-after: avoid; }
    tr  { page-break-inside: avoid; }
    .flowchart-wrap { page-break-inside: avoid; }
    .steps-table { page-break-before: auto; }
  }
</style>
</head>
<body>

{{-- Print toolbar (hidden in print) --}}
<div class="no-print" style="position:fixed;top:0;left:0;right:0;background:#1e293b;padding:8px 20px;display:flex;align-items:center;gap:12px;z-index:100;">
  <span style="color:#94a3b8;font-size:12px;flex:1;">{{ $sop->code }} — {{ $sop->title }}</span>
  <button onclick="window.print()" style="background:#3b82f6;color:#fff;border:none;padding:6px 16px;border-radius:6px;font-size:12px;cursor:pointer;font-weight:500;">
    In / Lưu PDF
  </button>
  <a href="{{ route('backend.sop.show', $sop) }}" style="color:#94a3b8;font-size:12px;text-decoration:none;">
    ← Quay lại
  </a>
</div>
<div class="no-print" style="height:44px;"></div>

{{-- Document header --}}
<div class="doc-header">
  <div>
    <div class="doc-code">{{ $sop->code }}</div>
    <h1>{{ $sop->title }}</h1>
    @if($sop->description)
    <p style="color:#64748b;font-size:12px;margin-top:4px;max-width:640px;">{{ $sop->description }}</p>
    @endif
  </div>
  <div style="text-align:right;flex-shrink:0;margin-left:24px;">
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
    <div style="font-size:11px;color:#9ca3af;margin-top:4px;">Phiên bản {{ $sop->version }}</div>
    @endif
    @if($sop->approved_at)
    <div style="font-size:11px;color:#9ca3af;">Duyệt {{ $sop->approved_at->format('d/m/Y') }}</div>
    @endif
  </div>
</div>

{{-- Metadata grid --}}
<div class="meta-grid">
  <div class="meta-item">
    <label>Loại</label>
    <span>{{ $sop->type?->label() ?? $sop->type }}</span>
  </div>
  <div class="meta-item">
    <label>Người phụ trách</label>
    <span>{{ $sop->owner?->name ?? '—' }}</span>
  </div>
  <div class="meta-item">
    <label>Chi nhánh</label>
    <span>{{ $sop->branch?->name ?? 'Toàn tổ chức' }}</span>
  </div>
  <div class="meta-item">
    <label>Phòng ban</label>
    <span>{{ $sop->department?->name ?? '—' }}</span>
  </div>
  @if($sop->effective_date)
  <div class="meta-item">
    <label>Ngày hiệu lực</label>
    <span>{{ $sop->effective_date->format('d/m/Y') }}</span>
  </div>
  @endif
  @if($sop->expired_date)
  <div class="meta-item">
    <label>Ngày hết hạn</label>
    <span>{{ $sop->expired_date->format('d/m/Y') }}</span>
  </div>
  @endif
  <div class="meta-item">
    <label>Tổng số bước</label>
    <span>{{ $steps->count() }}</span>
  </div>
  <div class="meta-item">
    <label>Tổng thời gian</label>
    <span>{{ $flowData['total_duration'] ? $flowData['total_duration'] . ' phút' : '—' }}</span>
  </div>
</div>

{{-- Flowchart SVG --}}
@if($steps->isNotEmpty())
<h2>Sơ đồ quy trình</h2>
<div class="flowchart-wrap">
  {!! $svg !!}
</div>
@endif

{{-- Steps detail table --}}
<h2>Chi tiết các bước</h2>

@if($steps->isEmpty())
  <p style="color:#9ca3af;font-style:italic;">Chưa có bước nào trong quy trình này.</p>
@else
<table class="steps-table">
  <thead>
    <tr>
      <th style="width:28px;">#</th>
      <th style="width:70px;">Loại</th>
      <th style="width:200px;">Tên bước</th>
      <th>Mô tả & Kết quả</th>
      <th style="width:160px;">Phân công RACI</th>
      <th style="width:50px;">Phút</th>
    </tr>
  </thead>
  <tbody>
    @foreach($steps as $step)
    @php $s = (array)$step; @endphp
    <tr>
      <td style="text-align:center;color:#9ca3af;font-weight:600;">{{ $s['position'] }}</td>
      <td>
        <span class="type-chip type-{{ $s['step_type'] }}">
          {{ match($s['step_type']) {
            'start'        => 'Bắt đầu',
            'end'          => 'Kết thúc',
            'action'       => 'Hành động',
            'decision'     => 'Quyết định',
            'sub_sop'      => 'Sub-SOP',
            'notification' => 'Thông báo',
            'wait'         => 'Chờ',
            default        => $s['step_type'],
          } }}
        </span>
      </td>
      <td>
        <strong>{{ $s['title'] }}</strong>
        @if(!empty($s['warning_note']))
        <div class="warning-note">⚠ {{ $s['warning_note'] }}</div>
        @endif
        @if($s['step_type'] === 'sub_sop' && !empty($s['ref_sop_code']))
        <div class="sub-ref">↗ {{ $s['ref_sop_code'] }}: {{ $s['ref_sop_title'] ?? '' }}</div>
        @endif
      </td>
      <td>
        @if(!empty($s['description']))<p style="margin-bottom:3px;">{{ $s['description'] }}</p>@endif
        @if(!empty($s['expected_output']))
        <p style="color:#64748b;font-size:11px;">→ {{ $s['expected_output'] }}</p>
        @endif
      </td>
      <td>
        @foreach(collect($s['raci'] ?? []) as $r)
        @php $r = (array)$r; @endphp
        <div class="raci-line">
          <span class="raci-badge raci-{{ $r['raci_type'] }}">{{ $r['raci_type'] }}</span>
          <span style="font-size:11px;">{{ $r['assignee_name'] ?? '' }}</span>
        </div>
        @endforeach
      </td>
      <td style="text-align:center;color:#64748b;">
        {{ $s['duration_minutes'] ?? '—' }}
      </td>
    </tr>
    @endforeach
  </tbody>
</table>
@endif

{{-- Footer --}}
<div class="doc-footer">
  <span>{{ config('app.name') }}</span>
  <span>{{ $sop->code }} @if($sop->version > 0) · v{{ $sop->version }} @endif · Xuất {{ now()->format('d/m/Y H:i') }}</span>
</div>

</body>
</html>
