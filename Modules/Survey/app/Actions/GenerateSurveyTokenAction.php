<?php

namespace Modules\Survey\Actions;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Survey\Data\TokenFormData;
use Modules\Survey\Models\Survey;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Survey\Models\SurveyToken;

class GenerateSurveyTokenAction
{
    use AsAction;

    /**
     * Tạo token mới cho survey.
     * Trả về plaintext DUY NHẤT 1 lần — không lưu vào DB.
     * DB chỉ lưu SHA-256 hash.
     *
     * @return array{ token: SurveyToken, plain: string }
     */
    public function handle(Survey $survey, TokenFormData $data): array
    {
        [$plain, $hashed] = $this->generateUniqueToken();
        $encrypted = Crypt::encryptString($plain);

        $token = new SurveyToken([
            'survey_id'       => $survey->id,
            'name'            => $data->name,
            'token_encrypted' => $encrypted,
            'is_active'       => true,
            'expires_at'      => $data->expires_at,
        ]);
        $token->token = $hashed;
        $token->save();

        ActivityLogger::info('Survey', 'token_created', $token, ['survey_id' => $survey->id, 'name' => $data->name]);

        return ['token' => $token, 'plain' => $plain];
    }

    /**
     * Sinh token random 64 ký tự, đảm bảo hash không trùng trong DB.
     * Xác suất collision cực thấp (SHA-256 trên 64 chars) — retry tối đa 3 lần.
     *
     * @return array{0: string, 1: string}  [plain, hashed]
     * @throws \RuntimeException nếu không tạo được token duy nhất sau 3 lần
     */
    private function generateUniqueToken(): array
    {
        $attempts = 0;

        do {
            if ($attempts >= 3) {
                throw new \RuntimeException('Không thể tạo token duy nhất sau 3 lần thử.');
            }

            $plain  = Str::random(64);
            $hashed = hash('sha256', $plain);
            $attempts++;
        } while (SurveyToken::where('token', $hashed)->exists());

        return [$plain, $hashed];
    }
}
