@props(['class' => ''])

<button
    type="button"
    data-theme-toggle
    aria-label="Wissel tussen licht en donker thema"
    {{ $attributes->merge(['class' => 'inline-flex h-7 w-7 items-center justify-center rounded-sm text-ink-500 hover:text-brand-800 dark:text-dark-text2 dark:hover:text-brand-darkhover ' . $class]) }}
>
    <svg class="hidden h-4 w-4 dark:block" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4.24 2.76a1 1 0 010 1.41l-.71.71a1 1 0 11-1.41-1.41l.71-.71a1 1 0 011.41 0zM17 9a1 1 0 110 2h-1a1 1 0 110-2h1zM6.34 13.66a1 1 0 010 1.41l-.71.71a1 1 0 11-1.41-1.41l.71-.71a1 1 0 011.41 0zM10 15a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zm5.66-1.34a1 1 0 011.41 0l.71.71a1 1 0 11-1.41 1.41l-.71-.71a1 1 0 010-1.41zM4 9a1 1 0 110 2H3a1 1 0 110-2h1zm1.63-4.24a1 1 0 011.41 0l.71.71A1 1 0 116.34 6.9l-.71-.71a1 1 0 010-1.41zM10 6a4 4 0 100 8 4 4 0 000-8z" />
    </svg>
    <svg class="block h-4 w-4 dark:hidden" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path d="M17.293 13.293a8 8 0 01-10.586-10.586 8.003 8.003 0 1010.586 10.586z" />
    </svg>
</button>
