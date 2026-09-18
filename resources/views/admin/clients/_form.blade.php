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
