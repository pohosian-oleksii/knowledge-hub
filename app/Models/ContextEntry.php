<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContextEntryType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class ContextEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'type',
        'title',
        'content',
        'tags',
        'source_agent',
        'importance',
    ];

    protected $attributes = [
        'tags' => '[]',
        'importance' => 3,
    ];

    protected function casts(): array
    {
        return [
            'type' => ContextEntryType::class,
            'tags' => 'array',
            'importance' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(static function (self $entry): void {
            $entry->key = self::deriveKey($entry->type, $entry->title);
        });
    }

    public static function deriveKey(ContextEntryType|string $type, string $title): string
    {
        $typeValue = $type instanceof ContextEntryType ? $type->value : $type;

        return sha1($typeValue . ':' . Str::slug($title));
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ContextImage::class)->orderBy('created_at');
    }
}
