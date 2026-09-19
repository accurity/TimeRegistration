@php
    $periodStart = \Illuminate\Support\Carbon::createFromDate($year, $month, 1);
    $periodLabel = $periodStart->translatedFormat('F Y');
    $defaultDate = $periodStart->isCurrentMonth() ? now()->toDateString() : $periodStart->toDateString();
    $periodRoute = fn (\App\Models\Project $routeProject, \Illuminate\Support\Carbon $period, string $routeView) => route('admin.projects.time-entries.index', array_filter([
        $routeProject,
        'year' => $period->year,
        'month' => $period->month,
        'view' => $routeView === 'list' ? 'list' : null,
    ]));
@endphp
<x-admin-layout>
    <div class="mb-5 flex items-end justify-between gap-6">
        <div>
            <h1 class="mb-2 font-display text-[28px] font-bold leading-9 text-ink-900 dark:text-dark-text1">Uren registreren</h1>
            <div class="flex items-center gap-[10px]">
                <x-select-input aria-label="Project" class="min-w-[260px] py-[7px] pr-9" onchange="window.location = this.value">
                    @foreach ($projects as $projectOption)
                        <option value="{{ $periodRoute($projectOption, $periodStart, $view) }}" @selected($projectOption->is($project))>{{ $projectOption->client->name }} — {{ $projectOption->name }}</option>
                    @endforeach
                </x-select-input>

                <div class="flex items-stretch overflow-hidden rounded-[3px] border border-ink-300 bg-white text-sm dark:border-dark-borderfield dark:bg-dark-surface1">
                    <a href="{{ $periodRoute($project, $previousPeriod, $view) }}" aria-label="Vorige maand" class="border-r border-ink-200 px-3 py-[7px] text-ink-700 hover:bg-ink-50 dark:border-dark-border dark:text-dark-text2 dark:hover:bg-dark-surface2">&lsaquo;</a>
                    <div class="min-w-[132px] px-[18px] py-[7px] text-center font-semibold text-ink-900 dark:text-dark-text1">{{ ucfirst($periodLabel) }}</div>
                    <a href="{{ $periodRoute($project, $nextPeriod, $view) }}" aria-label="Volgende maand" class="border-l border-ink-200 px-3 py-[7px] text-ink-700 hover:bg-ink-50 dark:border-dark-border dark:text-dark-text2 dark:hover:bg-dark-surface2">&rsaquo;</a>
                </div>

                <div class="flex overflow-hidden rounded-[3px] border border-ink-300 text-[13px] font-semibold dark:border-dark-borderfield">
                    @foreach (['calendar' => 'Kalender', 'list' => 'Lijst'] as $viewOption => $viewLabel)
                        <a
                            href="{{ $periodRoute($project, $periodStart, $viewOption) }}"
                            @class([
                                'px-[14px] py-2',
                                'bg-brand-600 text-white' => $view === $viewOption,
                                'bg-white text-ink-700 hover:bg-ink-50 dark:bg-dark-surface1 dark:text-dark-text2 dark:hover:bg-dark-surface2' => $view !== $viewOption,
                            ])
                            @if ($view === $viewOption) aria-current="page" @endif
                        >{{ $viewLabel }}</a>
                    @endforeach
                </div>
            </div>
        </div>

        @if ($view === 'calendar')
            @include('admin.projects.time-entries._approval-action')
        @endif
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (session('error'))
        <div class="mb-4 text-sm font-medium text-status-red-fg dark:text-status-red-fg-dark">{{ session('error') }}</div>
    @endif

    @if ($view === 'list')
        @include('admin.projects.time-entries._list')
    @else
        @include('admin.projects.time-entries._calendar')
    @endif
</x-admin-layout>
