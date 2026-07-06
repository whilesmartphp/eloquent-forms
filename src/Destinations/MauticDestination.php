<?php

namespace Whilesmart\Forms\Destinations;

use RuntimeException;
use Whilesmart\Forms\Contracts\FormDestination;
use Whilesmart\Forms\Models\FormSubmission;

/**
 * Registers/updates a Mautic contact from a submission.
 *
 * Not yet implemented: the Mautic REST contract (auth flow and the contacts
 * endpoint payload) must be verified against the running Mautic instance
 * before this is wired, so it is not guessed here. It throws until then so an
 * accidental enable fails loudly instead of silently dropping the contact.
 */
class MauticDestination implements FormDestination
{
    public function deliver(FormSubmission $submission): void
    {
        throw new RuntimeException(
            'Mautic destination is not implemented yet. Provide MAUTIC_BASE_URL and credentials so it can be built and tested against the live instance.'
        );
    }
}
