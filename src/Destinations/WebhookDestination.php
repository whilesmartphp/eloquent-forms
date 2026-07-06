<?php

namespace Whilesmart\Forms\Destinations;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Whilesmart\Forms\Contracts\FormDestination;
use Whilesmart\Forms\Models\FormSubmission;

class WebhookDestination implements FormDestination
{
    public function deliver(FormSubmission $submission): void
    {
        $url = config('eloquent-forms.webhook.url');

        if (empty($url)) {
            throw new RuntimeException('No URL configured for the webhook destination.');
        }

        $payload = [
            'id' => $submission->id,
            'form' => optional($submission->form)->key,
            'fields' => $submission->fields(),
            'source_url' => $submission->source_url,
            'submitted_at' => optional($submission->created_at)->toIso8601String(),
        ];

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $headers = ['Content-Type' => 'application/json'];
        if ($secret = config('eloquent-forms.webhook.secret')) {
            $headers['X-Forms-Signature'] = hash_hmac('sha256', $body, $secret);
        }

        $response = Http::withHeaders($headers)->withBody($body, 'application/json')->post($url);

        if ($response->failed()) {
            throw new RuntimeException("Webhook responded with HTTP {$response->status()}.");
        }
    }
}
