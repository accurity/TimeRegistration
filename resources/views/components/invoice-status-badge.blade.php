@props(['status', 'paymentStatus' => null])

@if ($status === 'draft')
    <span {{ $attributes->merge(['class' => 'inline-block rounded-[3px] border border-status-gray-border bg-status-gray-bg px-[9px] py-[3px] text-[12px] font-semibold text-status-gray-fg dark:border-status-gray-border-dark dark:bg-status-gray-bg-dark dark:text-status-gray-fg-dark']) }}>Concept</span>
@elseif ($status === 'cancelled')
    <span {{ $attributes->merge(['class' => 'inline-block rounded-[3px] border border-status-red-border bg-status-red-bg px-[9px] py-[3px] text-[12px] font-semibold text-status-red-fg dark:border-status-red-border-dark dark:bg-status-red-bg-dark dark:text-status-red-fg-dark']) }}>Geannuleerd</span>
@elseif ($paymentStatus === 'paid')
    <span {{ $attributes->merge(['class' => 'inline-block rounded-[3px] border border-status-green-border bg-status-green-bg px-[9px] py-[3px] text-[12px] font-semibold text-status-green-fg dark:border-status-green-border-dark dark:bg-status-green-bg-dark dark:text-status-green-fg-dark']) }}>Betaald</span>
@else
    <span {{ $attributes->merge(['class' => 'inline-block rounded-[3px] border border-status-amber-border bg-status-amber-bg px-[9px] py-[3px] text-[12px] font-semibold text-status-amber-fg dark:border-status-amber-border-dark dark:bg-status-amber-bg-dark dark:text-status-amber-fg-dark']) }}>Openstaand</span>
@endif
