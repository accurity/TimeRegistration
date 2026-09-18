@php $project ??= new \App\Models\Project(); @endphp

<div class="mb-[14px]">
    <x-input-label for="name" value="Projectnaam" />
    <x-text-input id="name" name="name" type="text" class="block w-full" :value="old('name', $project->name)" required />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div class="mb-[14px] grid grid-cols-2 gap-[14px]">
    <div>
        <x-input-label for="client_id" value="Klant" />
        <x-select-input id="client_id" name="client_id" class="block w-full">
            <option value="">Kies een klant…</option>
            @foreach ($clients as $client)
                <option value="{{ $client->id }}" @selected(old('client_id', $project->client_id) == $client->id)>{{ $client->name }}</option>
            @endforeach
        </x-select-input>
        <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="rate" value="Uurtarief (€)" />
        <x-text-input id="rate" name="rate" type="number" step="0.01" min="0.01" class="block w-full" :value="old('rate', $project->rate)" required />
        <x-input-error :messages="$errors->get('rate')" class="mt-2" />
    </div>
</div>

<div class="mb-[22px]">
    <x-input-label for="status" value="Status" />
    <x-select-input id="status" name="status" class="block w-full">
        <option value="active" @selected(old('status', $project->status ?? 'active') === 'active')>Actief</option>
        <option value="archived" @selected(old('status', $project->status ?? 'active') === 'archived')>Gearchiveerd</option>
    </x-select-input>
    <x-input-error :messages="$errors->get('status')" class="mt-2" />
</div>
