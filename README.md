# whilesmart/eloquent-forms

Polymorphic form collection with a pluggable, agnostic delivery fan-out. Every
submission is stored, then delivered to any number of configured destinations
(mail, webhook, Mautic, SmartPings, ...). Add a destination once and every form
gains it.

## Install (in this monorepo)

The package is developed under `api/packages/eloquent-forms` and wired as a path
repository in the app's `composer.json`. From `api/`:

```bash
composer install          # symlinks the package and discovers the provider
php artisan migrate       # creates forms + form_submissions
php artisan vendor:publish --tag=eloquent-forms-config   # optional
```

## Submitting

Public, throttled endpoint (mounted under the `route_prefix`, default `api`):

```
POST /api/forms/{key}/submissions
```

The form `{key}` is resolved or created on first use. Body is freeform JSON;
`name`, `email`, `phone`, `subject`, `message` are recognised and copied to typed
columns, everything else is retained in `payload`.

Include two protection fields the frontend should send:

- `_gotcha` — honeypot, must be empty (configurable name)
- `_started_at` — unix-ms timestamp of when the form rendered (time-trap)

## Destinations

Set the default fan-out with `FORMS_DESTINATIONS` (comma-separated) or override
per form via the `destinations` column on a `Form` row.

| key | status | needs |
| :-- | :----- | :---- |
| `mail` | ready | `FORMS_MAIL_TO` or a Form `recipient_email` |
| `webhook` | ready | `FORMS_WEBHOOK_URL` (optional `FORMS_WEBHOOK_SECRET`) |
| `mautic` | pending | Mautic base URL + credentials |
| `smartpings` | pending | `SMARTPINGS_CLIENT_ID` / `SMARTPINGS_SECRET_ID` |

Add your own by implementing `Whilesmart\Forms\Contracts\FormDestination` and
registering it in `config('eloquent-forms.drivers')`.

## Polymorphism

`FormSubmission` and `Form` both `morphTo` an owner. Add
`Whilesmart\Forms\Traits\HasFormSubmissions` to any model to read submissions
attached to it.

## Events

`Whilesmart\Forms\Events\FormSubmittedEvent` fires after a submission is stored,
before fan-out. Listen to it to add custom side effects.
