<?php

namespace Whilesmart\Forms\Tests\Feature;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Whilesmart\Forms\Models\Form;
use Whilesmart\Forms\Models\FormSubmission;
use Whilesmart\Forms\Tests\TestCase;

class ChallengeVerificationTest extends TestCase
{
    private const VERIFY_URL = 'challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * Captured from a live siteverify call against Cloudflare's documented
     * always-passing test secret.
     */
    private const ACCEPTED = [
        'challenge_ts' => '2026-08-01T15:00:29.021Z',
        'error-codes' => [],
        'hostname' => 'example.com',
        'metadata' => ['result_with_testing_key' => true],
        'success' => true,
    ];

    /**
     * Captured from a live siteverify call against the always-failing secret.
     */
    private const REJECTED = [
        'error-codes' => ['invalid-input-response'],
        'success' => false,
        'messages' => [],
        'metadata' => ['result_with_testing_key' => true],
    ];

    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('eloquent-forms.protection.challenge', 'turnstile');
        $app['config']->set('eloquent-forms.turnstile.secret', 'test-secret');
    }

    #[Test]
    public function a_solved_challenge_is_accepted(): void
    {
        Http::fake([self::VERIFY_URL => Http::response(self::ACCEPTED)]);

        $this->postJson('/api/forms/contact/submissions', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'message' => 'I would like a demo.',
            'cf_turnstile_response' => 'a-token-from-the-widget',
        ])->assertCreated();

        $this->assertSame(1, FormSubmission::count());
    }

    #[Test]
    public function the_challenge_token_is_not_kept_in_the_payload(): void
    {
        Http::fake([self::VERIFY_URL => Http::response(self::ACCEPTED)]);

        $this->postJson('/api/forms/contact/submissions', [
            'message' => 'I would like a demo.',
            'cf_turnstile_response' => 'a-token-from-the-widget',
        ])->assertCreated();

        $this->assertArrayNotHasKey('cf_turnstile_response', FormSubmission::first()->payload);
    }

    #[Test]
    public function a_rejected_token_is_refused(): void
    {
        Http::fake([self::VERIFY_URL => Http::response(self::REJECTED)]);

        $this->postJson('/api/forms/contact/submissions', [
            'message' => 'buy now',
            'cf_turnstile_response' => 'a-stale-token',
        ])->assertStatus(422);

        $this->assertSame(0, FormSubmission::count());
    }

    #[Test]
    public function a_missing_token_is_refused(): void
    {
        Http::fake([self::VERIFY_URL => Http::response(self::ACCEPTED)]);

        $this->postJson('/api/forms/contact/submissions', [
            'message' => 'no challenge solved',
        ])->assertStatus(422);

        $this->assertSame(0, FormSubmission::count());
    }

    #[Test]
    public function an_unreachable_provider_answers_service_unavailable(): void
    {
        Http::fake([self::VERIFY_URL => Http::response('gateway down', 502)]);

        $this->postJson('/api/forms/contact/submissions', [
            'message' => 'I would like a demo.',
            'cf_turnstile_response' => 'a-token-from-the-widget',
        ])->assertStatus(503);

        $this->assertSame(0, FormSubmission::count());
    }

    #[Test]
    public function a_missing_secret_answers_service_unavailable(): void
    {
        config()->set('eloquent-forms.turnstile.secret', null);

        $this->postJson('/api/forms/contact/submissions', [
            'message' => 'I would like a demo.',
            'cf_turnstile_response' => 'a-token-from-the-widget',
        ])->assertStatus(503);

        $this->assertSame(0, FormSubmission::count());
    }

    #[Test]
    public function no_configured_challenge_leaves_submissions_untouched(): void
    {
        config()->set('eloquent-forms.protection.challenge', null);
        Http::fake();

        $this->postJson('/api/forms/contact/submissions', [
            'message' => 'I would like a demo.',
        ])->assertCreated();

        Http::assertNothingSent();
    }

    #[Test]
    public function a_form_can_opt_out_of_the_configured_challenge(): void
    {
        Form::create(['key' => 'mobile-intake', 'challenge' => ['driver' => null]]);
        Http::fake();

        $this->postJson('/api/forms/mobile-intake/submissions', [
            'message' => 'posted by a client that cannot render a widget',
        ])->assertCreated();

        Http::assertNothingSent();
    }

    #[Test]
    public function opting_one_form_out_leaves_the_others_challenged(): void
    {
        Form::create(['key' => 'mobile-intake', 'challenge' => ['driver' => null]]);
        Http::fake([self::VERIFY_URL => Http::response(self::ACCEPTED)]);

        $this->postJson('/api/forms/mobile-intake/submissions', ['message' => 'no token'])
            ->assertCreated();

        $this->postJson('/api/forms/contact/submissions', ['message' => 'no token'])
            ->assertStatus(422);
    }

    #[Test]
    public function a_form_can_require_a_challenge_the_default_does_not(): void
    {
        config()->set('eloquent-forms.protection.challenge', null);
        Form::create(['key' => 'guarded', 'challenge' => ['driver' => 'turnstile']]);
        Http::fake([self::VERIFY_URL => Http::response(self::ACCEPTED)]);

        $this->postJson('/api/forms/open/submissions', ['message' => 'no token needed'])
            ->assertCreated();

        $this->postJson('/api/forms/guarded/submissions', ['message' => 'no token'])
            ->assertStatus(422);

        $this->postJson('/api/forms/guarded/submissions', [
            'message' => 'token supplied',
            'cf_turnstile_response' => 'a-token-from-the-widget',
        ])->assertCreated();
    }

    #[Test]
    public function driver_options_are_carried_from_the_form(): void
    {
        Form::create([
            'key' => 'pinned',
            'challenge' => ['driver' => 'turnstile', 'options' => ['hostname' => 'example.com']],
        ]);
        Http::fake([self::VERIFY_URL => Http::response(self::ACCEPTED)]);

        $this->postJson('/api/forms/pinned/submissions', [
            'message' => 'solved on the expected host',
            'cf_turnstile_response' => 'a-token-from-the-widget',
        ])->assertCreated();
    }

    #[Test]
    public function a_token_solved_for_another_hostname_is_refused(): void
    {
        Form::create([
            'key' => 'pinned',
            'challenge' => ['driver' => 'turnstile', 'options' => ['hostname' => 'whilesmart.com']],
        ]);
        Http::fake([self::VERIFY_URL => Http::response(self::ACCEPTED)]);

        $this->postJson('/api/forms/pinned/submissions', [
            'message' => 'token minted elsewhere',
            'cf_turnstile_response' => 'a-token-from-the-widget',
        ])->assertStatus(422);

        $this->assertSame(0, FormSubmission::count());
    }
}
