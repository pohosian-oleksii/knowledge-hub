<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\ContextEntry;
use App\Models\ContextImage;
use finfo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ContextImageStorage
{
    private const int MAX_BYTES = 10 * 1024 * 1024;

    private const array ALLOWED_MIME_TYPES = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    public static function store(ContextEntry $entry, string $base64, string $originalFilename): ContextImage
    {
        $binary = self::decode($base64);

        if (strlen($binary) > self::MAX_BYTES) {
            throw ValidationException::withMessages(['image_base64' => 'Image exceeds the 10MB limit.']);
        }

        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->buffer($binary);

        if (! array_key_exists($mimeType, self::ALLOWED_MIME_TYPES)) {
            throw ValidationException::withMessages(['image_base64' => "Unsupported image type: {$mimeType}"]);
        }

        if (@getimagesizefromstring($binary) === false) {
            throw ValidationException::withMessages(['image_base64' => 'File is not a valid image.']);
        }

        $diskPath = sprintf(
            'projects/%d/context/%d/%s.%s',
            $entry->project_id,
            $entry->id,
            Str::uuid()->toString(),
            self::ALLOWED_MIME_TYPES[$mimeType]
        );

        Storage::disk('local')->put($diskPath, $binary);

        return $entry->images()->create([
            'disk_path' => $diskPath,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'size' => strlen($binary),
        ]);
    }

    private static function decode(string $base64): string
    {
        if (str_starts_with($base64, 'data:') && str_contains($base64, ',')) {
            $base64 = substr($base64, strpos($base64, ',') + 1);
        }

        $binary = base64_decode($base64, true);

        if ($binary === false) {
            throw ValidationException::withMessages(['image_base64' => 'Invalid base64 payload.']);
        }

        return $binary;
    }
}
