<?php

namespace Modules\Lead\Actions;

use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Lead\Models\Lead;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportLeadsAction
{
    use AsAction;

    public function handle(Collection $leads, bool $maskContact = false): StreamedResponse
    {
        $rows = $leads->map(function (Lead $lead) use ($maskContact) {
            return [
                'ID'              => $lead->id,
                'Tiêu đề'         => $lead->displayTitle(),
                'Tên KH'          => $maskContact ? '***' : $lead->contact_name,
                'Điện thoại'      => $maskContact ? '***' : ($lead->contact_phone ?? ''),
                'Email'           => $maskContact ? '***' : ($lead->contact_email ?? ''),
                'Công ty'         => $maskContact ? '***' : ($lead->contact_company ?? ''),
                'Tình trạng'      => $lead->stage?->label ?? '',
                'Nguồn'           => $lead->source?->label ?? '',
                'Người phụ trách' => $lead->assignee?->name ?? '',
                'Giá trị dự kiến' => $lead->expected_value ?? 0,
                'Đơn vị tiền tệ'  => $lead->currency ?? 'VND',
                'Ngày chốt DK'    => $lead->expected_close_date?->format('d/m/Y') ?? '',
                'Trạng thái'      => $lead->status->label(),
                'Điểm lead'       => $lead->lead_score ?? 0,
                'Ngày tạo'        => $lead->created_at->format('d/m/Y H:i'),
                'Cập nhật lần cuối' => $lead->updated_at->format('d/m/Y H:i'),
            ];
        });

        $filename = 'leads_' . now()->format('Ymd_His') . '.xlsx';

        return (new FastExcel($rows))->download($filename);
    }
}
