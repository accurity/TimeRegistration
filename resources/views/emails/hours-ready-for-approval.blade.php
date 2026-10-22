@component('mail::message')
# Uren klaarstaan ter goedkeuring

Voor **{{ $project->name }}** ({{ $project->client->name }}) staan de uren van **{{ $period }}** klaar om te beoordelen.

@component('mail::button', ['url' => route('portal.approvals.show', [$project, $approval->year, $approval->month])])
Uren bekijken
@endcomponent

U wordt gevraagd in te loggen als u dat nog niet bent.

Met vriendelijke groet,<br>
{{ config('app.name') }}
@endcomponent
