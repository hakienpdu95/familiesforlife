<?php

namespace Modules\Newsletter\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Modules\Newsletter\Models\NewsletterSubscriber;

/**
 * spec/Newsletter_Technical_Specification.md §9.2 — transactional (1 người, ngay lúc đăng ký),
 * KHÔNG dùng Broadcast API cho việc này (§0 mục 7).
 */
class WelcomeSubscriberMail extends Mailable
{
    public function __construct(private readonly NewsletterSubscriber $subscriber) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: config('newsletter.from_address'),
            subject: 'Cảm ơn bạn đã đăng ký nhận bản tin',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'newsletter::emails.welcome',
            with: ['fullName' => $this->subscriber->full_name],
        );
    }
}
