const BASE_URL = (process.env.AGENTS_HUB_API_URL ?? "http://localhost:8000/api/v1").replace(/\/+$/, "");

export class ApiError extends Error {
    constructor(
        public readonly status: number,
        public readonly body: unknown
    ) {
        super(`Agents Knowledge Hub API error (${status}): ${JSON.stringify(body)}`);
        this.name = "ApiError";
    }
}

type Query = Record<string, string | number | undefined>;

function buildUrl(path: string, query?: Query): string {
    const url = new URL(BASE_URL + path);

    for (const [key, value] of Object.entries(query ?? {})) {
        if (value !== undefined) {
            url.searchParams.set(key, String(value));
        }
    }

    return url.toString();
}

async function request<T>(method: string, path: string, options: { query?: Query; body?: unknown } = {}): Promise<T> {
    const response = await fetch(buildUrl(path, options.query), {
        method,
        headers: {
            Accept: "application/json",
            ...(options.body !== undefined ? { "Content-Type": "application/json" } : {}),
        },
        body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
    });

    if (response.status === 204) {
        return undefined as T;
    }

    const isJson = response.headers.get("content-type")?.includes("json");
    const payload = isJson ? await response.json() : await response.text();

    if (!response.ok) {
        throw new ApiError(response.status, payload);
    }

    return payload as T;
}

export const api = {
    get: <T>(path: string, query?: Query) => request<T>("GET", path, { query }),
    post: <T>(path: string, body?: unknown) => request<T>("POST", path, { body }),
    patch: <T>(path: string, body?: unknown) => request<T>("PATCH", path, { body }),
    delete: <T>(path: string) => request<T>("DELETE", path),
};
