<?php

namespace Whilesmart\Forms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A named form definition. Optional: submissions can arrive for a bare key and
 * a Form row is resolved or created on the fly. When present, a Form row lets
 * you scope recipients, allowed origins and destinations per form, and attach
 * the form to any owning model (page, product, workspace) polymorphically.
 *
 * @property string $key
 * @property string|null $name
 * @property string|null $recipient_email
 * @property array|null $destinations
 * @property array|null $allowed_origins
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
