<?php

use Illuminate\Support\Facades\Route;
use Whilesmart\Forms\Http\Controllers\FormSubmissionController;

Route::post('forms/{key}/submissions', [FormSubmissionController::class, 'store'])
    ->middleware('throttle:eloquent-forms')
    ->name('eloquent-forms.submit');
