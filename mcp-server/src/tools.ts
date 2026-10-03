import { readFile } from "node:fs/promises";
import { basename } from "node:path";
import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { api, ApiError } from "./client.js";

const CONTEXT_ENTRY_TYPES = [
    "architecture",
    "convention",
    "decision",
    "gotcha",
    "dependency",
    "glossary",
    "todo",
    "other",
] as const;

type ToolResult = { content: Array<{ type: "text"; text: string }>; isError?: boolean };

function ok(data: unknown): ToolResult {
    if (data === undefined) {
        return { content: [{ type: "text", text: "OK" }] };
    }

    const text = typeof data === "string" ? data : JSON.stringify(data, null, 2);

    return { content: [{ type: "text", text }] };
}

async function run(fn: () => Promise<unknown>): Promise<ToolResult> {
    try {
        return ok(await fn());
    } catch (error) {
        if (error instanceof ApiError) {
            return { content: [{ type: "text", text: `API error ${error.status}: ${JSON.stringify(error.body)}` }], isError: true };
        }

        return { content: [{ type: "text", text: error instanceof Error ? error.message : String(error) }], isError: true };
    }
}

const contextEntryFields = {
    type: z.enum(CONTEXT_ENTRY_TYPES).describe("Category of the context entry"),
    title: z.string().describe("Short title; combined with type to derive the upsert key"),
    content: z.string().describe("Markdown body of the entry"),
    tags: z.array(z.string()).optional().describe("Free-form tags"),
    source_agent: z.string().optional().describe("Identifier of the writing agent/session"),
    importance: z.number().int().min(1).max(5).optional().describe("1-5, default 3. Higher is read first."),
};

export function createServer(): McpServer {
    const server = new McpServer({
        name: "agents-knowledge-hub",
        version: "1.0.0",
    });

    server.tool(
        "list_projects",
        "List all projects registered in the Agents Knowledge Hub, with their context entry counts.",
        {},
        async () => run(() => api.get("/projects"))
    );

    server.tool(
        "get_project",
        "Get one project by slug, including its overview narrative.",
        { slug: z.string().describe("Project slug") },
        async ({ slug }) => run(() => api.get(`/projects/${encodeURIComponent(slug)}`))
    );

    server.tool(
        "upsert_project",
        "Create a project, or update it if a project with the same slug already exists.",
        {
            name: z.string().describe("Project name"),
            slug: z.string().describe("Unique URL identifier; also the upsert key"),
            description: z.string().optional().describe("One-line subtitle"),
            overview: z.string().optional().describe("Markdown narrative: what the project does, key components/workers, entry points, tech stack"),
            repo_path: z.string().optional().describe("Local path or git URL"),
        },
        async (params) => run(() => api.post("/projects", params))
    );

    server.tool(
        "update_project",
        "Partially update an existing project, identified by its current slug.",
        {
            slug: z.string().describe("Current slug identifying the project to update"),
            name: z.string().optional(),
            new_slug: z.string().optional().describe("If set, renames the project's slug"),
            description: z.string().optional(),
            overview: z.string().optional(),
            repo_path: z.string().optional(),
        },
        async ({ slug, new_slug, ...rest }) =>
            run(() =>
                api.patch(`/projects/${encodeURIComponent(slug)}`, {
                    ...rest,
                    ...(new_slug !== undefined ? { slug: new_slug } : {}),
                })
            )
    );

    server.tool(
        "list_context_entries",
        "List a project's context entries, optionally filtered by type, tags, or minimum importance.",
        {
            project_slug: z.string(),
            type: z.enum(CONTEXT_ENTRY_TYPES).optional(),
            tags: z.array(z.string()).optional().describe("Entries matching any of these tags"),
            importance_min: z.number().int().min(1).max(5).optional(),
        },
        async ({ project_slug, type, tags, importance_min }) =>
            run(() =>
                api.get(`/projects/${encodeURIComponent(project_slug)}/context`, {
                    type,
                    tags: tags?.join(","),
                    importance_min,
                })
            )
    );

    server.tool(
        "get_context_summary",
        "Get the full context dump for a project as markdown, grouped by type and sorted by importance then recency. Use this to load a project's full context in one call.",
        { project_slug: z.string() },
        async ({ project_slug }) => run(() => api.get(`/projects/${encodeURIComponent(project_slug)}/context/summary`))
    );

    server.tool(
        "create_context_entry",
        "Create or update one context entry for a project. Upserts by type+title, so calling this again with the same type and title updates the existing entry instead of duplicating it.",
        { project_slug: z.string(), ...contextEntryFields },
        async ({ project_slug, ...body }) => run(() => api.post(`/projects/${encodeURIComponent(project_slug)}/context`, body))
    );

    server.tool(
        "bulk_create_context_entries",
        "Create or update many context entries for a project in one call. Same upsert semantics as create_context_entry — use this when dumping a whole scan of findings at once.",
        {
            project_slug: z.string(),
            entries: z.array(z.object(contextEntryFields)).describe("Entries to upsert"),
        },
        async ({ project_slug, entries }) => run(() => api.post(`/projects/${encodeURIComponent(project_slug)}/context/bulk`, entries))
    );

    server.tool(
        "get_context_entry",
        "Get one context entry by id.",
        { project_slug: z.string(), id: z.number().int() },
        async ({ project_slug, id }) => run(() => api.get(`/projects/${encodeURIComponent(project_slug)}/context/${id}`))
    );

    server.tool(
        "update_context_entry",
        "Partially update one context entry by id.",
        {
            project_slug: z.string(),
            id: z.number().int(),
            type: z.enum(CONTEXT_ENTRY_TYPES).optional(),
            title: z.string().optional(),
            content: z.string().optional(),
            tags: z.array(z.string()).optional(),
            source_agent: z.string().optional(),
            importance: z.number().int().min(1).max(5).optional(),
        },
        async ({ project_slug, id, ...body }) => run(() => api.patch(`/projects/${encodeURIComponent(project_slug)}/context/${id}`, body))
    );

    server.tool(
        "delete_context_entry",
        "Delete one context entry by id.",
        { project_slug: z.string(), id: z.number().int() },
        async ({ project_slug, id }) => run(() => api.delete(`/projects/${encodeURIComponent(project_slug)}/context/${id}`))
    );

    server.tool(
        "list_context_images",
        "List images attached to one context entry.",
        { project_slug: z.string(), context_entry_id: z.number().int() },
        async ({ project_slug, context_entry_id }) =>
            run(() => api.get(`/projects/${encodeURIComponent(project_slug)}/context/${context_entry_id}/images`))
    );

    server.tool(
        "upload_context_image",
        "Attach an image (PNG/JPEG/GIF/WebP, max 10MB) to a context entry. Images are stored on local disk, never in the database. " +
            "Pass file_path to read a local file on this machine (only works with the stdio transport running on the same host — " +
            "the HTTP/Docker transport has no access to the host filesystem). Otherwise pass base64_content directly with filename.",
        {
            project_slug: z.string(),
            context_entry_id: z.number().int(),
            file_path: z.string().optional().describe("Absolute path to a local image file to read and upload"),
            base64_content: z.string().optional().describe("Raw base64 image data, used if file_path is not given"),
            filename: z.string().optional().describe("Filename to store; inferred from file_path if omitted"),
        },
        async ({ project_slug, context_entry_id, file_path, base64_content, filename }) =>
            run(async () => {
                let base64 = base64_content;
                let resolvedFilename = filename;

                if (file_path) {
                    const buffer = await readFile(file_path);
                    base64 = buffer.toString("base64");
                    resolvedFilename ??= basename(file_path);
                }

                if (!base64) {
                    throw new Error("Provide either file_path or base64_content");
                }

                if (!resolvedFilename) {
                    throw new Error("filename is required when uploading base64_content directly");
                }

                return api.post(`/projects/${encodeURIComponent(project_slug)}/context/${context_entry_id}/images`, {
                    filename: resolvedFilename,
                    image_base64: base64,
                });
            })
    );

    server.tool(
        "delete_context_image",
        "Delete one image attached to a context entry, removing it from disk.",
        { project_slug: z.string(), context_entry_id: z.number().int(), image_id: z.number().int() },
        async ({ project_slug, context_entry_id, image_id }) =>
            run(() =>
                api.delete(`/projects/${encodeURIComponent(project_slug)}/context/${context_entry_id}/images/${image_id}`)
            )
    );

    return server;
}
