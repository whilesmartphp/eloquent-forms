<?php

namespace Whilesmart\Forms\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Whilesmart\Forms\Challenges\ChallengeManager;
use Whilesmart\Forms\Contracts\ChallengeVerifier;
use Whilesmart\Forms\Events\FormSubmittedEvent;
use Whilesmart\Forms\Http\Requests\SubmitFormRequest;
use Whilesmart\Forms\Jobs\ProcessFormSubmission;
use Whilesmart\Forms\Models\Form;
use Whilesmart\Forms\Models\FormSubmission;
use Whilesmart\Forms\Traits\ApiResponse;

class FormSubmissionController extends Controller
{
    use ApiResponse;

    public function store(SubmitFormRequest $request, ChallengeManager $challenges, string $key)
    {
        $form = Form::firstOrCreate(
            ['key' => $key],
            ['name' => Str::headline($key), 'is_active' => true],
        );

        if (! $form->is_active) {
            return $this->failure('This form is not accepting submissions.', 404);
        }

        if (! $this->originAllowed($request, $form)) {
            return $this->failure('Origin not allowed.', 403);
        }

        $verifier = $challenges->verifier($form->challengeKey());

        if ($verifier !== null) {
            $failure = $this->challengeFailure($request, $verifier);

            if ($failure !== null) {
                return $failure;
            }
        }

        $payload = $request->except(array_filter([
            '_started_at',
            $verifier?->tokenField(),
        ]));

        $submission = new FormSubmission([
            'form_id' => $form->id,
            'payload' => $payload,
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'subject' => $request->input('subject'),
            'message' => $request->input('message'),
            'source_url' => $request->headers->get('referer'),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);
        $submission->save();

        FormSubmittedEvent::dispatch($submission);

        ProcessFormSubmission::dispatch($submission)
            ->onConnection(config('eloquent-forms.queue'));

        return $this->success(
            ['id' => $submission->id],
            'Thanks. Your message has been received.',
            201,
        );
    }

    /**
     * Null when the challenge was solved. A provider that cannot be reached
     * throws instead, answering 503 rather than blaming the submitter.
     */
    private function challengeFailure(SubmitFormRequest $request, ChallengeVerifier $verifier): ?JsonResponse
    {
        $token = (string) $request->input($verifier->tokenField(), '');

        if ($token === '') {
            return $this->failure('Please complete the verification challenge.', 422);
        }

        if (! $verifier->verify($token, $request->ip())) {
            return $this->failure('Verification failed. Please try again.', 422);
        }

        return null;
    }

    private function originAllowed(SubmitFormRequest $request, Form $form): bool
    {
        $allowed = $form->allowed_origins
            ?? config('eloquent-forms.allowed_origins');

        // No restriction configured: allow.
        if (empty($allowed)) {
            return true;
        }

        $origin = $request->headers->get('origin')
            ?? $request->headers->get('referer');

        if (empty($origin)) {
            return false;
        }

        $host = parse_url($origin, PHP_URL_HOST) ?: $origin;

        foreach ((array) $allowed as $candidate) {
            $candidateHost = parse_url($candidate, PHP_URL_HOST) ?: $candidate;
            if (strcasecmp($host, $candidateHost) === 0) {
                return true;
            }
        }

        return false;
    }
}
