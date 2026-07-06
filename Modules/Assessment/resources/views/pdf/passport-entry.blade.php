<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Competency Passport — {{ $entry->source_org_name ?? 'Export' }}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'DejaVu Sans', 'Helvetica Neue', Arial, sans-serif; font-size: 11px; color: #1e293b; background: #fff; line-height: 1.5; }
  .page { padding: 28px 32px; }

  /* ── Cover / Header ── */
  .cover { background: linear-gradient(135deg, #1e40af 0%, #6d28d9 100%); color: #fff; padding: 26px 30px; border-radius: 10px; margin-bottom: 18px; }
  .cover-top { display: flex; align-items: flex-start; gap: 16px; }
  .avatar { width: 52px; height: 52px; border-radius: 50%; background: rgba(255,255,255,.2); display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 700; flex-shrink: 0; }
  .cover-info h1 { font-size: 18px; font-weight: 700; margin-bottom: 2px; }
  .cover-meta { font-size: 10px; opacity: .75; }
  .cover-badges { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
  .chip { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 9px; font-weight: 600; background: rgba(255,255,255,.2); }
  .chip-green { background: rgba(52,211,153,.3); }
  .chip-yellow { background: rgba(251,191,36,.3); }

  .org-strip { background: rgba(255,255,255,.12); border-radius: 8px; padding: 12px 16px; margin-top: 16px; display: flex; align-items: center; gap: 12px; }
  .org-name { font-size: 13px; font-weight: 600; }
  .org-meta { font-size: 10px; opacity: .75; }

  /* ── Section ── */
  .section-title { font-size: 10px; font-weight: 700; color: #1e40af; border-bottom: 2px solid #bfdbfe; padding-bottom: 4px; margin: 16px 0 10px; text-transform: uppercase; letter-spacing: .5px; }

  /* ── KPI row ── */
  .kpi-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin-bottom: 16px; }
  .kpi { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 9px 10px; text-align: center; }
  .kpi .label { font-size: 8px; color: #64748b; text-transform: uppercase; letter-spacing: .4px; }
  .kpi .value { font-size: 18px; font-weight: 700; color: #1e40af; margin-top: 1px; line-height: 1; }
  .kpi .value.purple { color: #7c3aed; }
  .kpi .value.green  { color: #16a34a; }
  .kpi .value.teal   { color: #0891b2; }
  .kpi .sub { font-size: 8px; color: #94a3b8; margin-top: 2px; }

  /* ── Domain bars ── */
  .domain-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; margin-bottom: 14px; }
  .domain-row { display: flex; align-items: center; gap: 8px; }
  .domain-code { width: 22px; font-size: 9px; font-weight: 700; color: #64748b; flex-shrink: 0; }
  .domain-name-col { width: 100px; font-size: 9.5px; color: #475569; flex-shrink: 0; }
  .bar-wrap { flex: 1; }
  .bar-track { height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden; }
  .bar-fill { height: 6px; border-radius: 3px; background: linear-gradient(90deg, #3b82f6, #6366f1); }
  .domain-score { width: 28px; text-align: right; font-size: 10px; font-weight: 700; flex-shrink: 0; }

  /* ── Certs ── */
  .cert-item { display: flex; align-items: center; gap: 8px; padding: 5px 9px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 5px; margin-bottom: 5px; font-size: 9.5px; }
  .cert-name { font-weight: 600; color: #166534; flex: 1; }
  .cert-issuer { color: #64748b; }
  .cert-date { color: #94a3b8; white-space: nowrap; }

  /* ── Impact ── */
  .impact-item { background: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #6366f1; border-radius: 5px; padding: 7px 10px; margin-bottom: 6px; }
  .impact-title { font-size: 10px; font-weight: 600; color: #1e293b; }
  .impact-desc { font-size: 9px; color: #64748b; margin-top: 2px; }
  .impact-metric { display: inline-block; margin-top: 3px; background: #ede9fe; color: #5b21b6; padding: 1px 7px; border-radius: 10px; font-size: 8.5px; font-weight: 600; }

  /* ── Sandbox ── */
  .sandbox-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 7px; margin-bottom: 14px; }
  .sandbox-card { padding: 8px 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; }
  .sandbox-env { font-size: 9.5px; font-weight: 600; }
  .sandbox-meta { font-size: 8.5px; color: #64748b; margin-top: 2px; }

  /* ── Note ── */
  .note-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 10px 14px; font-size: 9.5px; color: #78350f; margin-bottom: 14px; }
  .note-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #92400e; margin-bottom: 4px; }

  /* ── Footer ── */
  .report-footer { margin-top: 24px; padding-top: 8px; border-top: 1px solid #e2e8f0; font-size: 8.5px; color: #94a3b8; display: flex; justify-content: space-between; }
</style>
</head>
<body>
<div class="page">

  {{-- Cover --}}
  <div class="cover">
    <div class="cover-top">
      <div class="avatar">{{ strtoupper(mb_substr($entry->user?->name ?? 'U', 0, 1)) }}</div>
      <div class="cover-info">
        <h1>{{ $ownerName ?? $entry->user?->name ?? 'Người dùng' }}</h1>
        <div class="cover-meta">Competency Passport · Snapshot: {{ $entry->snapshot_at?->format('d/m/Y') ?? '—' }}</div>
        <div class="cover-badges">
          @if(($entry->user?->trust_level ?? 0) >= 2)
            <span class="chip">📱 Điện thoại</span>
          @elseif(($entry->user?->trust_level ?? 0) >= 1)
            <span class="chip">✉ Email</span>
          @endif

          @if($entry->org_verified)
            <span class="chip chip-green">✓ {{ $entry->source_org_name }}</span>
          @endif

          @if($entry->has_late_offboard_gap)
            <span class="chip chip-yellow">⚠ Xác nhận muộn</span>
          @endif
        </div>
      </div>
    </div>

    <div class="org-strip">
      <div>
        <div class="org-name">{{ $entry->source_org_name ?? 'Tổ chức' }}</div>
        <div class="org-meta">
          {{ $entry->job_title_at_exit ?? '' }}
          @if($entry->department_at_exit) · {{ $entry->department_at_exit }} @endif
          @if($entry->tenure_start && $entry->tenure_end)
            · {{ $entry->tenure_start->format('m/Y') }} – {{ $entry->tenure_end->format('m/Y') }}
            @if($entry->tenure_months) ({{ $entry->tenure_months }} tháng) @endif
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- KPI --}}
  <div class="section-title">Điểm số năng lực</div>
  <div class="kpi-row">
    <div class="kpi">
      <div class="label">TDWCF</div>
      <div class="value">{{ $entry->tdwcf_score ? number_format($entry->tdwcf_score, 1) : '—' }}</div>
      @if($entry->tdwcf_maturity_level)<div class="sub">{{ $entry->tdwcf_maturity_level }}</div>@endif
    </div>
    <div class="kpi">
      <div class="label">Trust Score</div>
      <div class="value purple">{{ $entry->workforce_trust_score ? number_format($entry->workforce_trust_score, 1) : '—' }}</div>
    </div>
    <div class="kpi">
      <div class="label">Chứng nhận</div>
      <div class="value green">{{ $entry->certifications_count ?? 0 }}</div>
      @if($entry->highest_cert_level)<div class="sub">{{ $entry->highest_cert_level }}</div>@endif
    </div>
    <div class="kpi">
      <div class="label">Sandbox</div>
      <div class="value teal">{{ $entry->sandbox_hours_total ?? 0 }}h</div>
      @if($entry->sandbox_score_avg)<div class="sub">TB {{ number_format($entry->sandbox_score_avg, 1) }}</div>@endif
    </div>
    <div class="kpi">
      <div class="label">Impact</div>
      <div class="value">{{ $entry->impact_entries_count ?? 0 }}</div>
    </div>
  </div>

  {{-- Domain scores --}}
  @if($entry->domainScores->count())
  <div class="section-title">Năng lực 6 miền (D1–D6)</div>
  @php
    $domainNames = ['D1'=>'Digital Literacy','D2'=>'Data Literacy','D3'=>'AI Literacy','D4'=>'Workflow','D5'=>'Innovation','D6'=>'Performance'];
  @endphp
  <div class="domain-grid">
    @foreach($entry->domainScores->sortBy('domain_code') as $ds)
    <div class="domain-row">
      <div class="domain-code">{{ $ds->domain_code }}</div>
      <div class="domain-name-col">{{ $domainNames[$ds->domain_code] ?? $ds->domain_code }}</div>
      <div class="bar-wrap">
        <div class="bar-track">
          <div class="bar-fill" style="width: {{ min(100, $ds->score) }}%"></div>
        </div>
      </div>
      <div class="domain-score">{{ number_format($ds->score, 0) }}</div>
    </div>
    @endforeach
  </div>
  @endif

  {{-- Certifications --}}
  @php
    $certs = $entry->certifications->filter(fn($c) => !$c->expires_at || $c->expires_at->isFuture());
  @endphp
  @if($certs->count())
  <div class="section-title">Chứng nhận</div>
  @foreach($certs as $cert)
  <div class="cert-item">
    <div class="cert-name">{{ $cert->cert_name }}</div>
    <div class="cert-issuer">{{ $cert->cert_type_code }}</div>
    <div class="cert-date">
      {{ $cert->issued_at?->format('m/Y') ?? '' }}
      @if($cert->expires_at) → {{ $cert->expires_at->format('m/Y') }} @endif
    </div>
  </div>
  @endforeach
  @endif

  {{-- Top 5 impact highlights --}}
  @if($entry->impactHighlights->count())
  <div class="section-title">Impact nổi bật</div>
  @foreach($entry->impactHighlights->take(5) as $impact)
  <div class="impact-item">
    <div class="impact-title">{{ $impact->title }}</div>
    @if($impact->impact_type || $impact->period_label)
    <div class="impact-desc">{{ $impact->impact_type }}{{ $impact->period_label ? ' · '.$impact->period_label : '' }}</div>
    @endif
    @if($impact->improvement_pct)
    <span class="impact-metric">+{{ number_format($impact->improvement_pct, 1) }}%</span>
    @elseif($impact->roi_pct)
    <span class="impact-metric">ROI {{ number_format($impact->roi_pct, 1) }}%</span>
    @endif
  </div>
  @endforeach
  @endif

  {{-- Sandbox summaries --}}
  @if($entry->sandboxSummaries->count())
  <div class="section-title">Môi trường Sandbox</div>
  <div class="sandbox-grid">
    @foreach($entry->sandboxSummaries as $sb)
    <div class="sandbox-card">
      <div class="sandbox-env">{{ $sb->env_name ?? $sb->env_code }}</div>
      <div class="sandbox-meta">
        {{ $sb->sessions_completed }} buổi
        @if($sb->hours_spent) · {{ $sb->hours_spent }}h @endif
        @if($sb->avg_score) · TB {{ number_format($sb->avg_score, 1) }} @endif
      </div>
    </div>
    @endforeach
  </div>
  @endif

  {{-- Personal note (only in authenticated personal PDF, showNote=true) --}}
  @if(($showNote ?? false) && $entry->personal_note)
  <div class="section-title">Ghi chú cá nhân</div>
  <div class="note-box">
    <div class="note-label">Ghi chú của tôi</div>
    {{ $entry->personal_note }}
  </div>
  @endif

  {{-- Footer --}}
  <div class="report-footer">
    <span>Competency Passport · Minhan Platform</span>
    <span>{{ $entry->uuid }}</span>
    <span>Snapshot: {{ $entry->snapshot_at?->format('d/m/Y') ?? '—' }} · Xuất: {{ now()->format('d/m/Y') }}</span>
  </div>

</div>
</body>
</html>
