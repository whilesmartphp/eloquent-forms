<?php

namespace Whilesmart\Forms\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Whilesmart\Forms\Models\FormSubmission;

/**
 * Add to any model that should own form submissions polymorphically, e.g. a
 * Page, Product or Workspace. Submissions are related through the
 * `submittable` morph on FormSubmission.
 */
trait HasFormSubmissions
{
    public function formSubmissions(): MorphMany
    {
        return $this->morphMany(FormSubmission::class, 'submittable');
    }
}
