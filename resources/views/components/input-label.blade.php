@props(['value'])

<label {{ $attributes->merge(['class' => 'mb-1.5 block text-[11px] font-semibold uppercase tracking-[.1em] text-ink-500 dark:text-dark-text2']) }}>
    {{ $value ?? $slot }}
</label>
