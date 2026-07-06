<?php

namespace Whilesmart\Forms\Tests\Feature;

use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Whilesmart\Forms\Enums\SubmissionStatus;
use Whilesmart\Forms\Mail\FormSubmissionReceived;
use Whilesmart\Forms\Models\Form;
use Whilesmart\Forms\Models\FormSubmission;
use Whilesmart\Forms\Tests\TestCase;

class FormSubmissionTest extends TestCase
{
    #[Test]
    public function a_valid_submission_is_stored_and_mailed(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/forms/contact/submissions', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'message' => 'I would like a demo of WhileSmart Pay.',
            'company' => 'Analytical Engines',
        ]);

        $response->assertCreated()->assertJsonPath('success', true);

        $submission = FormSubmission::first();
        $this->assertNotNull($submission);
        $this->assertSame('ada@example.com', $submission->email);
        $this->assertSame(SubmissionStatus::Processed, $submission->status);
        // Freeform field retained in the payload.
        $this->assertSame('Analytical Engines', $submission->payload['company']);
        $this->assertTrue($submission->delivery_log['mail']['ok']);

        $this->assertDatabaseHas('forms', ['key' => 'contact']);
        Mail::assertSent(FormSubmissionReceived::class);
    }

    #[Test]
    public function the_form_key_is_resolved_and_reused(): void
    {
        Mail::fake();

        $this->postJson('/api/forms/contact/submissions', ['message' => 'one']);
        $this->postJson('/api/forms/contact/submissions', ['message' => 'two']);

        $this->assertSame(1, Form::where('key', 'contact')->count());
        $this->assertSame(2, FormSubmission::count());
    }

    #[Test]
    public function a_honeypot_hit_is_rejected(): void
    {
        $response = $this->postJson('/api/forms/contact/submissions', [
            'name' => 'Spam Bot',
            'message' => 'buy now',
            '_gotcha' => 'i am a bot',
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, FormSubmission::count());
    }

    #[Test]
    public function an_empty_submission_is_rejected(): void
    {
        $this->postJson('/api/forms/contact/submissions', [])
            ->assertStatus(422);

        $this->assertSame(0, FormSubmission::count());
    }

    #[Test]
    public function an_inactive_form_refuses_submissions(): void
    {
        Form::create(['key' => 'closed', 'is_active' => false]);

        $this->postJson('/api/forms/closed/submissions', ['message' => 'hi'])
            ->assertStatus(404);

        $this->assertSame(0, FormSubmission::count());
    }

    #[Test]
    public function origin_restrictions_block_foreign_origins(): void
    {
        Form::create([
            'key' => 'guarded',
            'allowed_origins' => ['https://whilesmart.com'],
        ]);

        $this->postJson(
            '/api/forms/guarded/submissions',
            ['message' => 'hi'],
            ['Origin' => 'https://evil.example'],
        )->assertStatus(403);

        $this->postJson(
            '/api/forms/guarded/submissions',
            ['message' => 'hi'],
            ['Origin' => 'https://whilesmart.com'],
        )->assertCreated();
    }
}
