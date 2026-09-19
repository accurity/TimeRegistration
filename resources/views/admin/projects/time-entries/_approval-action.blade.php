@if (! $approval || $approval->isRejected())
    <form method="POST" action="{{ route('admin.projects.monthly-approval.submit', $project) }}">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="month" value="{{ $month }}">
        <x-primary-button>
            {{ $approval && $approval->isRejected() ? 'Opnieuw indienen ter goedkeuring' : 'Ter goedkeuring versturen' }}
        </x-primary-button>
    </form>
@elseif ($approval->isPending())
    <p class="max-w-[260px] text-right text-[13px] leading-5 text-ink-700 dark:text-dark-text2">Ingediend op {{ $approval->submitted_at?->translatedFormat('j F Y, H:i') }}. In afwachting van de klant.</p>
@else
    <a href="{{ route('admin.invoices.create', ['project_id' => $project->id, 'year' => $year, 'month' => $month]) }}" class="inline-flex items-center justify-center rounded-[3px] border border-brand-600 bg-brand-600 px-[18px] py-[9px] text-sm font-semibold text-white hover:border-brand-800 hover:bg-brand-800">Factuur opstellen</a>
@endif
