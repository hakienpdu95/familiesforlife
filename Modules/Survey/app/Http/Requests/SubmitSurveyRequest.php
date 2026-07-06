<?php

namespace Modules\Survey\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Static validation cho POST /api/surveys/{slug}/submit.
 *
 * Chỉ kiểm tra cấu trúc và kiểu dữ liệu cơ bản của payload.
 * Validation động (field_key tồn tại, option hợp lệ, required, rule_min/max)
 * thực hiện trong SubmitSurveyAction dựa theo definition của survey.
 */
class SubmitSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'respondent_ref'       => ['nullable', 'string', 'max:190'],

            'answers'              => ['required', 'array', 'min:1', 'max:200'],
            'answers.*.field_key'  => ['required', 'string', 'max:100'],
            'answers.*.value'      => ['present'],
            'answers.*.other_text' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'answers.required'            => 'Cần có ít nhất một câu trả lời.',
            'answers.min'                 => 'Cần có ít nhất một câu trả lời.',
            'answers.*.field_key.required' => 'Mỗi câu trả lời phải có field_key.',
            'answers.*.value.present'     => 'Mỗi câu trả lời phải có value.',
        ];
    }

    /**
     * Lấy IP của respondent dưới dạng binary 16 bytes (INET6_ATON).
     * Trả null nếu không lấy được IP hợp lệ.
     */
    public function respondentIpBinary(): ?string
    {
        $ip = $this->ip();
        if ($ip === null) {
            return null;
        }

        // Chuẩn hóa IPv4 sang IPv4-mapped IPv6 (::ffff:x.x.x.x) để luôn có 16 bytes
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ip = '::ffff:' . $ip;
        }

        $binary = inet_pton($ip);

        return $binary !== false ? $binary : null;
    }

    /**
     * Map sang SurveyResponseData để truyền vào Action.
     */
    public function toResponseData(): \Modules\Survey\Data\SurveyResponseData
    {
        return \Modules\Survey\Data\SurveyResponseData::from([
            'respondent_ref' => $this->input('respondent_ref'),
            'respondent_ip'  => $this->respondentIpBinary(),
            'answers'        => $this->input('answers', []),
        ]);
    }
}
