<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-[3px] border border-status-red-border bg-white px-[18px] py-[9px] text-sm font-semibold text-status-red-fg hover:bg-status-red-bg focus:outline-none focus:ring-[3px] focus:ring-status-red-border dark:border-status-red-border-dark dark:bg-transparent dark:text-status-red-fg-dark dark:hover:bg-status-red-bg-dark']) }}>
    {{ $slot }}
</button>
