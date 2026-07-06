<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Hồ sơ Năng lực số — {{ $employee?->full_name ?? 'Employee' }}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'DejaVu Sans', 'Helvetica Neue', Arial, sans-serif; font-size: 11px; color: #1e293b; background: #fff; line-height: 1.5; }
  .page { padding: 28px 32px; }

  /* ── Header ── */
  .profile-header { display: flex; align-items: flex-start; gap: 20px; background: linear-gradient(135deg, #1e40af 0%, #6d28d9 100%); color:#fff; padding: 22px 26px; border-radius: 10px; margin-bottom: 18px; }
  .avatar { width: 56px; height: 56px; border-radius: 50%; background: rgba(255,255,255,.2); display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 700; flex-shrink: 0; }
  .profile-header h1 { font-size: 18px; font-weight: 700; }
  .profile-header .meta { font-size: 11px; opacity: .8; margin-top: 3px; }
  .profile-header .badges { display: flex; gap: 8px; margin-top: 8px; }
  .chip { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 9.5px; font-weight: 600; background: rgba(255,255,255,.2); }

  /* ── Section ── */
  .section-title { font-size: 12px; font-weight: 700; color: #1e40af; border-bottom: 2px solid #bfdbfe; padding-bottom: 4px; margin: 16px 0 10px; text-transform: uppercase; letter-spacing: .5px; }

  /* ── KPI row ── */
  .kpi-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin-bottom: 16px; }
  .kpi { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; }
  .kpi .label { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: .4px; }
  .kpi .value { font-size: 19px; font-weight: 700; color: #1e293b; margin-top: 1px; }
  .kpi .unit  { font-size: 10px; color: #94a3b8; }
  .kpi.highlight .value { color: #1e40af; }
  .kpi.ai .value { color: #7c3aed; }

  /* ── Radar SVG ── */
  .radar-container { display: flex; justify-content: center; margin: 8px 0 14px; }

  /* ── Domain cards ── */
  .domain-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 16px; }
  .domain-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; }
  .domain-card .d-code { font-size: 9px; font-weight: 700; color: #64748b; text-transform: uppercase; }
  .domain-card .d-name { font-size: 9.5px; color: #475569; margin-top: 1px; }
  .domain-card .d-score { font-size: 20px; font-weight: 700; color: #1e293b; margin: 4px 0 6px; }
  .domain-card .bar-track { height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden; position: relative; }
  .domain-card .bar-fill  { height: 6px; border-radius: 3px; }
  .domain-card .bar-req   { position: absolute; top: 0; height: 6px; width: 2px; background: #ef4444; border-radius: 1px; }
  .domain-card .gap-label { font-size: 9px; margin-top: 4px; }
  .gap-ok   { color: #16a34a; font-weight: 600; }
  .gap-warn { color: #d97706; }
  .gap-crit { color: #dc2626; font-weight: 600; }

  /* ── Trust breakdown ── */
  .trust-table { width: 100%; border-collapse: collapse; font-size: 10px; }
  .trust-table th { background: #1e40af; color: #fff; padding: 5px 8px; text-align: left; font-size: 9px; }
  .trust-table th.num { text-align: right; }
  .trust-table td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; }
  .trust-table td.num { text-align: right; font-weight: 600; }

  /* ── Recommendations ── */
  .rec-item { background: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #6d28d9; border-radius: 6px; padding: 9px 12px; margin-bottom: 7px; }
  .rec-item .rec-header { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
  .rec-item .priority { background: #1e40af; color: #fff; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: 700; flex-shrink: 0; }
  .rec-item .domain-badge { background: #ede9fe; color: #5b21b6; padding: 1px 7px; border-radius: 10px; font-size: 9px; font-weight: 600; }
  .rec-item .resource-type { background: #f0fdf4; color: #166534; padding: 1px 7px; border-radius: 10px; font-size: 9px; }
  .rec-item .action-text { font-size: 10px; font-weight: 600; color: #1e293b; flex: 1; }
  .rec-item .resource-name { font-size: 9.5px; color: #475569; }
  .rec-item .why-text { font-size: 9px; color: #64748b; margin-top: 3px; font-style: italic; }
  .rec-item .weeks { font-size: 9px; color: #94a3b8; }

  /* ── Certs ── */
  .cert-item { display: flex; align-items: center; gap: 8px; padding: 6px 10px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; margin-bottom: 5px; font-size: 10px; }
  .cert-name { font-weight: 600; color: #166534; flex: 1; }
  .cert-date { color: #64748b; font-size: 9px; }

  /* ── Footer ── */
  .report-footer { margin-top: 22px; padding-top: 9px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #94a3b8; display: flex; justify-content: space-between; }
</style>
</head>
<body>
<div class="page">

  {{-- Profile header --}}
  <div class="profile-header">
    <div class="avatar">{{ strtoupper(substr($employee?->full_name ?? 'U', 0, 1)) }}</div>
    <div>
      <h1>{{ $employee?->full_name ?? '—' }}</h1>
      <div class="meta">{{ $employee?->snap_job_title ?? '' }}{{ $employee?->snap_dept_name ? ' · '.$employee->snap_dept_name : '' }}</div>
      <div class="badges">
        <span class="chip">{{ $levelLabel }}</span>
        <span class="chip">TDWCF {{ number_format($profile->tdwcf_score ?? 0, 1) }}</span>
        <span class="chip">AI Readiness {{ number_format($profile->ai_readiness_score ?? 0, 1) }}</span>
        @if($cgi !== null)
        <span class="chip">CGI {{ $cgi >= 0 ? '+' : '' }}{{ number_format($cgi, 1) }}%</span>
        @endif
      </div>
    </div>
  </div>

  {{-- KPI row --}}
  <div class="kpi-row">
    <div class="kpi highlight">
      <div class="label">TDWCF Score</div>
      <div class="value">{{ number_format($profile->tdwcf_score ?? 0, 1) }}<span class="unit">/100</span></div>
    </div>
    <div class="kpi ai">
      <div class="label">AI Readiness</div>
      <div class="value">{{ number_format($profile->ai_readiness_score ?? 0, 1) }}<span class="unit">/100</span></div>
    </div>
    <div class="kpi">
      <div class="label">Trust Score</div>
      <div class="value">{{ number_format($profile->workforce_trust_score ?? 0, 1) }}<span class="unit">/100</span></div>
    </div>
    <div class="kpi">
      <div class="label">Chứng chỉ</div>
      <div class="value">{{ $certCount }}<span class="unit">chứng chỉ</span></div>
    </div>
    @if($cgi !== null)
    <div class="kpi">
      <div class="label">CGI</div>
      <div class="value" style="color:{{ $cgi >= 0 ? '#16a34a' : '#dc2626' }}">{{ $cgi >= 0 ? '+' : '' }}{{ number_format($cgi, 1) }}<span class="unit">%</span></div>
    </div>
    @else
    <div class="kpi">
      <div class="label">Profile</div>
      <div class="value">{{ $profile->profile_completeness_pct ?? 0 }}<span class="unit">%</span></div>
    </div>
    @endif
  </div>

  {{-- Radar SVG --}}
  @php
    $rd = 72; $cx = 100; $cy = 100; $n = 6;
    $radarScores = [
      $profile->score_d1_digital_literacy ?? 0,
      $profile->score_d2_data_literacy    ?? 0,
      $profile->score_d3_ai_literacy      ?? 0,
      $profile->score_d4_workflow         ?? 0,
      $profile->score_d5_innovation       ?? 0,
      $profile->score_d6_performance      ?? 0,
    ];
    $radarColors = ['#3b82f6','#6366f1','#8b5cf6','#a855f7','#d946ef','#ec4899'];
    $anchors = ['middle','start','start','middle','end','end'];
    $dataPoints = []; $reqPoints = [];
    for ($i = 0; $i < $n; $i++) {
        $angle = M_PI * 2 * $i / $n - M_PI / 2;
        $score = min($radarScores[$i], 100);
        $req   = min($jobTitleRequirements['D'.($i+1)] ?? 0, 100);
        $dataPoints[] = ($cx + $rd * ($score / 100) * cos($angle)) . ',' . ($cy + $rd * ($score / 100) * sin($angle));
        $reqPoints[]  = ($cx + $rd * ($req  / 100) * cos($angle)) . ',' . ($cy + $rd * ($req  / 100) * sin($angle));
    }
    $dataPolygon = implode(' ', $dataPoints);
    $reqPolygon  = implode(' ', $reqPoints);
    $dLabels = ['D1','D2','D3','D4','D5','D6'];
    $dNames  = ['Số','Dữ liệu','AI','Quy trình','Đổi mới','Hiệu suất'];
  @endphp
  <div class="section-title">Radar Năng lực — TDWCF 6 miền</div>
  <div class="radar-container">
    <svg viewBox="0 0 200 200" width="200" height="200">
      @for($r = 1; $r <= 4; $r++)
      @php
        $pts = [];
        for ($i = 0; $i < $n; $i++) {
            $a = M_PI * 2 * $i / $n - M_PI / 2;
            $pts[] = ($cx + $rd * ($r/4) * cos($a)) . ',' . ($cy + $rd * ($r/4) * sin($a));
        }
      @endphp
      <polygon points="{{ implode(' ', $pts) }}" fill="none" stroke="#e2e8f0" stroke-width="0.8"/>
      @endfor
      @for($i = 0; $i < $n; $i++)
      @php $a = M_PI * 2 * $i / $n - M_PI / 2; @endphp
      <line x1="{{ $cx }}" y1="{{ $cy }}" x2="{{ $cx + $rd * cos($a) }}" y2="{{ $cy + $rd * sin($a) }}" stroke="#e2e8f0" stroke-width="0.8"/>
      @endfor
      @if(!empty(array_filter($reqPoints)))
      <polygon points="{{ $reqPolygon }}" fill="rgba(239,68,68,.08)" stroke="#ef4444" stroke-width="1" stroke-dasharray="3,2"/>
      @endif
      <polygon points="{{ $dataPolygon }}" fill="rgba(59,130,246,.18)" stroke="#3b82f6" stroke-width="1.5"/>
      @for($i = 0; $i < $n; $i++)
      @php
        $a = M_PI * 2 * $i / $n - M_PI / 2;
        $score = min($radarScores[$i], 100);
        $px = $cx + $rd * ($score / 100) * cos($a);
        $py = $cy + $rd * ($score / 100) * sin($a);
        $lx = $cx + ($rd + 14) * cos($a);
        $ly = $cy + ($rd + 14) * sin($a);
      @endphp
      <circle cx="{{ $px }}" cy="{{ $py }}" r="2.5" fill="{{ $radarColors[$i] }}"/>
      <text x="{{ $lx }}" y="{{ $ly + 3 }}" text-anchor="{{ $anchors[$i] }}" font-size="8" fill="#475569" font-weight="600">{{ $dLabels[$i] }}</text>
      @endfor
    </svg>
  </div>

  {{-- Domain cards --}}
  <div class="section-title">Chi tiết 6 năng lực</div>
  <div class="domain-grid">
    @php
      $domainItems = [
        ['D1','Năng lực số cơ bản',$profile->score_d1_digital_literacy??0,'#3b82f6'],
        ['D2','Năng lực dữ liệu',  $profile->score_d2_data_literacy??0,   '#6366f1'],
        ['D3','Năng lực AI',        $profile->score_d3_ai_literacy??0,     '#8b5cf6'],
        ['D4','Quy trình & TĐH',    $profile->score_d4_workflow??0,        '#a855f7'],
        ['D5','Đổi mới sáng tạo',   $profile->score_d5_innovation??0,      '#d946ef'],
        ['D6','Hiệu suất & KQ',     $profile->score_d6_performance??0,     '#ec4899'],
      ];
    @endphp
    @foreach($domainItems as [$code, $name, $score, $color])
    @php
      $req = $jobTitleRequirements[$code] ?? 0;
      $gap = max(0, $req - $score);
      $gapClass = $gap > 15 ? 'gap-crit' : ($gap > 0 ? 'gap-warn' : 'gap-ok');
    @endphp
    <div class="domain-card">
      <div class="d-code">{{ $code }}</div>
      <div class="d-name">{{ $name }}</div>
      <div class="d-score">{{ number_format($score, 0) }}<span style="font-size:11px;color:#94a3b8;font-weight:400">/100</span></div>
      <div class="bar-track">
        <div class="bar-fill" style="width:{{ min($score,100) }}%;background:{{ $color }};opacity:.7;"></div>
        @if($req > 0)
        <div class="bar-req" style="left:{{ min($req,100) }}%;"></div>
        @endif
      </div>
      <div class="gap-label {{ $gapClass }}">
        @if($gap > 0) Gap: -{{ number_format($gap,0) }} pts (yêu cầu {{ $req }})
        @else ✓ Đạt yêu cầu vị trí
        @endif
      </div>
    </div>
    @endforeach
  </div>

  {{-- Trust Score Breakdown --}}
  <div class="section-title">Workforce Trust Score — Phân tích thành phần</div>
  <table class="trust-table">
    <thead>
      <tr>
        <th>Thành phần</th>
        <th class="num">Trọng số</th>
        <th class="num">Điểm thô</th>
        <th class="num">Đóng góp</th>
      </tr>
    </thead>
    <tbody>
      @foreach($trustBreakdown as $t)
      <tr>
        <td>{{ $t['label'] }}</td>
        <td class="num">{{ $t['weight'] }}%</td>
        <td class="num">{{ number_format($t['raw'], 1) }}</td>
        <td class="num" style="color:#1e40af">{{ number_format($t['contribution'], 1) }}</td>
      </tr>
      @endforeach
      <tr style="background:#eff6ff">
        <td style="font-weight:700">TỔNG TRUST SCORE</td>
        <td class="num">100%</td>
        <td></td>
        <td class="num" style="color:#1e40af;font-size:13px;font-weight:700">{{ number_format($profile->workforce_trust_score ?? 0, 1) }}</td>
      </tr>
    </tbody>
  </table>

  {{-- Certifications --}}
  @if($certifications->count() > 0)
  <div class="section-title">Chứng chỉ đang hoạt động</div>
  @foreach($certifications->where('status','active') as $cert)
  <div class="cert-item">
    <span>🏅</span>
    <span class="cert-name">{{ $cert->definition?->name ?? $cert->cert_name ?? '—' }}</span>
    <span class="cert-date">Cấp: {{ $cert->issued_at?->format('d/m/Y') ?? '—' }}</span>
    @if($cert->expires_at)
    <span class="cert-date">HSD: {{ $cert->expires_at->format('d/m/Y') }}</span>
    @endif
  </div>
  @endforeach
  @endif

  {{-- AI Recommendations --}}
  @if($recommendation && !empty($recommendation->recommendations))
  <div class="section-title">AI Gợi ý phát triển</div>
  @foreach($recommendation->recommendations as $rec)
  <div class="rec-item">
    <div class="rec-header">
      <div class="priority">{{ $rec['priority'] }}</div>
      <span class="domain-badge">{{ $rec['domain'] }}</span>
      <span class="action-text">{{ $rec['action'] }}</span>
      <span class="resource-type">{{ match($rec['resource_type']??'') {
        'course' => 'Khoá học', 'sandbox' => 'Sandbox',
        'certification' => 'Chứng chỉ', 'practice' => 'Thực hành',
        default => $rec['resource_type'] ?? '—'
      } }}</span>
      <span class="weeks">{{ $rec['estimated_weeks'] ?? '' }}t</span>
    </div>
    <div class="resource-name">📚 {{ $rec['resource_name'] ?? '' }}</div>
    @if(!empty($rec['why']))
    <div class="why-text">{{ $rec['why'] }}</div>
    @endif
  </div>
  @endforeach
  @endif

  {{-- Goals --}}
  @if($profile->career_goal || $profile->current_learning_path)
  <div class="section-title">Mục tiêu và lộ trình</div>
  @if($profile->career_goal)
  <p style="font-size:10px;color:#475569;margin-bottom:5px"><strong>Mục tiêu nghề nghiệp:</strong> {{ $profile->career_goal }}</p>
  @endif
  @if($profile->current_learning_path)
  <p style="font-size:10px;color:#475569"><strong>Lộ trình học tập:</strong> {{ $profile->current_learning_path }}</p>
  @endif
  @endif

  <div class="report-footer">
    <span>Workforce Digital Twin Platform · Bảo mật nội bộ</span>
    <span>Xuất ngày {{ now()->format('d/m/Y H:i') }}</span>
  </div>

</div>
</body>
</html>
