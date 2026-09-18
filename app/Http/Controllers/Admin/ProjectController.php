<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProjectRequest;
use App\Http\Requests\Admin\UpdateProjectRequest;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'active');
        $clientId = $request->query('client_id');

        $projects = Project::query()
            ->with('client')
            ->when(in_array($status, ['active', 'archived'], true), fn ($query) => $query->where('status', $status))
            ->when($clientId, fn ($query) => $query->where('client_id', $clientId))
            ->orderBy('name')
            ->get();

        return view('admin.projects.index', [
            'projects' => $projects,
            'clients' => Client::query()->orderBy('name')->get(),
            'status' => $status,
            'clientId' => $clientId,
        ]);
    }

    public function create(): View
    {
        return view('admin.projects.create', [
            'clients' => Client::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        Project::query()->create($request->validated());

        return redirect()
            ->route('admin.projects.index')
            ->with('status', 'Project toegevoegd.');
    }

    public function edit(Project $project): View
    {
        return view('admin.projects.edit', [
            'project' => $project,
            'clients' => Client::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return redirect()
            ->route('admin.projects.index')
            ->with('status', 'Project bijgewerkt.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        if ($project->timeEntries()->exists()) {
            return back()->with('error', 'Dit project heeft nog geregistreerde uren en kan niet verwijderd worden.');
        }

        $project->delete();

        return redirect()
            ->route('admin.projects.index')
            ->with('status', 'Project verwijderd.');
    }
}
