<?php

namespace Modules\WorkflowAutomation\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Modules\WorkflowAutomation\Data\TriggerPayload;

class WorkflowMail extends Mailable
{
    public function __construct(
        public readonly string         $mailSubject,
        public readonly string         $template,
        public readonly TriggerPayload $payload,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mailSubject);
    }

    public function content(): Content
    {
        return new Content(
            view: $this->template,
            with: [
                'payload'     => $this->payload,
                'extra'       => $this->payload->extra,
                'actorEmail'  => $this->payload->actorEmail,
                'actorName'   => $this->payload->actorName,
                'subjectType' => $this->payload->subjectType,
                'subjectId'   => $this->payload->subjectId,
            ],
        );
    }
}
