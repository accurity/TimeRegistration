@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'text-sm font-medium text-status-green-fg dark:text-status-green-fg-dark']) }}>
        {{ $status }}
    </div>
@endif
