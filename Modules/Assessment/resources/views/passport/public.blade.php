<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
@if($noindex)
<meta name="robots" content="noindex, nofollow">
@endif
<title>{{ $entry->source_org_name ? 'Competency Passport · '.$entry->source_org_name : 'Competency Passport' }}</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Inter', system-ui, sans-serif; background: #f1f5f9; color: #1e293b; min-height: 100vh; line-height: 1.6; }
  a { color: inherit; text-decoration: none; }

  .wrapper { max-width: 780px; margin: 0 auto; padding: 32px 16px 64px; }

  /* ── Header card ── */
  .header-card { background: linear-gradient(135deg, #1e40af 0%, #6d28d9 100%); border-radius: 16px; padding: 32px; color: #fff; margin-bottom: 20px; }
  .header-top { display: flex; align-items: flex-start; gap: 20px; }
  .avatar { width: 64px; height: 64px; border-radius: 50%; background: rgba(255,255,255,.2); display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 700; flex-shrink: 0; }
  .header-info h1 { font-size: 22px; font-weight: 700; margin-bottom: 2px; }
  .header-meta { font-size: 13px; opacity: .75; }
  .badges { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
  .badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; background: rgba(255,255,255,.2); }
  .badge-verified { background: rgba(52,211,153,.25); }
  .badge-late { background: rgba(251,191,36,.25); }

  /* ── Org bar ── */
  .org-bar { background: rgba(255,255,255,.12); border-radius: 10px; padding: 14px 18px; margin-top: 20px; display: flex; align-items: center; gap: 14px; }
  .org-logo { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; }
  .org-logo-placeholder { width: 40px; height: 40px; border-radius: 8px; background: rgba(255,255,255,.2); display: flex; align-items: center; justify-content: center; font-size: 18px; }
  .org-name { font-size: 15px; font-weight: 600; }
  .org-meta { font-size: 12px; opacity: .75; }

  /* ── Section ── */
  .section { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 24px; margin-bottom: 16px; }
  .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: #64748b; margin-bottom: 16px; }

  /* ── KPI row ── */
  .kpi-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 12px; }
  .kpi { text-align: center; padding: 14px 10px; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0; }
  .kpi-val { font-size: 28px; font-weight: 700; color: #1e40af; line-height: 1; }
  .kpi-val.purple { color: #7c3aed; }
  .kpi-val.green  { color: #16a34a; }
  .kpi-val.teal   { color: #0891b2; }
  .kpi-label { font-size: 11px; color: #64748b; margin-top: 4px; }
  .kpi-sublabel { font-size: 10px; color: #94a3b8; margin-top: 2px; }

  /* ── Domain bars ── */
  .domain-list { display: flex; flex-direction: column; gap: 10px; }
  .domain-row { display: flex; align-items: center; gap: 12px; }
  .domain-code { width: 28px; font-size: 11px; font-weight: 700; color: #64748b; flex-shrink: 0; }
  .domain-name { width: 140px; font-size: 12px; color: #475569; flex-shrink: 0; }
  .bar-wrap { flex: 1; position: relative; }
  .bar-track { height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; }
  .bar-fill { height: 8px; border-radius: 4px; background: linear-gradient(90deg, #3b82f6, #6366f1); }
  .domain-score { width: 36px; text-align: right; font-size: 13px; font-weight: 700; color: #1e293b; flex-shrink: 0; }

  /* ── Certs ── */
  .cert-list { display: flex; flex-direction: column; gap: 8px; }
  .cert-item { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 8px; background: #f0fdf4; border: 1px solid #bbf7d0; }
  .cert-icon { font-size: 18px; flex-shrink: 0; }
  .cert-name { font-size: 13px; font-weight: 600; color: #166534; flex: 1; }
  .cert-issuer { font-size: 11px; color: #64748b; }
  .cert-date { font-size: 11px; color: #94a3b8; white-space: nowrap; }

  /* ── Impact highlights ── */
  .impact-list { display: flex; flex-direction: column; gap: 10px; }
  .impact-item { padding: 12px 16px; border-radius: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #6366f1; }
  .impact-title { font-size: 13px; font-weight: 600; color: #1e293b; }
  .impact-desc { font-size: 12px; color: #475569; margin-top: 4px; }
  .impact-metric { display: inline-block; margin-top: 6px; background: #ede9fe; color: #5b21b6; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }

  /* ── Sandbox ── */
  .sandbox-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; }
  .sandbox-card { padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; }
  .sandbox-env { font-size: 12px; font-weight: 600; color: #1e293b; }
  .sandbox-meta { font-size: 11px; color: #64748b; margin-top: 3px; }

  /* ── Action bar ── */
  .action-bar { display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 20px; }
  .btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; transition: opacity .15s; }
  .btn:hover { opacity: .85; }
  .btn-primary { background: #1e40af; color: #fff; }
  .btn-ghost { background: #fff; color: #475569; border: 1px solid #e2e8f0; }

  /* ── Footer ── */
  .footer { text-align: center; font-size: 11px; color: #94a3b8; margin-top: 32px; }
  .footer strong { color: #64748b; }

  .empty { text-align: center; padding: 20px; color: #94a3b8; font-size: 13px; }

  @media (max-width: 600px) {
    .header-card { padding: 20px; }
    .domain-name { width: 100px; }
    .kpi-val { font-size: 22px; }
  }
</style>
</head>
<body>
<div class="wrapper">

  {{-- Action bar --}}
  <div class="action-bar">
    <a href="{{ route('passport.public.pdf', $entry->share_token ?? $entry->uuid) }}" class="btn btn-primary">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
      Tải PDF
    </a>
  </div>

  {{-- Header --}}
  <div class="header-card">
    <div class="header-top">
      <div class="avatar">{{ strtoupper(mb_substr($entry->user?->name ?? 'U', 0, 1)) }}</div>
      <div class="header-info">
        <h1>{{ $entry->user?->name ?? 'Người dùng' }}</h1>
        <div class="header-meta">
          Competency Passport · Snapshot: {{ $entry->snapshot_at?->format('d/m/Y') ?? '—' }}
        </div>
        <div class="badges">
          @if(($entry->user?->trust_level ?? 0) >= 2)
            <span class="badge">📱 Điện thoại</span>
          @elseif(($entry->user?->trust_level ?? 0) >= 1)
            <span class="badge">✉ Email xác minh</span>
          @endif

          @if($entry->org_verified)
            <span class="badge badge-verified">
              ✓ Xác nhận bởi {{ $entry->source_org_name }}
            </span>
          @endif

          @if($entry->has_late_offboard_gap)
            <span class="badge badge-late">⚠ Xác nhận muộn</span>
          @endif
        </div>
      </div>
    </div>

    {{-- Org info --}}
    <div class="org-bar">
      @if($entry->source_org_logo_path)
        <img src="{{ Storage::url($entry->source_org_logo_path) }}" alt="" class="org-logo">
      @else
        <div class="org-logo-placeholder">🏢</div>
      @endif
      <div>
        <div class="org-name">{{ $entry->source_org_name ?? 'Tổ chức' }}</div>
        <div class="org-meta">
          {{ $entry->job_title_at_exit ?? '' }}
          @if($entry->department_at_exit) · {{ $entry->department_at_exit }} @endif
          · {{ $entry->tenure_start?->format('m/Y') ?? '?' }} – {{ $entry->tenure_end?->format('m/Y') ?? '?' }}
          @if($entry->tenure_months) ({{ $entry->tenure_months }} tháng) @endif
        </div>
      </div>
    </div>
  </div>

  {{-- KPI row --}}
  <div class="section">
    <div class="section-title">Điểm số năng lực</div>
    <div class="kpi-row">
      @if($entry->tdwcf_score)
      <div class="kpi">
        <div class="kpi-val">{{ number_format($entry->tdwcf_score, 1) }}</div>
        <div class="kpi-label">TDWCF Score</div>
        @if($entry->tdwcf_maturity_level)
        <div class="kpi-sublabel">{{ $entry->tdwcf_maturity_level }}</div>
        @endif
      </div>
      @endif

      @if($entry->workforce_trust_score)
      <div class="kpi">
        <div class="kpi-val purple">{{ number_format($entry->workforce_trust_score, 1) }}</div>
        <div class="kpi-label">Trust Score</div>
      </div>
      @endif

      @if($entry->certifications_count > 0)
      <div class="kpi">
        <div class="kpi-val green">{{ $entry->certifications_count }}</div>
        <div class="kpi-label">Chứng nhận</div>
        @if($entry->highest_cert_level)
        <div class="kpi-sublabel">Cao nhất: {{ $entry->highest_cert_level }}</div>
        @endif
      </div>
      @endif

      @if($entry->sandbox_hours_total > 0)
      <div class="kpi">
        <div class="kpi-val teal">{{ $entry->sandbox_hours_total }}</div>
        <div class="kpi-label">Giờ Sandbox</div>
        @if($entry->sandbox_score_avg)
        <div class="kpi-sublabel">TB: {{ number_format($entry->sandbox_score_avg, 1) }}</div>
        @endif
      </div>
      @endif

      @if($entry->impact_entries_count > 0)
      <div class="kpi">
        <div class="kpi-val">{{ $entry->impact_entries_count }}</div>
        <div class="kpi-label">Impact</div>
      </div>
      @endif
    </div>
  </div>

  {{-- Domain scores --}}
  @if($entry->domainScores->count())
  <div class="section">
    <div class="section-title">Năng lực 6 miền (D1–D6)</div>
    @php
      $domainNames = [
        'D1' => 'Digital Literacy',
        'D2' => 'Data Literacy',
        'D3' => 'AI Literacy',
        'D4' => 'Workflow',
        'D5' => 'Innovation',
        'D6' => 'Performance',
      ];
    @endphp
    <div class="domain-list">
      @foreach($entry->domainScores->sortBy('domain_code') as $ds)
      <div class="domain-row">
        <div class="domain-code">{{ $ds->domain_code }}</div>
        <div class="domain-name">{{ $domainNames[$ds->domain_code] ?? $ds->domain_code }}</div>
        <div class="bar-wrap">
          <div class="bar-track">
            <div class="bar-fill" style="width: {{ min(100, ($ds->score / 100) * 100) }}%"></div>
          </div>
        </div>
        <div class="domain-score">{{ number_format($ds->score, 0) }}</div>
      </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- Certifications (hide expired) --}}
  @php
    $activeCerts = $entry->certifications->filter(fn($c) => !$c->expires_at || $c->expires_at->isFuture());
  @endphp
  @if($activeCerts->count())
  <div class="section">
    <div class="section-title">Chứng nhận</div>
    <div class="cert-list">
      @foreach($activeCerts as $cert)
      <div class="cert-item">
        <div class="cert-icon">🏅</div>
        <div style="flex:1; min-width:0;">
          <div class="cert-name">{{ $cert->cert_name }}</div>
          <div class="cert-issuer">{{ $cert->cert_type_code }}</div>
        </div>
        <div class="cert-date">
          {{ $cert->issued_at?->format('m/Y') ?? '' }}
          @if($cert->expires_at) → {{ $cert->expires_at->format('m/Y') }} @endif
        </div>
      </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- Top 3 impact highlights --}}
  @if($entry->impactHighlights->count())
  <div class="section">
    <div class="section-title">Impact nổi bật</div>
    <div class="impact-list">
      @foreach($entry->impactHighlights->take(3) as $impact)
      <div class="impact-item">
        <div class="impact-title">{{ $impact->title }}</div>
        @if($impact->impact_type)
        <div class="impact-desc">{{ $impact->impact_type }}{{ $impact->period_label ? ' · '.$impact->period_label : '' }}</div>
        @endif
        @if($impact->improvement_pct)
        <span class="impact-metric">+{{ number_format($impact->improvement_pct, 1) }}%</span>
        @elseif($impact->roi_pct)
        <span class="impact-metric">ROI {{ number_format($impact->roi_pct, 1) }}%</span>
        @endif
      </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- Sandbox summaries --}}
  @if($entry->sandboxSummaries->count())
  <div class="section">
    <div class="section-title">Môi trường thực hành (Sandbox)</div>
    <div class="sandbox-list">
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
  </div>
  @endif

  {{-- Footer --}}
  <div class="footer">
    <p>Hồ sơ này được cấp bởi <strong>Minhan Platform</strong></p>
    <p style="margin-top:4px; font-family: monospace; font-size: 10px; color: #cbd5e1;">{{ $entry->uuid }}</p>
    <p style="margin-top:4px;">Snapshot: {{ $entry->snapshot_at?->format('d/m/Y H:i') ?? '—' }}</p>
  </div>

</div>
</body>
</html>
