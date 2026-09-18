@component('mail::message')
# Uren klaarstaan ter goedkeuring

Voor **{{ $project->name }}** ({{ $project->client->name }}) staan de uren van **{{ $period }}** klaar om te beoordelen.

@component('mail::button', ['url' => route('login')])
Inloggen op het klantportaal
@endcomponent

Na inloggen vindt u de uren onder "Uren" in het portaal.

Met vriendelijke groet,<br>
{{ config('app.name') }}
@endcomponent
