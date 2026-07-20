<?php

namespace Modules\Newsletter\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\URL;
use Modules\Newsletter\Models\NewsletterSubscriber;

/**
 * spec/Newsletter_Technical_Specification.md §9.2 — chỉ gửi khi NEWSLETTER_DOUBLE_OPT_IN=true
 * (§0 mục 14). Link xác nhận KHÔNG hết hạn (không truyền $expiration cho signedRoute) — subscriber
 * có thể xác nhận trễ vài ngày vẫn hợp lệ.
 */
class ConfirmSubscriptionMail extends Mailable
{
    public function __construct(private readonly NewsletterSubscriber $subscriber) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: config('newsletter.from_address'),
            subject: 'Xác nhận đăng ký nhận bản tin',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'newsletter::emails.confirm',
            with: [
                'fullName'    => $this->subscriber->full_name,
                'confirmUrl'  => URL::signedRoute('newsletter.public.confirm', ['subscriber' => $this->subscriber->uuid]),
            ],
        );
    }
}
