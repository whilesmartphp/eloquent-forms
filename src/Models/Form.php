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
 * @property array<string, mixed>|null $challenge
 * @property bool $is_active
 * @property array|null $meta
 */
class Form extends Model
{
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
            'challenge' => 'array',
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
     * Effective challenge driver for this form, or null to run none.
     *
     * The stored document names a driver and, optionally, settings for it:
     *
     *   null                                        inherit the configured default
     *   ['driver' => null]                          run no challenge on this form
     *   ['driver' => 'turnstile']                   name a driver
     *   ['driver' => 'turnstile', 'options' => []]  and pass it settings
     *
     * A present `driver` entry always wins, including a null one, which is how
     * a form submitted by a non-browser client opts out of an install-wide
     * default. Anything else falls through to that default.
     */
    public function challengeKey(): ?string
    {
        $config = $this->challenge;

        if (is_array($config) && array_key_exists('driver', $config)) {
            return blank($config['driver']) ? null : (string) $config['driver'];
        }

        $default = config('eloquent-forms.protection.challenge');

        return blank($default) ? null : (string) $default;
    }

    /**
     * Settings handed to this form's challenge driver. Their meaning belongs to
     * the driver, so nothing here interprets them.
     *
     * @return array<string, mixed>
     */
    public function challengeOptions(): array
    {
        $config = $this->challenge;

        if (! is_array($config) || ! isset($config['options']) || ! is_array($config['options'])) {
            return [];
        }

        return $config['options'];
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
