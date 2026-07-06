<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Báo cáo Năng lực số — {{ $orgName }}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'DejaVu Sans', 'Helvetica Neue', Arial, sans-serif; font-size: 11px; color: #1e293b; background: #fff; line-height: 1.5; }

  /* ── Layout ── */
  .page { padding: 28px 32px; }
  .page-break { page-break-before: always; padding: 28px 32px; }

  /* ── Header ── */
  .report-header { background: linear-gradient(135deg, #1e40af 0%, #6d28d9 100%); color: #fff; padding: 24px 28px; border-radius: 10px; margin-bottom: 20px; }
  .report-header h1 { font-size: 20px; font-weight: 700; letter-spacing: -0.3px; }
  .report-header .subtitle { font-size: 12px; opacity: .75; margin-top: 4px; }
  .report-header .meta { display: flex; gap: 24px; margin-top: 14px; font-size: 10px; opacity: .85; }
  .report-header .meta span { display: flex; align-items: center; gap: 4px; }

  /* ── Section titles ── */
  .section-title { font-size: 13px; font-weight: 700; color: #1e40af; border-bottom: 2px solid #bfdbfe; padding-bottom: 5px; margin: 18px 0 12px; text-transform: uppercase; letter-spacing: .5px; }

  /* ── KPI cards ── */
  .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 18px; }
  .kpi-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; }
  .kpi-card .label { font-size: 9.5px; color: #64748b; text-transform: uppercase; letter-spacing: .4px; }
  .kpi-card .value { font-size: 22px; font-weight: 700; color: #1e293b; margin-top: 2px; }
  .kpi-card .unit { font-size: 11px; color: #94a3b8; margin-left: 2px; }

  /* ── Two-col layout ── */
  .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px; }
  .card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 16px; }
  .card-title { font-size: 11px; font-weight: 600; color: #475569; margin-bottom: 10px; }

  /* ── Bar rows ── */
  .bar-row { display: flex; align-items: center; gap: 8px; margin-bottom: 7px; font-size: 10px; }
  .bar-row .bar-label { width: 110px; shrink: 0; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .bar-row .bar-track { flex: 1; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; }
  .bar-row .bar-fill { height: 8px; border-radius: 4px; }
  .bar-row .bar-value { width: 34px; text-align: right; font-weight: 600; color: #334155; }
  .bar-row .bar-pct { width: 28px; text-align: right; color: #94a3b8; }

  /* ── Maturity badge colours ── */
  .badge { display: inline-block; padding: 1px 7px; border-radius: 20px; font-size: 9px; font-weight: 600; }
  .badge-beginner    { background:#f1f5f9; color:#64748b; }
  .badge-aware       { background:#dbeafe; color:#1d4ed8; }
  .badge-practitioner{ background:#fef3c7; color:#92400e; }
  .badge-professional{ background:#dcfce7; color:#166534; }
  .badge-leader      { background:#f3e8ff; color:#6b21a8; }

  /* ── Data table ── */
  .data-table { width: 100%; border-collapse: collapse; font-size: 9.5px; }
  .data-table th { background: #1e40af; color: #fff; padding: 6px 8px; text-align: left; font-size: 9px; font-weight: 600; letter-spacing: .3px; }
  .data-table th.num { text-align: right; }
  .data-table td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
  .data-table td.num { text-align: right; color: #334155; font-weight: 500; }
  .data-table tr:nth-child(even) td { background: #f8fafc; }
  .data-table tr:hover td { background: #eff6ff; }

  /* ── Gap table colours ── */
  .gap-ok   { color: #16a34a; font-weight: 600; }
  .gap-warn { color: #d97706; font-weight: 600; }
  .gap-crit { color: #dc2626; font-weight: 600; }

  /* ── Radar (SVG) ── */
  .radar-wrap { display: flex; justify-content: center; margin: 8px 0; }

  /* ── Footer ── */
  .report-footer { margin-top: 24px; padding-top: 10px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #94a3b8; display: flex; justify-content: space-between; }
</style>
</head>
<body>

{{-- ═══════════════════════════════════════════════
     PAGE 1 — Tổng quan
═══════════════════════════════════════════════ --}}
<div class="page">

  {{-- Header --}}
  <div class="report-header">
    <h1>Báo cáo Năng lực số Tổ chức</h1>
    <div class="subtitle">Thai Digital Workforce Competency Framework (TDWCF) · Phiên bản {{ now()->format('d/m/Y') }}</div>
    <div class="meta">
      <span>🏢 {{ $orgName }}</span>
      <span>📅 Ngày xuất: {{ now()->format('d/m/Y H:i') }}</span>
      <span>👥 Tổng hồ sơ: {{ $total }}</span>
    </div>
  </div>

  {{-- KPI Cards --}}
  <div class="section-title">Chỉ số tổng hợp</div>
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="label">Điểm TDWCF TB</div>
      <div class="value">{{ number_format($avgTdwcf, 1) }}<span class="unit">/100</span></div>
    </div>
    <div class="kpi-card">
      <div class="label">AI Readiness TB</div>
      <div class="value">{{ number_format($avgAi, 1) }}<span class="unit">/100</span></div>
    </div>
    <div class="kpi-card">
      <div class="label">Trust Score TB</div>
      <div class="value">{{ number_format($avgTrust, 1) }}<span class="unit">/100</span></div>
    </div>
    <div class="kpi-card">
      <div class="label">Tổng hồ sơ</div>
      <div class="value">{{ $total }}<span class="unit">người</span></div>
    </div>
  </div>

  {{-- Two-col: maturity distribution + domain averages --}}
  <div class="two-col">

    {{-- Maturity distribution --}}
    <div class="card">
      <div class="card-title">Phân bổ cấp độ trưởng thành</div>
      @php
        $distColors = [
          'DIGITAL_BEGINNER'     => '#94a3b8',
          'DIGITAL_AWARE'        => '#38bdf8',
          'DIGITAL_PRACTITIONER' => '#fbbf24',
          'DIGITAL_PROFESSIONAL' => '#34d399',
          'DIGITAL_LEADER'       => '#a78bfa',
        ];
      @endphp
      @foreach($levelDistribution as $level => $data)
      <div class="bar-row">
        <div class="bar-label">{{ $data['label'] }}</div>
        <div class="bar-track">
          <div class="bar-fill" style="width:{{ $data['pct'] }}%;background:{{ $distColors[$level] }};"></div>
        </div>
        <div class="bar-value">{{ $data['count'] }}</div>
        <div class="bar-pct">{{ $data['pct'] }}%</div>
      </div>
      @endforeach
    </div>

    {{-- Domain averages --}}
    <div class="card">
      <div class="card-title">Điểm trung bình 6 năng lực</div>
      @php
        $domainColors = ['#3b82f6','#6366f1','#8b5cf6','#a855f7','#d946ef','#ec4899'];
        $i = 0;
      @endphp
      @foreach($domainAvgs as $label => $avg)
      <div class="bar-row">
        <div class="bar-label">{{ $label }}</div>
        <div class="bar-track">
          <div class="bar-fill" style="width:{{ min($avg,100) }}%;background:{{ $domainColors[$i] }};opacity:.8;"></div>
        </div>
        <div class="bar-value">{{ number_format($avg, 1) }}</div>
      </div>
      @php $i++ @endphp
      @endforeach
    </div>

  </div>

  {{-- Leaderboard top 5 --}}
  <div class="section-title">Top 5 — Workforce Trust Score</div>
  <table class="data-table">
    <thead>
      <tr>
        <th style="width:30px">#</th>
        <th>Họ tên</th>
        <th>Chức danh</th>
        <th class="num">TDWCF</th>
        <th class="num">AI Readiness</th>
        <th class="num">Trust Score</th>
        <th>Cấp độ</th>
      </tr>
    </thead>
    <tbody>
      @foreach($leaderboard as $i => $p)
      @php
        $badgeClass = [
          'DIGITAL_BEGINNER'     => 'badge-beginner',
          'DIGITAL_AWARE'        => 'badge-aware',
          'DIGITAL_PRACTITIONER' => 'badge-practitioner',
          'DIGITAL_PROFESSIONAL' => 'badge-professional',
          'DIGITAL_LEADER'       => 'badge-leader',
        ][$p->tdwcf_maturity_level] ?? 'badge-beginner';
        $levelLabels = [
          'DIGITAL_BEGINNER'=>'Khởi đầu','DIGITAL_AWARE'=>'Nhận thức',
          'DIGITAL_PRACTITIONER'=>'Thực hành','DIGITAL_PROFESSIONAL'=>'Chuyên nghiệp','DIGITAL_LEADER'=>'Dẫn dắt'
        ];
      @endphp
      <tr>
        <td class="num">{{ $i + 1 }}</td>
        <td>{{ $p->employee?->full_name ?? '—' }}</td>
        <td>{{ $p->employee?->snap_job_title ?? '—' }}</td>
        <td class="num">{{ number_format($p->tdwcf_score, 1) }}</td>
        <td class="num">{{ number_format($p->ai_readiness_score ?? 0, 1) }}</td>
        <td class="num" style="color:#1e40af;font-weight:700">{{ number_format($p->workforce_trust_score ?? 0, 1) }}</td>
        <td><span class="badge {{ $badgeClass }}">{{ $levelLabels[$p->tdwcf_maturity_level] ?? '—' }}</span></td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <div class="report-footer">
    <span>Workforce Digital Twin Platform · Bảo mật nội bộ</span>
    <span>Trang 1 / 2</span>
  </div>
</div>

{{-- ═══════════════════════════════════════════════
     PAGE 2 — Danh sách đầy đủ + Skill Gap
═══════════════════════════════════════════════ --}}
<div class="page-break">

  <div class="section-title">Danh sách đầy đủ — {{ $total }} nhân viên</div>
  <table class="data-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Họ tên</th>
        <th>Chức danh</th>
        <th class="num">D1</th>
        <th class="num">D2</th>
        <th class="num">D3</th>
        <th class="num">D4</th>
        <th class="num">D5</th>
        <th class="num">D6</th>
        <th class="num">TDWCF</th>
        <th class="num">Trust</th>
        <th>Cấp độ</th>
      </tr>
    </thead>
    <tbody>
      @foreach($profiles as $i => $p)
      @php
        $badgeClass = [
          'DIGITAL_BEGINNER'     => 'badge-beginner',
          'DIGITAL_AWARE'        => 'badge-aware',
          'DIGITAL_PRACTITIONER' => 'badge-practitioner',
          'DIGITAL_PROFESSIONAL' => 'badge-professional',
          'DIGITAL_LEADER'       => 'badge-leader',
        ][$p->tdwcf_maturity_level] ?? 'badge-beginner';
        $lv = ['DIGITAL_BEGINNER'=>'Khởi đầu','DIGITAL_AWARE'=>'Nhận thức',
               'DIGITAL_PRACTITIONER'=>'Thực hành','DIGITAL_PROFESSIONAL'=>'Chuyên nghiệp','DIGITAL_LEADER'=>'Dẫn dắt'];
      @endphp
      <tr>
        <td class="num">{{ $i + 1 }}</td>
        <td>{{ $p->employee?->full_name ?? '—' }}</td>
        <td>{{ $p->employee?->snap_job_title ?? '—' }}</td>
        <td class="num">{{ number_format($p->score_d1_digital_literacy ?? 0, 0) }}</td>
        <td class="num">{{ number_format($p->score_d2_data_literacy    ?? 0, 0) }}</td>
        <td class="num">{{ number_format($p->score_d3_ai_literacy      ?? 0, 0) }}</td>
        <td class="num">{{ number_format($p->score_d4_workflow         ?? 0, 0) }}</td>
        <td class="num">{{ number_format($p->score_d5_innovation       ?? 0, 0) }}</td>
        <td class="num">{{ number_format($p->score_d6_performance      ?? 0, 0) }}</td>
        <td class="num" style="font-weight:700;color:#1e40af">{{ number_format($p->tdwcf_score ?? 0, 1) }}</td>
        <td class="num">{{ number_format($p->workforce_trust_score ?? 0, 1) }}</td>
        <td><span class="badge {{ $badgeClass }}">{{ $lv[$p->tdwcf_maturity_level] ?? '—' }}</span></td>
      </tr>
      @endforeach
    </tbody>
  </table>

  {{-- Skill gap summary --}}
  <div class="section-title" style="margin-top:22px">Phân tích Skill Gap theo vị trí</div>
  <table class="data-table">
    <thead>
      <tr>
        <th>Họ tên</th>
        <th>Chức danh</th>
        <th class="num">D1</th>
        <th class="num">D2</th>
        <th class="num">D3</th>
        <th class="num">D4</th>
        <th class="num">D5</th>
        <th class="num">D6</th>
        <th class="num">Tổng gap</th>
      </tr>
    </thead>
    <tbody>
      @foreach($skillGaps as $row)
      <tr>
        <td>{{ $row['name'] }}</td>
        <td>{{ $row['job_title'] }}</td>
        @foreach(['D1','D2','D3','D4','D5','D6'] as $dc)
        @php $g = $row['gaps'][$dc] ?? 0; @endphp
        <td class="num {{ $g > 15 ? 'gap-crit' : ($g > 0 ? 'gap-warn' : 'gap-ok') }}">
          {{ $g > 0 ? '-'.number_format($g,0) : '✓' }}
        </td>
        @endforeach
        <td class="num {{ $row['total_gap'] > 0 ? 'gap-crit' : 'gap-ok' }}">
          {{ $row['total_gap'] > 0 ? $row['total_gap'] : '✓' }}
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <div class="report-footer">
    <span>Workforce Digital Twin Platform · Bảo mật nội bộ</span>
    <span>Trang 2 / 2</span>
  </div>
</div>

</body>
</html>
