<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

final class ContextImage extends Model
{
    protected $fillable = [
        'context_entry_id',
        'disk_path',
        'original_filename',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(static function (self $image): void {
            Storage::disk('local')->delete($image->disk_path);
        });
    }

    public function contextEntry(): BelongsTo
    {
        return $this->belongsTo(ContextEntry::class);
    }
}
