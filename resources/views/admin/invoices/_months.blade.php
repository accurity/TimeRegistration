@php
    $approvalLabels = ['approved' => 'goedgekeurd', 'pending' => 'ter beoordeling', 'rejected' => 'afgekeurd'];
@endphp
<div class="mb-[14px] grid gap-[9px] rounded-md border border-ink-100 px-4 py-3 text-[13px] dark:border-dark-border" style="font-variant-numeric: tabular-nums">
    @foreach ($months as $month)
        <div class="flex justify-between">
            <span class="text-ink-900 dark:text-dark-text1">{{ ucfirst($month['label']) }}</span>
            <span class="text-ink-500 dark:text-dark-text2">
                {{ number_format($month['hours'], 2, ',', '.') }} uur
                @if ($showApprovalStatus)
                    &middot; {{ $approvalLabels[$month['approval_status']] ?? 'niet ingediend' }}
                @endif
            </span>
        </div>
    @endforeach
</div>
