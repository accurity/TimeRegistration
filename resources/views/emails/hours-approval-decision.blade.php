@component('mail::message')
# Uren {{ $approval->isApproved() ? 'goedgekeurd' : 'afgekeurd' }}

De uren van **{{ $project->name }}** ({{ $project->client->name }}) voor **{{ $period }}** zijn zojuist {{ $approval->isApproved() ? 'goedgekeurd' : 'afgekeurd' }}@if ($approval->approvedBy) door {{ $approval->approvedBy->name }}@endif.

@if ($approval->isRejected() && $approval->rejection_reason)
**Reden:** {{ $approval->rejection_reason }}
@endif

@component('mail::button', ['url' => route('admin.projects.time-entries.index', [$project, 'year' => $approval->year, 'month' => $approval->month])])
Bekijken in het admin-scherm
@endcomponent

Met vriendelijke groet,<br>
{{ config('app.name') }}
@endcomponent
