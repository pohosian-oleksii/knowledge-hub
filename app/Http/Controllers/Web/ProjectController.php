<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\ContextEntryType;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class ProjectController extends Controller
{
    public function show(Request $request, Project $project): View
    {
        $query = $project->contextEntries()->with('images');

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('tag')) {
            $query->whereJsonContains('tags', $request->string('tag')->trim()->toString());
        }

        if ($request->filled('importance_min')) {
            $query->where('importance', '>=', (int) $request->query('importance_min'));
        }

        if ($request->filled('q')) {
            $search = $request->string('q')->trim()->toString();
            $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $entries = $query->orderByDesc('importance')->orderByDesc('updated_at')->get();

        $groupedEntries = $entries->groupBy(static fn ($entry) => $entry->type->value);

        return view('projects.show', [
            'project' => $project,
            'groupedEntries' => $groupedEntries,
            'types' => ContextEntryType::cases(),
            'filters' => $request->only(['type', 'tag', 'importance_min', 'q']),
        ]);
    }
}
