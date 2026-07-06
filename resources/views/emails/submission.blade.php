<x-mail::message>
# {{ $submission->form?->name ?? $submission->form?->key ?? 'New submission' }}

You received a new form submission.

<x-mail::table>
| Field | Value |
| :---- | :---- |
@foreach ($fields as $key => $value)
| {{ $key }} | {{ is_array($value) ? json_encode($value) : $value }} |
@endforeach
</x-mail::table>

@if ($submission->source_url)
Submitted from: {{ $submission->source_url }}
@endif

Received: {{ optional($submission->created_at)->toDayDateTimeString() }}
</x-mail::message>
