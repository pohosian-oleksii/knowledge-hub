<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\ContextEntryType;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class IndexController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'name' => 'Agents Knowledge Hub API',
            'version' => 'v1',
            'auth' => 'none',
            'guide' => 'Each project has two layers of context. `overview` (on the project resource) is a short human-readable narrative: what the project does, key components/workers, entry points. Write or update it once you have a clear picture of the project as a whole. `context_entries` are granular, individually-typed facts — conventions, decisions, gotchas, dependencies, etc. Write many of these as you discover things. A reading agent should fetch GET /projects/{slug} for the overview and GET /projects/{slug}/context/summary for the full detail dump.',
            'resources' => [
                'project' => [
                    'fields' => [
                        'name' => ['type' => 'string', 'required' => true],
                        'slug' => ['type' => 'string', 'required' => true, 'notes' => 'unique, used as the URL identifier; upsert key on POST /projects'],
                        'description' => ['type' => 'string', 'required' => false, 'notes' => 'one-line subtitle, shown on dashboard cards'],
                        'overview' => ['type' => 'markdown string', 'required' => false, 'notes' => 'human-readable narrative: what the project does, key components/workers, entry points, tech stack'],
                        'repo_path' => ['type' => 'string', 'required' => false, 'notes' => 'local path or git URL'],
                    ],
                ],
                'context_entry' => [
                    'fields' => [
                        'type' => ['type' => 'enum', 'required' => true, 'values' => ContextEntryType::values()],
                        'title' => ['type' => 'string', 'required' => true],
                        'content' => ['type' => 'markdown string', 'required' => true],
                        'tags' => ['type' => 'array<string>', 'required' => false, 'default' => []],
                        'source_agent' => ['type' => 'string', 'required' => false, 'notes' => 'free text identifying the writing agent/session'],
                        'importance' => ['type' => 'integer', 'required' => false, 'default' => 3, 'range' => [1, 5], 'notes' => 'read agents with a limited budget should prefer higher values first'],
                        'key' => ['type' => 'string', 'readonly' => true, 'notes' => 'server-derived from sha1(type + \':\' + slug(title)); POSTing the same type+title again updates this row instead of creating a duplicate'],
                    ],
                ],
            ],
            'endpoints' => [
                ['method' => 'GET', 'path' => '/projects', 'description' => 'List projects', 'response' => 'array<project>'],
                ['method' => 'POST', 'path' => '/projects', 'description' => 'Create or update a project, upsert by slug', 'body' => 'project'],
                ['method' => 'GET', 'path' => '/projects/{slug}', 'description' => 'Get one project, including its overview', 'response' => 'project'],
                ['method' => 'PATCH', 'path' => '/projects/{slug}', 'description' => 'Update a project', 'body' => 'partial<project>'],
                ['method' => 'GET', 'path' => '/projects/{slug}/context', 'description' => 'List context entries', 'query' => ['type' => 'filter by context_entry.type', 'tags' => 'filter by tag, comma-separated', 'importance_min' => 'integer 1-5'], 'response' => 'array<context_entry>'],
                ['method' => 'GET', 'path' => '/projects/{slug}/context/summary', 'description' => 'Markdown of all context entries grouped by type, sorted importance desc then updated_at desc — the full context dump for a reading agent', 'response' => 'text/markdown'],
                ['method' => 'POST', 'path' => '/projects/{slug}/context', 'description' => 'Create or update one context entry, upsert by derived key', 'body' => 'context_entry'],
                ['method' => 'POST', 'path' => '/projects/{slug}/context/bulk', 'description' => 'Same upsert semantics as POST, for dumping a whole scan in one call', 'body' => 'array<context_entry>'],
                ['method' => 'GET', 'path' => '/projects/{slug}/context/{id}', 'description' => 'Get one context entry', 'response' => 'context_entry'],
                ['method' => 'PATCH', 'path' => '/projects/{slug}/context/{id}', 'description' => 'Update one context entry', 'body' => 'partial<context_entry>'],
                ['method' => 'DELETE', 'path' => '/projects/{slug}/context/{id}', 'description' => 'Delete one context entry'],
            ],
        ]);
    }
}
