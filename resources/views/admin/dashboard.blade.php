<x-admin-layout>
    <div class="mb-[22px] flex items-end justify-between">
        <div>
            <h1 class="mb-1 font-display text-[28px] font-bold leading-9 text-ink-900 dark:text-dark-text1">Dashboard</h1>
            <div class="text-[13px] text-ink-500 dark:text-dark-text2">{{ ucfirst(now()->translatedFormat('l j F Y')) }}</div>
        </div>
    </div>
</x-admin-layout>
