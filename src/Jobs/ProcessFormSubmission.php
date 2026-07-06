<?php

namespace Whilesmart\Forms\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;
use Whilesmart\Forms\Destinations\DestinationManager;
use Whilesmart\Forms\Enums\SubmissionStatus;
use Whilesmart\Forms\Models\FormSubmission;

/**
 * Fans a stored submission out to each configured destination. Destinations are
 * independent: one failing does not stop the others, and each outcome is
 * recorded on the submission's delivery log.
 */
class ProcessFormSubmission implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public FormSubmission $submission)
    {
    }

    public function handle(DestinationManager $destinations): void
    {
        $keys = $this->submission->form
            ? $this->submission->form->destinationKeys()
            : config('eloquent-forms.destinations', []);

        $anyFailed = false;

        foreach ($keys as $key) {
            try {
                $destinations->driver($key)->deliver($this->submission);
                $this->submission->recordDelivery($key, true);
            } catch (Throwable $e) {
                $anyFailed = true;
                $this->submission->recordDelivery($key, false, $e->getMessage());
                Log::error("Form destination [{$key}] failed for submission {$this->submission->id}: {$e->getMessage()}");
            }
        }

        $this->submission->status = $anyFailed ? SubmissionStatus::Failed : SubmissionStatus::Processed;
        $this->submission->save();
    }
}
