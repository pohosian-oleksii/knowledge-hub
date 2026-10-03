<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\ContextEntryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkStoreContextEntryRequest;
use App\Http\Requests\StoreContextEntryRequest;
use App\Http\Requests\UpdateContextEntryRequest;
use App\Http\Resources\ContextEntryResource;
use App\Models\ContextEntry;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

final class ContextEntryController extends Controller
{
    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        $query = $project->contextEntries()->with('images');

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('tags')) {
            $tags = array_filter(explode(',', (string) $request->query('tags')));

            $query->where(function ($query) use ($tags): void {
                foreach ($tags as $tag) {
                    $query->orWhereJsonContains('tags', trim($tag));
                }
            });
        }

        if ($request->filled('importance_min')) {
            $query->where('importance', '>=', (int) $request->query('importance_min'));
        }

        $entries = $query->orderByDesc('importance')->orderByDesc('updated_at')->get();

        return ContextEntryResource::collection($entries);
    }

    public function summary(Project $project): Response
    {
        $entries = $project->contextEntries()
            ->orderByDesc('importance')
            ->orderByDesc('updated_at')
            ->get()
            ->groupBy(static fn (ContextEntry $entry): string => $entry->type->value);

        $markdown = $this->buildSummaryMarkdown($entries);

        return response($markdown, 200)->header('Content-Type', 'text/markdown');
    }

    public function store(StoreContextEntryRequest $request, Project $project): ContextEntryResource
    {
        $entry = $this->upsert($project, $request->validated());

        return new ContextEntryResource($entry);
    }

    public function bulk(BulkStoreContextEntryRequest $request, Project $project): AnonymousResourceCollection
    {
        $entries = collect($request->validated())
            ->map(fn (array $data): ContextEntry => $this->upsert($project, $data));

        return ContextEntryResource::collection($entries);
    }

    public function show(Project $project, int $id): ContextEntryResource
    {
        return new ContextEntryResource($project->contextEntries()->with('images')->findOrFail($id));
    }

    public function update(UpdateContextEntryRequest $request, Project $project, int $id): ContextEntryResource
    {
        $entry = $project->contextEntries()->findOrFail($id);
        $entry->update($request->validated());

        return new ContextEntryResource($entry);
    }

    public function destroy(Project $project, int $id): Response
    {
        $project->contextEntries()->findOrFail($id)->delete();

        return response()->noContent();
    }

    private function upsert(Project $project, array $data): ContextEntry
    {
        $key = ContextEntry::deriveKey($data['type'], $data['title']);

        return ContextEntry::updateOrCreate(
            ['project_id' => $project->id, 'key' => $key],
            [...$data, 'project_id' => $project->id]
        );
    }

    private function buildSummaryMarkdown(Collection $groupedEntries): string
    {
        $sections = [];

        foreach (ContextEntryType::cases() as $type) {
            $entries = $groupedEntries->get($type->value);

            if (! $entries || $entries->isEmpty()) {
                continue;
            }

            $lines = ["## {$type->value}", ''];

            foreach ($entries as $entry) {
                $lines[] = "### {$entry->title}";
                $lines[] = '';
                $lines[] = sprintf(
                    '_importance: %d · tags: %s · source: %s · updated: %s_',
                    $entry->importance,
                    $entry->tags === [] ? '—' : implode(', ', $entry->tags),
                    $entry->source_agent ?? '—',
                    $entry->updated_at?->toDateTimeString() ?? '—'
                );
                $lines[] = '';
                $lines[] = $entry->content;
                $lines[] = '';
            }

            $sections[] = implode("\n", $lines);
        }

        return implode("\n", $sections);
    }
}
