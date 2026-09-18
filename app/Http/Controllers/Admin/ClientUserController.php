<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClientUserRequest;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ClientUserController extends Controller
{
    public function index(Client $client): View
    {
        return view('admin.clients.users.index', [
            'client' => $client,
            'users' => $client->users()->orderBy('name')->get(),
        ]);
    }

    public function create(Client $client): View
    {
        return view('admin.clients.users.create', [
            'client' => $client,
        ]);
    }

    public function store(StoreClientUserRequest $request, Client $client): RedirectResponse
    {
        $user = User::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make(Str::random(40)),
            'role' => 'client',
            'client_id' => $client->id,
        ]);

        Password::sendResetLink(['email' => $user->email]);

        return redirect()
            ->route('admin.clients.users.index', $client)
            ->with('status', 'Contactpersoon toegevoegd. Wachtwoord-instellink verstuurd.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->isClient(), 404);

        $client = $user->client_id;

        $user->delete();

        return redirect()
            ->route('admin.clients.users.index', $client)
            ->with('status', 'Contactpersoon verwijderd.');
    }
}
