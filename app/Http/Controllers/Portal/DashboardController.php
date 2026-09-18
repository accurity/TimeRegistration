<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\MonthlyApproval;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $approvals = MonthlyApproval::query()
            ->whereHas('project', fn ($query) => $query->where('client_id', $request->user()->client_id))
            ->with('project')
            ->orderByDesc('submitted_at')
            ->get();

        return view('portal.dashboard', [
            'approvals' => $approvals,
        ]);
    }
}
