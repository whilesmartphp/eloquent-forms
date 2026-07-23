<?php

namespace Whilesmart\Forms\Destinations;

use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Whilesmart\Forms\Contracts\FormDestination;
use Whilesmart\Forms\Mail\FormSubmissionReceived;
use Whilesmart\Forms\Models\FormSubmission;

class MailDestination implements FormDestination
{
    public function deliver(FormSubmission $submission): void
    {
        $recipient = optional($submission->form)->recipient_email
            ?? config('eloquent-forms.mail.to');

        if (empty($recipient)) {
            throw new RuntimeException('No recipient configured for the mail destination.');
        }

        Mail::to($recipient)->send(new FormSubmissionReceived($submission));
    }
}
