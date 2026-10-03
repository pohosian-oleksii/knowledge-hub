@extends('layouts.app')

@section('title', $project->name . ' — Agents Knowledge Hub')

@section('content')
    <div class="mb-8">
        <a href="{{ route('dashboard') }}" class="text-sm text-slate-500 hover:text-slate-700">&larr; All projects</a>
        <h1 class="mt-2 text-2xl font-semibold">{{ $project->name }}</h1>
        @if ($project->description)
            <p class="mt-1 text-slate-500">{{ $project->description }}</p>
        @endif
        @if ($project->repo_path)
            <p class="mt-1 text-xs text-slate-400">{{ $project->repo_path }}</p>
        @endif
    </div>

    @if ($project->overview)
        <section class="mb-10 rounded-lg border border-slate-200 bg-white p-6">
            <div class="prose prose-slate max-w-none prose-headings:font-semibold">
                {!! \App\Support\Markdown::toHtml($project->overview) !!}
            </div>
        </section>
    @endif

    <section>
        <h2 class="mb-4 text-lg font-semibold">Context entries</h2>

        <form method="GET" class="mb-6 flex flex-wrap items-end gap-3 rounded-lg border border-slate-200 bg-white p-4">
            <div>
                <label class="block text-xs font-medium text-slate-500">Type</label>
                <select name="type" class="mt-1 rounded border-slate-300 text-sm">
                    <option value="">All</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(($filters['type'] ?? '') === $type->value)>{{ $type->value }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500">Tag</label>
                <input type="text" name="tag" value="{{ $filters['tag'] ?? '' }}" class="mt-1 rounded border-slate-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500">Min importance</label>
                <select name="importance_min" class="mt-1 rounded border-slate-300 text-sm">
                    <option value="">Any</option>
                    @foreach (range(1, 5) as $level)
                        <option value="{{ $level }}" @selected(($filters['importance_min'] ?? '') == $level)>{{ $level }}+</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-slate-500">Search</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="title or content" class="mt-1 w-full rounded border-slate-300 text-sm">
            </div>
            <button type="submit" class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Filter</button>
            @if (array_filter($filters))
                <a href="{{ route('projects.show', $project) }}" class="text-sm text-slate-500 hover:text-slate-700">Reset</a>
            @endif
        </form>

        @if ($groupedEntries->isEmpty())
            <p class="text-slate-500">No context entries match these filters.</p>
        @endif

        @foreach ($types as $type)
            @continue(! $groupedEntries->has($type->value))

            <div class="mb-8">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-400">{{ $type->value }}</h3>

                <div class="space-y-3">
                    @foreach ($groupedEntries[$type->value] as $entry)
                        <div x-data="{ open: false }" class="rounded-lg border border-slate-200 bg-white">
                            <button type="button" @click="open = !open" class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left">
                                <div>
                                    <p class="font-medium">{{ $entry->title }}</p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        importance {{ $entry->importance }}
                                        @if ($entry->tags)
                                            &middot; {{ implode(', ', $entry->tags) }}
                                        @endif
                                        @if ($entry->source_agent)
                                            &middot; {{ $entry->source_agent }}
                                        @endif
                                        &middot; updated {{ $entry->updated_at->diffForHumans() }}
                                    </p>
                                </div>
                                <span class="text-slate-400" x-text="open ? '−' : '+'"></span>
                            </button>

                            <div x-show="open" x-cloak class="border-t border-slate-100 px-5 py-4">
                                <div class="mb-3 flex justify-end">
                                    <button
                                        type="button"
                                        x-data="{ copied: false }"
                                        @click="navigator.clipboard.writeText({{ Js::from($entry->content) }}); copied = true; setTimeout(() => copied = false, 1500)"
                                        class="text-xs text-slate-400 hover:text-slate-600"
                                    >
                                        <span x-show="!copied">Copy markdown</span>
                                        <span x-show="copied">Copied!</span>
                                    </button>
                                </div>
                                <div class="prose prose-slate max-w-none prose-sm">
                                    {!! \App\Support\Markdown::toHtml($entry->content) !!}
                                </div>

                                @if ($entry->images->isNotEmpty())
                                    <div class="mt-4 flex flex-wrap gap-3 border-t border-slate-100 pt-4">
                                        @foreach ($entry->images as $image)
                                            <a
                                                href="{{ route('context-images.show', $image) }}"
                                                target="_blank"
                                                class="block h-24 w-24 overflow-hidden rounded border border-slate-200 bg-slate-50"
                                                title="{{ $image->original_filename }}"
                                            >
                                                <img
                                                    src="{{ route('context-images.show', $image) }}"
                                                    alt="{{ $image->original_filename }}"
                                                    class="h-full w-full object-cover"
                                                    loading="lazy"
                                                >
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </section>
@endsection
