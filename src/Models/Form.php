<?php

namespace Whilesmart\Forms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A named form definition. Optional: submissions can arrive for a bare key and
 * a Form row is resolved or created on the fly. When present, a Form row lets
 * you scope recipients, allowed origins, destinations and the human-verification
 * challenge per form, and attach the form to any owning model (page, product,
 * workspace) polymorphically.
 *
 * @property string $key
 * @property string|null $name
 * @property string|null $recipient_email
 * @property array|null $destinations
 * @property array|null $allowed_origins
 * @property string|null $challenge
 * @property bool $is_active
 * @property array|null $meta
 */
class Form extends Model
{
    /**
     * Reserved `challenge` value meaning "run no challenge on this form",
     * as distinct from null, which inherits the configured default.
     */
    public const CHALLENGE_NONE = 'none';

    protected $fillable = [
        'key',
        'name',
        'recipient_email',
        'destinations',
        'allowed_origins',
        'challenge',
        'is_active',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'destinations' => 'array',
            'allowed_origins' => 'array',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    /**
     * Effective challenge key for this form: the form's own when set, otherwise
     * the package default. The reserved value `none` opts a form out entirely,
     * which is what a form submitted by a non-browser client needs.
     */
    public function challengeKey(): ?string
    {
        $key = $this->challenge ?: config('eloquent-forms.protection.challenge');

        if (blank($key) || $key === self::CHALLENGE_NONE) {
            return null;
        }

        return $key;
    }

    /**
     * Effective destination keys for this form: the form's own list when set,
     * otherwise the package default from config.
     *
     * @return array<int, string>
     */
    public function destinationKeys(): array
    {
        $keys = $this->destinations;

        if (empty($keys)) {
            $keys = config('eloquent-forms.destinations', []);
        }

        return array_values(array_unique(array_filter((array) $keys)));
    }
}
