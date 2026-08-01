<?php

namespace Whilesmart\Forms\Challenges;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Whilesmart\Forms\Contracts\ChallengeVerifier;

class ChallengeManager
{
    public function __construct(private Container $container)
    {
    }

    /**
     * The configured verifier, or null when no challenge is in use.
     */
    public function verifier(): ?ChallengeVerifier
    {
        $key = config('eloquent-forms.protection.challenge');

        if (blank($key)) {
            return null;
        }

        $map = config('eloquent-forms.challenge_drivers', []);

        if (! isset($map[$key])) {
            throw new InvalidArgumentException("Form challenge [{$key}] is not registered.");
        }

        $instance = $this->container->make($map[$key]);

        if (! $instance instanceof ChallengeVerifier) {
            throw new InvalidArgumentException("Form challenge [{$key}] must implement ChallengeVerifier.");
        }

        return $instance;
    }
}
