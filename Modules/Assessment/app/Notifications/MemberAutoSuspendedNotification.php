<?php

namespace Modules\Assessment\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Organization\Models\OrganizationMember;

class MemberAutoSuspendedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public function __construct(
        private readonly OrganizationMember $member,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $user = $this->member->user;
        $org  = $this->member->organization;

        return (new MailMessage())
            ->subject("[{$org->name}] Tài khoản thành viên đã bị tạm khóa tự động")
            ->line("Tài khoản của **{$user->name}** ({$user->email}) đã bị tạm khóa tự động.")
            ->line("Lý do: ngày hết hợp đồng ({$this->member->contract_end_date?->format('d/m/Y')}) đã qua.")
            ->line("Vui lòng xác nhận offboarding hoặc gia hạn hợp đồng trên hệ thống.")
            ->action('Mở HR Dashboard', url('/workforce/members'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'          => 'member_auto_suspended',
            'user_id'       => $this->member->user_id,
            'user_name'     => $this->member->user->name,
            'organization_id' => $this->member->organization_id,
        ];
    }
}
