@extends('layouts.app')

@section('title', 'Dashboard — Agents Knowledge Hub')

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">Projects</h1>

    @if ($projects->isEmpty())
        <p class="text-slate-500">No projects yet. Have an agent POST to <code class="rounded-md border border-slate-200 bg-slate-100 px-1.5 py-0.5 font-mono text-[0.85em] text-pink-700">/api/v1/projects</code>.</p>
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($projects as $project)
                <a href="{{ route('projects.show', $project) }}" class="block rounded-lg border border-slate-200 bg-white p-5 transition hover:border-slate-300 hover:shadow-sm">
                    <h2 class="text-lg font-medium">{{ $project->name }}</h2>
                    @if ($project->description)
                        <p class="mt-1 text-sm text-slate-500">{{ $project->description }}</p>
                    @endif
                    <div class="mt-4 flex items-center justify-between text-xs text-slate-400">
                        <span>{{ $project->context_entries_count }} {{ $project->context_entries_count === 1 ? 'entry' : 'entries' }}</span>
                        <span>updated {{ $project->updated_at->diffForHumans() }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
