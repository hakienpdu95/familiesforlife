<?php

namespace Modules\Assessment\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InactiveMembersReportNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public function __construct(
        private readonly Collection $members,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $org   = $this->members->first()->organization;
        $count = $this->members->count();

        $mail = (new MailMessage())
            ->subject("Báo cáo thành viên không hoạt động — {$org->name}")
            ->line("**{$count} thành viên** không có hoạt động trong hơn 45 ngày qua:")
            ->line('');

        foreach ($this->members as $member) {
            $lastActive = $member->last_active_at
                ? $member->last_active_at->format('d/m/Y') . ' (' . $member->last_active_at->diffForHumans() . ')'
                : 'Chưa có hoạt động';

            $mail->line("• **{$member->user->name}** — lần cuối: {$lastActive}");
        }

        return $mail->action('Xem HR Dashboard', url('/workforce/members'));
    }
}
