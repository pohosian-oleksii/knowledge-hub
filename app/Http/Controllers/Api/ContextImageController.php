<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContextImageRequest;
use App\Http\Resources\ContextImageResource;
use App\Models\ContextImage;
use App\Models\Project;
use App\Support\ContextImageStorage;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ContextImageController extends Controller
{
    public function index(Project $project, int $contextEntry): AnonymousResourceCollection
    {
        $entry = $project->contextEntries()->findOrFail($contextEntry);

        return ContextImageResource::collection($entry->images);
    }

    public function store(StoreContextImageRequest $request, Project $project, int $contextEntry): ContextImageResource
    {
        $entry = $project->contextEntries()->findOrFail($contextEntry);

        $image = ContextImageStorage::store(
            $entry,
            $request->validated('image_base64'),
            $request->validated('filename')
        );

        return new ContextImageResource($image);
    }

    public function show(int $image): StreamedResponse
    {
        $contextImage = ContextImage::findOrFail($image);

        return Storage::disk('local')->response(
            $contextImage->disk_path,
            $contextImage->original_filename,
            ['Content-Type' => $contextImage->mime_type]
        );
    }

    public function destroy(Project $project, int $contextEntry, int $image): Response
    {
        $entry = $project->contextEntries()->findOrFail($contextEntry);
        $entry->images()->findOrFail($image)->delete();

        return response()->noContent();
    }
}
