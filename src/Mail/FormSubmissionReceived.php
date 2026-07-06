<?php

namespace Whilesmart\Forms\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Whilesmart\Forms\Models\FormSubmission;

class FormSubmissionReceived extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public FormSubmission $submission)
    {
    }

    public function envelope(): Envelope
    {
        $prefix = config('eloquent-forms.mail.subject_prefix', 'New submission');
        $form = optional($this->submission->form);
        $formName = $form->name ?? $form->key ?? 'form';

        return new Envelope(
            subject: "{$prefix}: {$formName}",
            replyTo: array_filter([$this->submission->email]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'eloquent-forms::emails.submission',
            with: [
                'fields' => $this->submission->fields(),
                'submission' => $this->submission,
            ],
        );
    }
}
