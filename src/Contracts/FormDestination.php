<?php

namespace Whilesmart\Forms\Contracts;

use Whilesmart\Forms\Models\FormSubmission;

/**
 * A sink a submission is delivered to after it is stored. Implementations
 * throw on failure; the delivery job records the per-destination outcome.
 */
interface FormDestination
{
    public function deliver(FormSubmission $submission): void;
}
