<?php

namespace Modules\OcopSubject\Features\OcopSubjectManagement\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\OcopSubject\Enums\OcopSubjectOrganizationType;
use Modules\OcopSubject\Models\OcopSubject;

class OcopSubjectRequest extends FormRequest
{
    private const TAX_CODE_REGEX = '/^(\d{10}(-\d{3})?|\d{12})$/';

    private const GPS_REGEX = '/^(-?\d{1,2}(?:\.\d+)?)\s*,\s*(-?\d{1,3}(?:\.\d+)?)$/';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $mediaUuids = $this->input('media_uuids');

        $this->merge([
            'media_uuids' => is_string($mediaUuids) ? (json_decode($mediaUuids, true) ?: []) : ($mediaUuids ?? []),
            'remove_media_uuids' => $this->input('remove_media_uuids') ?? [],
            'tax_code' => preg_replace('/\s+/', '', (string) $this->input('tax_code')) ?: null,
            'gps_coordinates' => trim((string) $this->input('gps_coordinates')) ?: null,
        ]);
    }

    public function rules(): array
    {
        $ocopSubject = $this->route('ocop_subject');

        return [
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'tax_code' => [
                'nullable', 'string', 'regex:'.self::TAX_CODE_REGEX,
                Rule::unique('ocop_subjects', 'tax_code')->ignore($ocopSubject?->id)->withoutTrashed(),
            ],
            'organization_type' => ['nullable', Rule::enum(OcopSubjectOrganizationType::class)],
            'legal_representative' => ['nullable', 'string', 'max:150'],
            'position' => ['nullable', 'string', 'max:100'],

            'address' => ['nullable', 'string', 'max:255'],
            'province_code' => ['required', 'string', 'size:2', 'exists:provinces,province_code'],
            'ward_code' => [
                'required', 'string',
                Rule::exists('wards', 'ward_code')->where('province_code', (string) $this->input('province_code')),
            ],
            'gps_coordinates' => ['nullable', 'string', 'max:60', $this->gpsRule()],
            'factory_code' => ['nullable', 'string', 'max:50'],
            'is_food_business' => ['boolean'],

            'ocop_star' => [Rule::requiredIf($this->organizationType()?->requiresOcopDecision() ?? false), 'nullable', 'in:3,4,5'],
            'ocop_cert_expiry' => ['nullable', 'required_with:ocop_star', 'date'],

            'hotline' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+().\s-]{8,20}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'website' => ['nullable', 'url', 'max:255'],
            'story' => ['nullable', 'string'],
            'is_active' => ['boolean'],

            'media_uuids' => ['nullable', 'array', 'max:'.OcopSubject::MAX_IMAGES],
            'media_uuids.*' => ['string', 'uuid'],
            'remove_media_uuids' => ['nullable', 'array'],
            'remove_media_uuids.*' => ['string', 'uuid'],

            'documents' => ['nullable', 'array:'.implode(',', OcopSubject::DOCUMENT_COLLECTIONS)],
            'documents.*' => ['array', 'max:'.OcopSubject::MAX_DOCUMENTS],
            'documents.*.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:20480'],
        ];
    }

    public function after(): array
    {
        return [fn (Validator $validator) => $this->validateDocuments($validator)];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên chủ thể.',
            'name.max' => 'Tên chủ thể không được vượt quá :max ký tự.',
            'tax_code.regex' => 'Mã số định danh không hợp lệ — MST 10 số (hoặc 10 số kèm -XXX), hoặc CCCD 12 số với hộ kinh doanh.',
            'tax_code.unique' => 'Mã số định danh này đã được dùng cho một chủ thể khác.',
            'organization_type.enum' => 'Loại hình tổ chức không hợp lệ.',
            'province_code.required' => 'Vui lòng chọn tỉnh/thành.',
            'province_code.exists' => 'Tỉnh/thành được chọn không hợp lệ.',
            'ward_code.required' => 'Vui lòng chọn phường/xã.',
            'ward_code.exists' => 'Phường/xã không thuộc tỉnh/thành đã chọn.',
            'ocop_star.required' => 'Hộ kinh doanh bắt buộc khai báo hạng sao OCOP.',
            'ocop_star.in' => 'Hạng sao không hợp lệ — chỉ chấp nhận 3, 4 hoặc 5 sao.',
            'ocop_cert_expiry.required_with' => 'Vui lòng nhập ngày hết hạn chứng nhận OCOP.',
            'ocop_cert_expiry.date' => 'Ngày hết hạn không hợp lệ.',
            'hotline.regex' => 'Số điện thoại không hợp lệ.',
            'email.email' => 'Email không hợp lệ.',
            'website.url' => 'Website không hợp lệ — phải bắt đầu bằng https://',
            'media_uuids.max' => 'Tối đa :max ảnh cho mỗi chủ thể.',
            'documents.*.max' => 'Tối đa :max tệp cho mỗi loại hồ sơ.',
            'documents.*.*.file' => 'Tệp tải lên không hợp lệ.',
            'documents.*.*.mimes' => 'Chỉ chấp nhận tệp PDF, JPG, PNG hoặc WEBP.',
            'documents.*.*.max' => 'Mỗi tệp tối đa 20MB.',
        ];
    }

    private function organizationType(): ?OcopSubjectOrganizationType
    {
        return OcopSubjectOrganizationType::tryFrom((string) $this->input('organization_type'));
    }

    private function gpsRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! preg_match(self::GPS_REGEX, (string) $value, $m)) {
                $fail('Toạ độ GPS phải có dạng "vĩ độ, kinh độ" — vd 16.4637, 107.5909.');

                return;
            }

            if (abs((float) $m[1]) > 90 || abs((float) $m[2]) > 180) {
                $fail('Toạ độ GPS nằm ngoài phạm vi hợp lệ (vĩ độ ±90, kinh độ ±180).');
            }
        };
    }

    private function documentCountAfterSave(string $collection): int
    {
        $ocopSubject = $this->route('ocop_subject');
        $removed = (array) $this->input('remove_media_uuids', []);

        $kept = $ocopSubject
            ? $ocopSubject->getMedia($collection)->whereNotIn('uuid', $removed)->count()
            : 0;

        return $kept + count((array) $this->file("documents.$collection", []));
    }

    private function validateDocuments(Validator $validator): void
    {
        foreach (OcopSubject::DOCUMENT_COLLECTIONS as $collection) {
            if ($this->documentCountAfterSave($collection) > OcopSubject::MAX_DOCUMENTS) {
                $validator->errors()->add("documents.$collection", 'Tối đa '.OcopSubject::MAX_DOCUMENTS.' tệp cho mỗi loại hồ sơ.');
            }
        }

        if ($this->organizationType()?->requiresOcopDecision() && $this->documentCountAfterSave(OcopSubject::DOC_OCOP_CERT) === 0) {
            $validator->errors()->add('documents.'.OcopSubject::DOC_OCOP_CERT, 'Hộ kinh doanh bắt buộc tải lên Quyết định phê duyệt hạng sao OCOP.');
        }

        if ($this->boolean('is_food_business') && $this->documentCountAfterSave(OcopSubject::DOC_FOOD_SAFETY_CERT) === 0) {
            $validator->errors()->add('documents.'.OcopSubject::DOC_FOOD_SAFETY_CERT, 'Chủ thể ngành thực phẩm bắt buộc tải lên Giấy chứng nhận ATTP.');
        }
    }
}
