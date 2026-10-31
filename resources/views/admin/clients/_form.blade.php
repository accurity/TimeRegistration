@php $client ??= new \App\Models\Client(); @endphp

<div class="mb-[14px] grid grid-cols-2 gap-[14px]">
    <div>
        <x-input-label for="name" value="Bedrijfsnaam" />
        <x-text-input id="name" name="name" type="text" class="block w-full" :value="old('name', $client->name)" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="invoice_abbreviation" value="Factuur-afkorting" />
        <x-text-input id="invoice_abbreviation" name="invoice_abbreviation" type="text" class="block w-full" :value="old('invoice_abbreviation', $client->invoice_abbreviation)" required />
        <x-input-error :messages="$errors->get('invoice_abbreviation')" class="mt-2" />
    </div>
</div>

<div class="mb-[14px]">
    <x-input-label for="address" value="Adres" />
    <x-text-input id="address" name="address" type="text" class="block w-full" :value="old('address', $client->address)" required />
    <x-input-error :messages="$errors->get('address')" class="mt-2" />
</div>

<div class="mb-[22px] grid grid-cols-2 gap-[14px]">
    <div>
        <x-input-label for="postal_code" value="Postcode" />
        <x-text-input id="postal_code" name="postal_code" type="text" class="block w-full" :value="old('postal_code', $client->postal_code)" required />
        <x-input-error :messages="$errors->get('postal_code')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="city" value="Plaats" />
        <x-text-input id="city" name="city" type="text" class="block w-full" :value="old('city', $client->city)" required />
        <x-input-error :messages="$errors->get('city')" class="mt-2" />
    </div>
</div>

<div class="mb-[22px] grid grid-cols-2 gap-[14px]">
    <div>
        <x-input-label for="kvk_number" value="KvK-nummer" />
        <x-text-input id="kvk_number" name="kvk_number" type="text" class="block w-full" :value="old('kvk_number', $client->kvk_number)" />
        <x-input-error :messages="$errors->get('kvk_number')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="btw_number" value="Btw-nummer" />
        <x-text-input id="btw_number" name="btw_number" type="text" class="block w-full" :value="old('btw_number', $client->btw_number)" />
        <x-input-error :messages="$errors->get('btw_number')" class="mt-2" />
    </div>
</div>

<div class="mb-[22px]">
    <input type="hidden" name="requires_approval" value="0">
    <label for="requires_approval" class="inline-flex items-center gap-2 text-sm text-ink-700 dark:text-dark-text2">
        <input id="requires_approval" type="checkbox" name="requires_approval" value="1" class="h-3.5 w-3.5 rounded-[2px] border-ink-300 text-brand-600 focus:ring-brand-400 dark:border-dark-borderfield dark:bg-dark-bg" @checked(old('requires_approval', $client->requires_approval ?? true))>
        Goedkeuring van uren vereist
    </label>
    <p class="mt-1 text-[13px] text-ink-500 dark:text-dark-text2">Uitgezet: uren hoeven niet ter goedkeuring te worden aangeboden en een factuur kan meteen worden opgesteld.</p>
    <x-input-error :messages="$errors->get('requires_approval')" class="mt-2" />
</div>
