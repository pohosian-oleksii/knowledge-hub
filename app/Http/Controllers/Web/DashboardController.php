<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Contracts\View\View;

final class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $projects = Project::withCount('contextEntries')
            ->orderByDesc('updated_at')
            ->get();

        return view('dashboard', ['projects' => $projects]);
    }
}
