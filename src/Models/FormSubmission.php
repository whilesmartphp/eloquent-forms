<?php

namespace Whilesmart\Forms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Whilesmart\Forms\Enums\SubmissionStatus;

/**
 * A single submission. `payload` holds the freeform fields exactly as posted;
 * the typed columns (name/email/phone/subject/message) are convenience copies
 * pulled from the payload for querying and display. `submittable` relates the
 * submission to any owning model polymorphically.
 *
 * @property int|null $form_id
 * @property array $payload
 * @property string|null $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $subject
 * @property string|null $message
 * @property string|null $source_url
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property SubmissionStatus $status
 * @property array|null $delivery_log
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Form|null $form
 * @property-read Model|null $submittable
 */
class FormSubmission extends Model
{
    protected $fillable = [
        'form_id',
        'submittable_type',
        'submittable_id',
        'payload',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'source_url',
        'ip_address',
        'user_agent',
        'status',
        'delivery_log',
    ];

    protected $attributes = [
        'status' => SubmissionStatus::Pending->value,
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'delivery_log' => 'array',
            'status' => SubmissionStatus::class,
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function submittable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The freeform fields with reserved/control keys (honeypot, timing) removed.
     *
     * @return array<string, mixed>
     */
    public function fields(): array
    {
        return array_filter(
            $this->payload ?? [],
            fn ($key) => ! str_starts_with((string) $key, '_'),
            ARRAY_FILTER_USE_KEY
        );
    }

    public function recordDelivery(string $destination, bool $succeeded, ?string $detail = null): void
    {
        $log = $this->delivery_log ?? [];
        $log[$destination] = array_filter([
            'ok' => $succeeded,
            'detail' => $detail,
        ], fn ($value) => $value !== null);

        $this->delivery_log = $log;
        $this->save();
    }
}
