<?php

namespace Whilesmart\Forms\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Whilesmart\Forms\Interfaces\ResponseFormatterInterface;

/**
 * The challenge provider could not be reached or is misconfigured. Distinct
 * from a rejected token: the submitter may well be human, so this answers 503
 * and invites a retry rather than accusing them of being a bot.
 */
class ChallengeUnavailableException extends RuntimeException
{
    public function render(Request $request): ?JsonResponse
    {
        if (! $request->expectsJson()) {
            return null;
        }

        return app(ResponseFormatterInterface::class)->failure(
            'Verification is unavailable. Please try again shortly.',
            503,
        );
    }
}
