<?php

namespace Whilesmart\Forms\Challenges;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use Whilesmart\Forms\Contracts\ChallengeVerifier;
use Whilesmart\Forms\Exceptions\ChallengeUnavailableException;

/**
 * Cloudflare Turnstile. The site key is public and belongs in the frontend
 * bundle; only the secret is read here.
 *
 * @see https://developers.cloudflare.com/turnstile/get-started/server-side-validation/
 */
class TurnstileVerifier implements ChallengeVerifier
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function tokenField(): string
    {
        return config('eloquent-forms.turnstile.token_field', 'cf_turnstile_response');
    }

    /**
     * Recognised options: `hostname`, asserted against the hostname Cloudflare
     * reports for the token so a token minted on one site cannot be replayed
     * against another.
     *
     * @param  array<string, mixed>  $options
     */
    public function verify(string $token, ?string $ipAddress = null, array $options = []): bool
    {
        $secret = config('eloquent-forms.turnstile.secret');

        if (blank($secret)) {
            throw new ChallengeUnavailableException('Turnstile secret is not configured.');
        }

        try {
            $response = Http::asForm()
                ->timeout((int) config('eloquent-forms.turnstile.timeout', 5))
                ->post(self::VERIFY_URL, array_filter([
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $ipAddress,
                ]));
        } catch (Throwable $e) {
            throw new ChallengeUnavailableException('Turnstile could not be reached.', 0, $e);
        }

        if ($response->failed()) {
            throw new ChallengeUnavailableException(
                sprintf('Turnstile answered %d.', $response->status())
            );
        }

        if ($response->json('success') !== true) {
            Log::info('Turnstile rejected a submission.', [
                'errors' => $response->json('error-codes', []),
                'ip' => $ipAddress,
            ]);

            return false;
        }

        $expectedHostname = $options['hostname'] ?? null;

        if (filled($expectedHostname) && $response->json('hostname') !== $expectedHostname) {
            Log::info('Turnstile token was solved for a different hostname.', [
                'expected' => $expectedHostname,
                'actual' => $response->json('hostname'),
                'ip' => $ipAddress,
            ]);

            return false;
        }

        return true;
    }
}
