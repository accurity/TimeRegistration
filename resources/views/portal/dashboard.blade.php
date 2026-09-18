<x-portal-layout>
    <h1 class="mb-1 font-display text-[28px] font-bold leading-9 text-ink-900 dark:text-dark-text1">Uren</h1>
    <p class="mb-6 text-[13px] text-ink-500 dark:text-dark-text2">{{ ucfirst(now()->translatedFormat('l j F Y')) }}</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="overflow-hidden rounded-md border border-ink-200 dark:border-dark-border">
        <div class="grid grid-cols-[1.6fr_1fr_140px_100px] gap-3 border-b border-ink-200 bg-ink-50 px-5 py-[10px] text-[11px] font-semibold uppercase tracking-[.1em] text-ink-500 dark:border-dark-border dark:bg-dark-surface2 dark:text-dark-text2">
            <div>Project</div>
            <div>Periode</div>
            <div>Status</div>
            <div></div>
        </div>

        @forelse ($approvals as $approval)
            <div @class([
                'grid grid-cols-[1.6fr_1fr_140px_100px] items-center gap-3 border-b border-ink-100 px-5 py-3 text-sm dark:border-dark-border',
                'bg-ink-50 dark:bg-dark-surface2' => $loop->even,
                'last:border-b-0' => true,
            ])>
                <div class="text-ink-900 dark:text-dark-text1">{{ $approval->project->name }}</div>
                <div class="text-ink-700 dark:text-dark-text2">{{ \Illuminate\Support\Carbon::createFromDate($approval->year, $approval->month, 1)->translatedFormat('F Y') }}</div>
                <div>
                    <x-approval-status-badge :status="$approval->status" />
                </div>
                <div class="text-right">
                    <a href="{{ route('portal.approvals.show', [$approval->project, $approval->year, $approval->month]) }}" class="text-brand-600 hover:text-brand-800 dark:text-brand-darkhover">Bekijken</a>
                </div>
            </div>
        @empty
            <div class="px-5 py-8 text-center text-sm text-ink-500 dark:text-dark-text2">
                Er staan nog geen uren voor u klaar ter beoordeling.
            </div>
        @endforelse
    </div>
</x-portal-layout>
