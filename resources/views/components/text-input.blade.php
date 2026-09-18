@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-[3px] border border-ink-300 bg-white px-3 py-[9px] text-sm text-ink-900 placeholder:text-ink-400 focus:border-brand-600 focus:outline-none focus:ring-[3px] focus:ring-brand-400/30 disabled:cursor-not-allowed disabled:bg-ink-100 disabled:text-ink-400 dark:border-dark-borderfield dark:bg-dark-bg dark:text-dark-text1 dark:placeholder:text-dark-text3 dark:focus:border-brand-darkfg dark:focus:ring-brand-darkfg/30']) }}>
