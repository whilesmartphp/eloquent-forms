<?php

namespace Whilesmart\Forms\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Whilesmart\Forms\Models\FormSubmission;

class FormSubmittedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public FormSubmission $submission)
    {
    }
}
