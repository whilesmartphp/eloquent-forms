<?php

namespace Whilesmart\Forms\Contracts;

/**
 * A human-verification challenge solved in the browser and confirmed here.
 * Implementations return false for a token the provider rejects, and throw
 * when the provider itself could not be reached.
 */
interface ChallengeVerifier
{
    /**
     * Name of the request field carrying the provider's token.
     */
    public function tokenField(): string;

    public function verify(string $token, ?string $ipAddress = null): bool;
}
