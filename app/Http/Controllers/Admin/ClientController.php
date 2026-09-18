<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClientRequest;
use App\Http\Requests\Admin\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(): View
    {
        return view('admin.clients.index', [
            'clients' => Client::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.clients.create');
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        Client::query()->create($request->validated());

        return redirect()
            ->route('admin.clients.index')
            ->with('status', 'Klant toegevoegd.');
    }

    public function edit(Client $client): View
    {
        return view('admin.clients.edit', [
            'client' => $client,
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        return redirect()
            ->route('admin.clients.index')
            ->with('status', 'Klant bijgewerkt.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        if ($client->users()->exists()) {
            return back()->with('error', 'Deze klant heeft nog gekoppelde gebruikers en kan niet verwijderd worden.');
        }

        $client->delete();

        return redirect()
            ->route('admin.clients.index')
            ->with('status', 'Klant verwijderd.');
    }
}
