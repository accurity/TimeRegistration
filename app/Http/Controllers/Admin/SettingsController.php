<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'setting' => Setting::current(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        Setting::current()->update($request->validated());

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', 'Instellingen opgeslagen.');
    }
}
