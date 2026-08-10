# whilesmart/eloquent-forms

Polymorphic form collection with a pluggable, agnostic delivery fan-out. Every
submission is stored, then delivered to any number of configured destinations
(mail, webhook, Mautic, SmartPings, ...). Add a destination once and every form
gains it.

## Install

```bash
composer require whilesmart/eloquent-forms
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

- `_gotcha`: honeypot, must be empty (configurable name)
- `_started_at`: unix-ms timestamp of when the form rendered (time-trap)

## Challenges

The honeypot and the time trap cost a bot nothing to defeat once someone
bothers. Set `FORMS_CHALLENGE` to require a solved human-verification challenge
as well:

```env
FORMS_CHALLENGE=turnstile
TURNSTILE_SECRET_KEY=your-turnstile-secret-here
```

The frontend renders the widget with its own public site key and posts the
resulting token as `cf_turnstile_response`. The token is verified server-side,
then dropped rather than stored with the submission.

A rejected or missing token answers 422. A provider that cannot be reached
answers 503 instead, so an outage at the provider reads as a retry rather than
as an accusation.

Leaving `FORMS_CHALLENGE` unset runs no challenge and makes no outbound call.

### Forms a browser does not submit

Only a browser can render the widget and produce a token, so a challenge set in
config would otherwise lock out mobile and server-to-server clients posting to
the same API. The `challenge` column decides this per form:

| Stored value | Effect |
| :-- | :----- |
| `null` | inherit the configured default |
| `['driver' => null]` | run no challenge on this form |
| `['driver' => 'turnstile']` | name a driver |
| `['driver' => 'turnstile', 'options' => [...]]` | and pass it settings |

```php
Form::create(['key' => 'mobile-intake', 'challenge' => ['driver' => null]]);
```

A present `driver` entry always wins, including a null one. Anything else falls
through to the default, so the column follows the same rule as `destinations`
and `allowed_origins`.

`options` belongs to the driver and nothing else interprets it. Turnstile reads
`hostname`, checking it against the host Cloudflare reports for the token so one
minted on another site cannot be replayed:

```php
'challenge' => ['driver' => 'turnstile', 'options' => ['hostname' => 'whilesmart.com']],
```

Forms are created on first use, so a key that has never been submitted inherits
the config default and is challenged. Create the row ahead of the first call for
anything an API client submits.

Add another provider by implementing
`Whilesmart\Forms\Contracts\ChallengeVerifier` and registering it in
`config('eloquent-forms.challenge_drivers')`.

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
