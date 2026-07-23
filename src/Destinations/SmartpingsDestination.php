<?php

namespace Whilesmart\Forms\Destinations;

use RuntimeException;
use Whilesmart\Forms\Contracts\FormDestination;
use Whilesmart\Forms\Models\FormSubmission;

/**
 * Alerts the team via SmartPings on each submission.
 *
 * Not yet implemented: this will build on the official smartpings/php-sdk (the
 * same dependency the auth package uses) rather than a guessed HTTP shape. It
 * throws until then so an accidental enable fails loudly.
 */
class SmartpingsDestination implements FormDestination
{
    public function deliver(FormSubmission $submission): void
    {
        throw new RuntimeException(
            'SmartPings destination is not implemented yet. Set SMARTPINGS_CLIENT_ID and SMARTPINGS_SECRET_ID before enabling it.'
        );
    }
}
