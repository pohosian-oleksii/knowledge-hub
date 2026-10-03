# Agents Knowledge Hub

Shared memory for AI agents. One agent analyzes a project once, pushes what it learned here, and every agent after it reads that instead of re-scanning the repo. 🧠

Two parts:

- **Laravel app** — REST API (`/api/v1`) plus a read-only web dashboard for browsing projects and their context.
- **MCP server** (`mcp-server/`) — wraps the API as MCP tools, so Claude Code / Claude Desktop can read and write context directly.

## Stack

PHP 8.4 (FPM), Laravel 12, MySQL 8, nginx, Node + TypeScript for the MCP server. Everything runs in Docker; local domain comes from OrbStack.

## Quick start

```bash
cp .env.example .env
make up
docker compose exec php php artisan key:generate
```

`make up` builds the containers, installs Composer deps, runs migrations, restarts nginx and builds the MCP server.

Then:

- Dashboard: https://agents-knowledge-hub.local (or http://localhost:8000)
- API: https://agents-knowledge-hub.local/api/v1
- MCP: https://agents-knowledge-hub.local/mcp

Other targets: `make down`, `make shell`, `make migrate`, `make mcp-build`, `make mcp-dev`.

## Data model

```
projects ──< context_entries ──< context_images
```

**Project** — identified by a unique `slug`. Fields: `name`, `description`, `overview`, `repo_path`.

**ContextEntry** — one durable fact about a project. Fields: `type`, `title`, `content` (Markdown), `tags`, `source_agent`, `importance` (1–5, default 3).

Allowed `type` values: `architecture`, `convention`, `decision`, `gotcha`, `dependency`, `glossary`, `todo`, `other`.

Entries upsert by `key`, which is derived from `type` + `title`. Same type and title → the existing entry gets updated, no duplicate. Re-running an analyzer is safe.

**ContextImage** — screenshots or diagrams attached to an entry. Files live on disk under `storage/app/private/projects/{project_id}/context/{entry_id}/`. Only metadata goes to the DB.

## API

Base: `/api/v1`. All routes take the project `slug`.

| Method | Path | What |
|---|---|---|
| GET | `/projects` | List projects |
| POST | `/projects` | Create or update by `slug` |
| GET | `/projects/{slug}` | Show project |
| PATCH | `/projects/{slug}` | Update project |
| GET | `/projects/{slug}/context` | List entries. Filters: `type`, `tags` (comma-separated), `importance_min` |
| GET | `/projects/{slug}/context/summary` | All entries as one Markdown doc, grouped by type |
| POST | `/projects/{slug}/context` | Create or update one entry |
| POST | `/projects/{slug}/context/bulk` | Same, array of entries |
| GET | `/projects/{slug}/context/{id}` | Show entry |
| PATCH | `/projects/{slug}/context/{id}` | Update entry |
| DELETE | `/projects/{slug}/context/{id}` | Delete entry |
| GET | `/projects/{slug}/context/{id}/images` | List images |
| POST | `/projects/{slug}/context/{id}/images` | Upload image (`filename`, `image_base64`) |
| DELETE | `/projects/{slug}/context/{id}/images/{image_id}` | Delete image |
| GET | `/context-images/{image_id}` | Serve image file |

Example:

```bash
curl -X POST https://agents-knowledge-hub.local/api/v1/projects/my-app/context \
  -H "Content-Type: application/json" \
  -d '{
    "type": "gotcha",
    "title": "Queue worker needs restart after deploy",
    "content": "Workers cache the app code. Run `php artisan queue:restart` on every deploy.",
    "tags": ["queue", "deploy"],
    "importance": 4,
    "source_agent": "project-context-analyzer"
  }'
```

## Connecting Claude

Claude Code (HTTP transport, runs in Docker):

```bash
claude mcp add --transport http agents-knowledge-hub https://agents-knowledge-hub.local/mcp
```

Claude Desktop: Settings → Connectors → Add custom connector → `https://agents-knowledge-hub.local/mcp`.

stdio setup, the full tool list and image upload details are in [mcp-server/README.md](mcp-server/README.md).

### Agents and skills

`.claude/` ships the agents and skills that work with the hub. Claude Code picks them up automatically inside this repo. To use them in other projects, copy them into `~/.claude/agents/` and `~/.claude/skills/`.

- `agents/project-context-analyzer.md` scans a repo and pushes structured entries into the hub. Point it elsewhere with `AGENTS_HUB_URL`.
- `skills/spec-loop` drives a task end to end: requirements → plan → implement → verify → review, looping on failure. It uses the `requirements-analyst`, `code-implementer`, `requirements-verifier` and `code-reviewer` agents, which ship alongside it.

## Guidelines for writing context

The hub is only useful if entries stay high-signal. Rules for agents and humans alike:

- **Write what isn't obvious from the code.** "Uses Laravel" is noise. "Orders are soft-deleted, but reports query `withTrashed()` on purpose" is the good stuff.
- **One fact per entry.** Small entries upsert cleanly and filter well. A 3-page essay doesn't.
- **Pick the right type.** `gotcha` for traps, `decision` for the why behind a choice, `convention` for house rules. Use `other` as a last resort.
- **Titles are keys.** Change a title and you get a new entry, not an update. Keep titles stable and descriptive.
- **Be honest with `importance`.** 5 = breaks prod if ignored. 1 = nice to know. If everything is a 5, nothing is.
- **Tag for retrieval.** Short lowercase tags agents will filter by: `auth`, `queue`, `billing`, `docker`.
- **Set `source_agent`.** Makes it easy to find and clean up what a misbehaving agent wrote.
- **No secrets.** No API keys, passwords, tokens or customer data. Ever.
- **Delete stale entries.** Wrong context is worse than none.

## Development

PHP rules: `declare(strict_types=1);` in every file, PSR-12, `final` classes, FormRequests for validation, enums for fixed value sets.

Tests:

```bash
docker compose exec php php artisan test
```

## Security

No auth on the API or the MCP endpoint. Anyone who can reach the host can read and write everything. Fine for local dev on your own machine — put auth in front before exposing it anywhere else.
