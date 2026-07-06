<?php

namespace Whilesmart\Forms\Tests\Feature;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Whilesmart\Forms\Enums\SubmissionStatus;
use Whilesmart\Forms\Mail\FormSubmissionReceived;
use Whilesmart\Forms\Models\FormSubmission;
use Whilesmart\Forms\Tests\TestCase;
use Workbench\App\Models\User;

class DestinationFanOutTest extends TestCase
{
    #[Test]
    public function a_submission_is_delivered_to_a_signed_webhook(): void
    {
        Http::fake();
        config([
            'eloquent-forms.destinations' => ['webhook'],
            'eloquent-forms.webhook.url' => 'https://hooks.example/inbox',
            'eloquent-forms.webhook.secret' => 'shh',
        ]);

        $this->postJson('/api/forms/contact/submissions', ['message' => 'ping'])
            ->assertCreated();

        $submission = FormSubmission::first();
        $this->assertSame(SubmissionStatus::Processed, $submission->status);
        $this->assertTrue($submission->delivery_log['webhook']['ok']);

        Http::assertSent(fn ($request) => $request->url() === 'https://hooks.example/inbox'
            && $request->hasHeader('X-Forms-Signature'));
    }

    #[Test]
    public function a_destination_failure_is_recorded_without_failing_the_request(): void
    {
        // Webhook with no URL configured throws inside the driver.
        config([
            'eloquent-forms.destinations' => ['webhook'],
            'eloquent-forms.webhook.url' => null,
        ]);

        $this->postJson('/api/forms/contact/submissions', ['message' => 'ping'])
            ->assertCreated();

        $submission = FormSubmission::first();
        $this->assertSame(SubmissionStatus::Failed, $submission->status);
        $this->assertFalse($submission->delivery_log['webhook']['ok']);
        $this->assertNotEmpty($submission->delivery_log['webhook']['detail']);
    }

    #[Test]
    public function an_unimplemented_destination_fails_loudly_and_is_logged(): void
    {
        config(['eloquent-forms.destinations' => ['mautic']]);

        $this->postJson('/api/forms/contact/submissions', ['message' => 'ping'])
            ->assertCreated();

        $submission = FormSubmission::first();
        $this->assertSame(SubmissionStatus::Failed, $submission->status);
        $this->assertFalse($submission->delivery_log['mautic']['ok']);
        $this->assertStringContainsString('not implemented', $submission->delivery_log['mautic']['detail']);
    }

    #[Test]
    public function submissions_can_be_owned_polymorphically(): void
    {
        config(['eloquent-forms.destinations' => []]);

        $user = User::forceCreate([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => bcrypt('secret'),
        ]);

        $submission = FormSubmission::create([
            'payload' => ['message' => 'hi'],
            'message' => 'hi',
        ]);
        $submission->submittable()->associate($user)->save();

        $this->assertTrue($user->formSubmissions()->whereKey($submission->id)->exists());
    }

    #[Test]
    public function the_mailable_targets_the_markdown_view(): void
    {
        $submission = FormSubmission::create([
            'payload' => ['name' => 'A', '_gotcha' => ''],
            'name' => 'A',
            'email' => 'a@example.com',
        ]);

        $mailable = new FormSubmissionReceived($submission);

        $this->assertStringContainsString('New submission', $mailable->envelope()->subject);
        $this->assertSame('eloquent-forms::emails.submission', $mailable->content()->markdown);
        // Control keys are stripped from the rendered fields.
        $this->assertArrayNotHasKey('_gotcha', $submission->fields());
    }
}
