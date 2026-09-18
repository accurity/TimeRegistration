<x-admin-layout>
    <div class="mb-[22px]">
        <h1 class="mb-1 font-display text-[28px] font-bold leading-9 text-ink-900 dark:text-dark-text1">Instellingen</h1>
        <p class="text-[13px] text-ink-500 dark:text-dark-text2">Bedrijfsgegevens en facturatie-standaarden.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="max-w-2xl rounded-md border border-ink-200 bg-white dark:border-dark-border dark:bg-dark-surface1">
        <div class="border-b border-ink-200 px-[22px] py-[18px] dark:border-dark-border">
            <h2 class="font-display text-base font-bold leading-6 text-ink-900 dark:text-dark-text1">Bedrijfsgegevens</h2>
        </div>

        <form method="POST" action="{{ route('admin.settings.update') }}" class="px-[22px] py-5">
            @csrf
            @method('PUT')

            <div class="mb-[14px]">
                <x-input-label for="company_name" value="Bedrijfsnaam" />
                <x-text-input id="company_name" name="company_name" type="text" class="block w-full" :value="old('company_name', $setting->company_name)" required />
                <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
            </div>

            <div class="mb-[14px]">
                <x-input-label for="address" value="Adres" />
                <x-text-input id="address" name="address" type="text" class="block w-full" :value="old('address', $setting->address)" required />
                <x-input-error :messages="$errors->get('address')" class="mt-2" />
            </div>

            <div class="mb-[14px] grid grid-cols-2 gap-[14px]">
                <div>
                    <x-input-label for="postal_code" value="Postcode" />
                    <x-text-input id="postal_code" name="postal_code" type="text" class="block w-full" :value="old('postal_code', $setting->postal_code)" required />
                    <x-input-error :messages="$errors->get('postal_code')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="city" value="Plaats" />
                    <x-text-input id="city" name="city" type="text" class="block w-full" :value="old('city', $setting->city)" required />
                    <x-input-error :messages="$errors->get('city')" class="mt-2" />
                </div>
            </div>

            <div class="mb-[14px] grid grid-cols-2 gap-[14px]">
                <div>
                    <x-input-label for="kvk_number" value="KvK-nummer" />
                    <x-text-input id="kvk_number" name="kvk_number" type="text" class="block w-full" :value="old('kvk_number', $setting->kvk_number)" />
                    <x-input-error :messages="$errors->get('kvk_number')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="btw_number" value="Btw-nummer" />
                    <x-text-input id="btw_number" name="btw_number" type="text" class="block w-full" :value="old('btw_number', $setting->btw_number)" />
                    <x-input-error :messages="$errors->get('btw_number')" class="mt-2" />
                </div>
            </div>

            <div class="mb-[22px]">
                <x-input-label for="iban" value="IBAN" />
                <x-text-input id="iban" name="iban" type="text" class="block w-full" :value="old('iban', $setting->iban)" />
                <x-input-error :messages="$errors->get('iban')" class="mt-2" />
            </div>

            <div class="mb-[22px] grid grid-cols-2 gap-[14px]">
                <div>
                    <x-input-label for="default_payment_term_days" value="Standaard betalingstermijn (dagen)" />
                    <x-text-input id="default_payment_term_days" name="default_payment_term_days" type="number" min="1" max="365" class="block w-full" :value="old('default_payment_term_days', $setting->default_payment_term_days)" required />
                    <x-input-error :messages="$errors->get('default_payment_term_days')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="default_vat_percentage" value="Standaard btw-percentage" />
                    <x-text-input id="default_vat_percentage" name="default_vat_percentage" type="number" min="0" max="100" step="0.01" class="block w-full" :value="old('default_vat_percentage', $setting->default_vat_percentage)" required />
                    <x-input-error :messages="$errors->get('default_vat_percentage')" class="mt-2" />
                </div>
            </div>

            <div class="flex gap-[10px] border-t border-ink-100 pt-[18px] dark:border-dark-border">
                <x-primary-button>Opslaan</x-primary-button>
            </div>
        </form>
    </div>
</x-admin-layout>
