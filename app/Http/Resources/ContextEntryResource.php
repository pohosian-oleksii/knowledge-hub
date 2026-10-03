<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ContextEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'type' => $this->type->value,
            'key' => $this->key,
            'title' => $this->title,
            'content' => $this->content,
            'tags' => $this->tags,
            'source_agent' => $this->source_agent,
            'importance' => $this->importance,
            'images' => ContextImageResource::collection($this->whenLoaded('images')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
