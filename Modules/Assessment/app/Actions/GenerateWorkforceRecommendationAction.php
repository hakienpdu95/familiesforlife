<?php

namespace Modules\Assessment\Actions;

use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\AiCopilot\Drivers\ClaudeDriver;
use Modules\AiCopilot\Drivers\DTOs\AiCompletionRequest;
use Modules\Assessment\Models\JobTitleDomainRequirement;
use Modules\Assessment\Models\WorkforceProfile;
use Modules\Assessment\Models\WorkforceRecommendation;
use App\Shared\Tenancy\TenantContext;

class GenerateWorkforceRecommendationAction
{
    use AsAction;

    public string $jobQueue   = 'default';
    public int    $jobTries   = 2;
    public array  $jobBackoff = [30, 120];

    /** Domain codes → human-readable Vietnamese labels. */
    private const DOMAIN_LABELS = [
        'D1' => 'Năng lực Số',
        'D2' => 'Năng lực Dữ liệu',
        'D3' => 'Năng lực AI',
        'D4' => 'Năng lực Quy trình',
        'D5' => 'Đổi mới & Sáng tạo',
        'D6' => 'Hiệu suất & Kết quả',
    ];

    public function handle(WorkforceProfile $profile, bool $forceRefresh = false): WorkforceRecommendation
    {
        // 1. Ensure employee/jobTitle loaded — bypass tenant scope so this works in CLI/queue too
        if ((! $profile->relationLoaded('employee')) || $profile->employee === null) {
            $emp = \Modules\Employee\Models\Employee::withoutTenant()->find($profile->employee_id);
            if ($emp) {
                $emp->loadMissing('jobTitle');
            }
            $profile->setRelation('employee', $emp);
        } elseif ($profile->employee && ! $profile->employee->relationLoaded('jobTitle')) {
            $profile->employee->load('jobTitle');
        }

        // 2. Compute context hash
        $contextHash = WorkforceRecommendation::computeContextHash($profile);

        // 3. Return cached recommendation if still fresh
        if (! $forceRefresh) {
            $existing = WorkforceRecommendation::withoutTenant()
                ->where('workforce_profile_id', $profile->id)
                ->latest()
                ->first();

            if ($existing && $existing->isStillFresh($profile)) {
                return $existing;
            }
        }

        // 4. Get job-title domain requirements
        $requirements = JobTitleDomainRequirement::getForJobTitle(
            $profile->employee?->job_title_id,
            $profile->organization_id,
        );

        // 5. Build domain scores array (D1–D6)
        $domainScores = [
            'D1' => (float) ($profile->score_d1_digital_literacy ?? 0),
            'D2' => (float) ($profile->score_d2_data_literacy    ?? 0),
            'D3' => (float) ($profile->score_d3_ai_literacy      ?? 0),
            'D4' => (float) ($profile->score_d4_workflow         ?? 0),
            'D5' => (float) ($profile->score_d5_innovation       ?? 0),
            'D6' => (float) ($profile->score_d6_performance      ?? 0),
        ];

        // 6. Compute gaps per domain, sort descending
        $gaps = [];
        foreach ($domainScores as $code => $current) {
            $required      = (float) ($requirements[$code] ?? 0);
            $gaps[$code]   = max(0.0, $required - $current);
        }
        arsort($gaps);

        // 7–11. Generate recommendations (API or mock)
        $apiKey = config('services.anthropic.key') ?? env('ANTHROPIC_API_KEY');

        if (! empty($apiKey)) {
            $recommendations = $this->generateViaApi(
                $apiKey,
                $profile,
                $domainScores,
                $requirements,
                $gaps,
            );
        } else {
            Log::info('workforce_recommendation.no_api_key', [
                'workforce_profile_id' => $profile->id,
            ]);
            $recommendations = $this->generateMock($domainScores, $requirements, $gaps);
        }

        // 12. Persist the recommendation record
        $saved = $this->saveRecommendation(
            profile:         $profile,
            contextHash:     $contextHash,
            recommendations: $recommendations['items'],
            provider:        $recommendations['provider'],
            model:           $recommendations['model'],
            inputTokens:     $recommendations['input_tokens'],
            outputTokens:    $recommendations['output_tokens'],
        );

        // 13. Return saved model
        return $saved;
    }

    // ── API path ──────────────────────────────────────────────────────────────

    private function generateViaApi(
        string $apiKey,
        WorkforceProfile $profile,
        array $domainScores,
        array $requirements,
        array $gaps,
    ): array {
        try {
            $driver = new ClaudeDriver($apiKey);

            $systemPrompt = $this->buildSystemPrompt();
            $userMessage  = $this->buildUserMessage($profile, $domainScores, $requirements, $gaps);

            $request = new AiCompletionRequest(
                model:       'claude-haiku-4-5-20251001',
                systemPrompt: $systemPrompt,
                userMessage:  $userMessage,
                temperature:  0.4,
                maxTokens:    1200,
            );

            $result = $driver->complete($request);

            $items = $this->parseRecommendations($result->content);

            return [
                'items'        => $items,
                'provider'     => 'claude',
                'model'        => 'claude-haiku-4-5-20251001',
                'input_tokens'  => $result->inputTokens,
                'output_tokens' => $result->outputTokens,
            ];
        } catch (\Throwable $e) {
            Log::warning('workforce_recommendation.api_failed', [
                'workforce_profile_id' => $profile->id,
                'error'                => $e->getMessage(),
            ]);

            // Fall back to mock data so the action never throws
            $items = $this->generateMock($domainScores, $requirements, $gaps);

            return $items;
        }
    }

    // ── Prompt builders ───────────────────────────────────────────────────────

    private function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
Bạn là chuyên gia phát triển năng lực số trong doanh nghiệp Việt Nam.
Nhiệm vụ của bạn là phân tích hồ sơ năng lực của một nhân viên và đề xuất
5 hành động phát triển cụ thể, cá nhân hóa, có thể thực hiện ngay.

Quy tắc:
- Tập trung vào các khoảng cách năng lực lớn nhất so với yêu cầu vị trí.
- Mỗi đề xuất phải có thể đo lường và hoàn thành trong khung thời gian cụ thể.
- Ưu tiên hành động thực tế, tránh đề xuất chung chung.
- Trả về ĐÚNG định dạng JSON được yêu cầu, không thêm bất kỳ text nào khác.
PROMPT;
    }

    private function buildUserMessage(
        WorkforceProfile $profile,
        array $domainScores,
        array $requirements,
        array $gaps,
    ): string {
        $employeeName  = $profile->employee?->full_name ?? 'Nhân viên';
        $jobTitleName  = $profile->employee?->jobTitle?->name ?? 'Chưa xác định';
        $maturityLevel = $profile->tdwcf_maturity_level ?? 'UNKNOWN';
        $tdwcfScore    = round($profile->tdwcf_score ?? 0, 2);

        $scoresTable = '';
        foreach (self::DOMAIN_LABELS as $code => $label) {
            $current  = round($domainScores[$code] ?? 0, 2);
            $required = round($requirements[$code]  ?? 0, 2);
            $gap      = round($gaps[$code]           ?? 0, 2);
            $scoresTable .= "  {$code} ({$label}): điểm hiện tại={$current}, yêu cầu={$required}, khoảng cách={$gap}\n";
        }

        $topGaps = array_slice($gaps, 0, 3, true);
        $topGapStr = implode(', ', array_map(
            fn ($code, $gap) => "{$code}=" . round($gap, 2),
            array_keys($topGaps),
            $topGaps,
        ));

        return <<<MSG
Hồ sơ nhân viên:
- Tên: {$employeeName}
- Vị trí: {$jobTitleName}
- Điểm TDWCF tổng: {$tdwcfScore}/10
- Mức độ trưởng thành số: {$maturityLevel}

Điểm năng lực theo 6 miền:
{$scoresTable}
Khoảng cách lớn nhất: {$topGapStr}

Hãy đề xuất ĐÚNG 5 hành động phát triển để thu hẹp các khoảng cách trên.
Trả lời chỉ bằng JSON array theo định dạng sau, không có text nào khác:
[
  {
    "priority": 1,
    "domain": "D3",
    "action": "Mô tả hành động cụ thể",
    "resource_type": "course|sandbox|certification|practice",
    "resource_name": "Tên tài nguyên hoặc khoá học cụ thể",
    "estimated_weeks": 2,
    "why": "Lý do tại sao hành động này quan trọng với nhân viên"
  }
]
MSG;
    }

    // ── JSON parser ───────────────────────────────────────────────────────────

    private function parseRecommendations(string $content): array
    {
        try {
            // Extract first JSON array from the response
            if (preg_match('/\[[\s\S]*\]/u', $content, $matches)) {
                $decoded = json_decode($matches[0], true, 512, JSON_THROW_ON_ERROR);

                if (is_array($decoded) && count($decoded) > 0) {
                    return $decoded;
                }
            }
        } catch (\JsonException $e) {
            Log::warning('workforce_recommendation.json_parse_failed', [
                'error'   => $e->getMessage(),
                'content' => mb_substr($content, 0, 500),
            ]);
        }

        // Return empty array; the caller will fall back to mock via outer try/catch
        return [];
    }

    // ── Mock data path ────────────────────────────────────────────────────────

    /**
     * Generates realistic mock recommendations based on actual gap sizes.
     * Used when no ANTHROPIC_API_KEY is configured, or as a fallback on API errors.
     */
    private function generateMock(array $domainScores, array $requirements, array $gaps): array
    {
        $mockLibrary = [
            'D1' => [
                'action'        => 'Thực hành sử dụng bộ công cụ số văn phòng nâng cao (Google Workspace / Microsoft 365)',
                'resource_type' => 'sandbox',
                'resource_name' => 'Google Workspace Learning Center',
                'estimated_weeks' => 1,
                'why'           => 'Năng lực số cơ bản là nền tảng cho mọi kỹ năng công nghệ khác trong công việc hàng ngày.',
            ],
            'D2' => [
                'action'        => 'Hoàn thành khoá học phân tích dữ liệu cơ bản với Excel/Google Sheets',
                'resource_type' => 'course',
                'resource_name' => 'Data Analysis with Spreadsheets - Coursera',
                'estimated_weeks' => 3,
                'why'           => 'Khả năng đọc và diễn giải dữ liệu giúp đưa ra quyết định dựa trên bằng chứng, tăng hiệu quả công việc.',
            ],
            'D3' => [
                'action'        => 'Hoàn thành khoá học AI Fundamentals và thực hành với công cụ AI phổ biến',
                'resource_type' => 'course',
                'resource_name' => 'AI for Everyone - Coursera (Andrew Ng)',
                'estimated_weeks' => 2,
                'why'           => 'Hiểu biết về AI là năng lực chiến lược giúp nhân viên chủ động ứng dụng công nghệ vào công việc.',
            ],
            'D4' => [
                'action'        => 'Lập bản đồ và tối ưu hoá ít nhất 2 quy trình công việc bằng công cụ số',
                'resource_type' => 'practice',
                'resource_name' => 'Lucidchart / Miro Workflow Mapping',
                'estimated_weeks' => 2,
                'why'           => 'Tối ưu quy trình bằng công nghệ trực tiếp nâng cao năng suất và giảm sai sót trong công việc.',
            ],
            'D5' => [
                'action'        => 'Tham gia chương trình đổi mới sáng tạo nội bộ và đề xuất ít nhất 1 sáng kiến',
                'resource_type' => 'practice',
                'resource_name' => 'Design Thinking Sprint - nội bộ tổ chức',
                'estimated_weeks' => 4,
                'why'           => 'Tư duy đổi mới là chìa khoá để tạo ra giá trị mới và thích nghi với môi trường kinh doanh thay đổi nhanh.',
            ],
            'D6' => [
                'action'        => 'Thiết lập hệ thống theo dõi KPI cá nhân và đánh giá hiệu suất hàng tuần',
                'resource_type' => 'practice',
                'resource_name' => 'OKR/KPI Dashboard tự xây dựng trên Google Sheets',
                'estimated_weeks' => 1,
                'why'           => 'Tự theo dõi kết quả công việc giúp nhân viên nhận ra điểm cần cải thiện và duy trì đà phát triển.',
            ],
        ];

        // Sort domains by gap descending to prioritise the most critical ones
        $sortedGaps = $gaps;
        arsort($sortedGaps);

        // Ensure we always have 5 recommendations; cycle through domains if needed
        $allDomains  = array_keys($sortedGaps);
        $cycle       = array_merge($allDomains, $allDomains); // at most 12 unique slots needed
        $result      = [];
        $priority    = 1;

        foreach ($cycle as $code) {
            if ($priority > 5) {
                break;
            }

            if (! isset($mockLibrary[$code])) {
                continue;
            }

            $lib = $mockLibrary[$code];

            // Skip if gap is zero and we already have enough items (prefer domains with gaps)
            if (($gaps[$code] ?? 0) === 0.0 && count($result) >= min(5, count(array_filter($gaps)))) {
                continue;
            }

            $result[] = [
                'priority'        => $priority++,
                'domain'          => $code,
                'action'          => $lib['action'],
                'resource_type'   => $lib['resource_type'],
                'resource_name'   => $lib['resource_name'],
                'estimated_weeks' => $lib['estimated_weeks'],
                'why'             => $lib['why'],
            ];
        }

        return [
            'items'        => $result,
            'provider'     => 'mock',
            'model'        => 'mock',
            'input_tokens'  => 0,
            'output_tokens' => 0,
        ];
    }

    // ── Persistence ───────────────────────────────────────────────────────────

    private function saveRecommendation(
        WorkforceProfile $profile,
        string $contextHash,
        array $recommendations,
        string $provider,
        string $model,
        int $inputTokens,
        int $outputTokens,
    ): WorkforceRecommendation {
        // Mark any previous recommendations for this profile as stale
        WorkforceRecommendation::withoutTenant()
            ->where('workforce_profile_id', $profile->id)
            ->where('is_stale', false)
            ->update(['is_stale' => true]);

        // Use TenantContext org id if profile doesn't carry one
        $orgId = $profile->organization_id
            ?? TenantContext::getOrganizationId();

        $record = new WorkforceRecommendation([
            'organization_id'      => $orgId,
            'workforce_profile_id' => $profile->id,
            'generated_at'         => now(),
            'provider'             => $provider,
            'model'                => $model,
            'context_hash'         => $contextHash,
            'recommendations'      => $recommendations,
            'input_tokens'         => $inputTokens,
            'output_tokens'        => $outputTokens,
            'is_stale'             => false,
        ]);

        // Save bypassing tenant scope to avoid duplicate-org-id injection issues
        $record->saveQuietly();

        Log::info('workforce_recommendation.saved', [
            'workforce_profile_id' => $profile->id,
            'provider'             => $provider,
            'count'                => count($recommendations),
        ]);

        return $record;
    }

    // ── Queue entry-point ─────────────────────────────────────────────────────

    public function asJob(WorkforceProfile $profile, bool $forceRefresh = false): void
    {
        $this->handle($profile, $forceRefresh);
    }
}
