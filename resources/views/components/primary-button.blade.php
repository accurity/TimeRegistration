<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-[3px] border border-brand-600 bg-brand-600 px-[18px] py-[9px] text-sm font-semibold text-white hover:border-brand-800 hover:bg-brand-800 focus:outline-none focus:ring-[3px] focus:ring-brand-400/30 dark:border-brand-darkfg']) }}>
    {{ $slot }}
</button>
