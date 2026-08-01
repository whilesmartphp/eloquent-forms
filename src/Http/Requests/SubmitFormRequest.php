<?php

namespace Whilesmart\Forms\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Whilesmart\Forms\Challenges\ChallengeManager;

class SubmitFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $max = (int) config('eloquent-forms.protection.max_value_length', 5000);

        $rules = [
            'email' => ['nullable', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', "max:{$max}"],
        ];

        // Honeypot fields must arrive empty or absent.
        foreach ((array) config('eloquent-forms.protection.honeypot_fields', []) as $field) {
            $rules[$field] = ['prohibited'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Time-trap: reject submissions that arrive implausibly fast.
            $min = (int) config('eloquent-forms.protection.min_submit_seconds', 0);
            $startedAt = $this->input('_started_at');
            if ($min > 0 && is_numeric($startedAt)) {
                $elapsed = (microtime(true) * 1000 - (float) $startedAt) / 1000;
                if ($elapsed < $min) {
                    $validator->errors()->add('form', 'Submission rejected.');
                }
            }

            // Require at least one meaningful field.
            $hasContent = collect(['name', 'email', 'phone', 'subject', 'message'])
                ->contains(fn ($key) => filled($this->input($key)));
            if (! $hasContent) {
                $validator->errors()->add('form', 'The form is empty.');
            }

            $this->verifyChallenge($validator);
        });
    }

    /**
     * A configured challenge provider gets the last word. An unreachable
     * provider throws out of here, answering 503 rather than 422.
     */
    private function verifyChallenge(Validator $validator): void
    {
        $verifier = app(ChallengeManager::class)->verifier();

        if ($verifier === null) {
            return;
        }

        $token = (string) $this->input($verifier->tokenField(), '');

        if ($token === '') {
            $validator->errors()->add('challenge', 'Please complete the verification challenge.');

            return;
        }

        if (! $verifier->verify($token, $this->ip())) {
            $validator->errors()->add('challenge', 'Verification failed. Please try again.');
        }
    }
}
