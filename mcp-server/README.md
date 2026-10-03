# Agents Knowledge Hub MCP Server

MCP server wrapping the Agents Knowledge Hub REST API (`/api/v1`). Talks to the API only — no direct DB access.

Two transports, same tools, same source (`src/tools.ts`):

- **HTTP** (`src/http.ts`) — runs as the `mcp` service in `docker-compose.yml`, proxied by nginx at `https://agents-knowledge-hub.local/mcp`. This is the one to use day to day.
- **stdio** (`src/index.ts`) — spawned directly by a local MCP client. Useful for the MCP Inspector or if you'd rather not depend on the domain being reachable.

## Setup

`docker compose up -d --build` (or `make up`) builds and starts the `mcp` container automatically. `make up` also restarts nginx so it picks up `docker/nginx/site.conf` — it won't reload a bind-mounted config on its own.

For the stdio build (local host, not containerized):

```bash
make mcp-build
```

or directly:

```bash
cd mcp-server
npm install
npm run build
```

## Config

`mcp` container reads `AGENTS_HUB_API_URL`, set in `docker-compose.yml` to `http://nginx/api/v1` (internal Docker network). For the stdio build run standalone on the host, it defaults to `http://localhost:8000/api/v1`.

## Claude Code

```bash
claude mcp add --transport http agents-knowledge-hub https://agents-knowledge-hub.local/mcp
```

## Claude Desktop

Settings → Connectors → Add custom connector → paste `https://agents-knowledge-hub.local/mcp`.

## stdio, if you need it instead

Claude Code:

```bash
claude mcp add agents-knowledge-hub -- node /Users/alexpogosyan/WEB/agents-knowledge-hub/mcp-server/dist/index.js
```

Claude Desktop (`claude_desktop_config.json`):

```json
{
  "mcpServers": {
    "agents-knowledge-hub": {
      "command": "node",
      "args": ["/Users/alexpogosyan/WEB/agents-knowledge-hub/mcp-server/dist/index.js"],
      "env": {
        "AGENTS_HUB_API_URL": "http://localhost:8000/api/v1"
      }
    }
  }
}
```

## Tools

| Tool | API call |
|---|---|
| `list_projects` | `GET /projects` |
| `get_project` | `GET /projects/{slug}` |
| `upsert_project` | `POST /projects` |
| `update_project` | `PATCH /projects/{slug}` |
| `list_context_entries` | `GET /projects/{slug}/context` |
| `get_context_summary` | `GET /projects/{slug}/context/summary` |
| `create_context_entry` | `POST /projects/{slug}/context` |
| `bulk_create_context_entries` | `POST /projects/{slug}/context/bulk` |
| `get_context_entry` | `GET /projects/{slug}/context/{id}` |
| `update_context_entry` | `PATCH /projects/{slug}/context/{id}` |
| `delete_context_entry` | `DELETE /projects/{slug}/context/{id}` |
| `list_context_images` | `GET /projects/{slug}/context/{id}/images` |
| `upload_context_image` | `POST /projects/{slug}/context/{id}/images` |
| `delete_context_image` | `DELETE /projects/{slug}/context/{id}/images/{image_id}` |

Images are read from a local `file_path` (stdio transport only — the Docker/HTTP transport has no host filesystem access) or from inline base64, then stored on disk under `storage/app/private/projects/{project_id}/context/{entry_id}/`. Never stored in the database — only metadata (filename, mime type, size) is.

The Laravel API requires no auth (`.env` sets no auth guard on `api/v1`), so this server sends none either — and `https://agents-knowledge-hub.local/mcp` accepts requests from anyone who can resolve that OrbStack domain, no auth in front of it. Fine for local dev; add auth before this is reachable from anywhere but your own machine.
