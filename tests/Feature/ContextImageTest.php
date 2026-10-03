<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ContextEntry;
use App\Models\ContextImage;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ContextImageTest extends TestCase
{
    use RefreshDatabase;

    private const string ONE_PIXEL_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    public function test_it_uploads_stores_and_serves_an_image(): void
    {
        Storage::fake('local');

        $entry = ContextEntry::factory()->for(Project::factory())->create();

        $response = $this->postJson(
            "/api/v1/projects/{$entry->project->slug}/context/{$entry->id}/images",
            ['filename' => 'pixel.png', 'image_base64' => self::ONE_PIXEL_PNG_BASE64]
        );

        $response->assertCreated();
        $response->assertJsonPath('data.original_filename', 'pixel.png');
        $response->assertJsonPath('data.mime_type', 'image/png');

        $image = ContextImage::firstOrFail();

        Storage::disk('local')->assertExists($image->disk_path);
        $this->assertDatabaseMissing('context_images', ['disk_path' => null]);

        $this->getJson("/api/v1/context-images/{$image->id}")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $this->getJson("/api/v1/projects/{$entry->project->slug}/context/{$entry->id}")
            ->assertJsonCount(1, 'data.images');
    }

    public function test_it_rejects_invalid_base64_payloads(): void
    {
        Storage::fake('local');

        $entry = ContextEntry::factory()->for(Project::factory())->create();

        $this->postJson(
            "/api/v1/projects/{$entry->project->slug}/context/{$entry->id}/images",
            ['filename' => 'not-an-image.txt', 'image_base64' => base64_encode('plain text, not an image')]
        )->assertUnprocessable();
    }

    public function test_deleting_an_image_removes_it_from_disk(): void
    {
        Storage::fake('local');

        $entry = ContextEntry::factory()->for(Project::factory())->create();

        $uploadResponse = $this->postJson(
            "/api/v1/projects/{$entry->project->slug}/context/{$entry->id}/images",
            ['filename' => 'pixel.png', 'image_base64' => self::ONE_PIXEL_PNG_BASE64]
        );

        $image = ContextImage::firstOrFail();

        $this->deleteJson("/api/v1/projects/{$entry->project->slug}/context/{$entry->id}/images/{$image->id}")
            ->assertNoContent();

        Storage::disk('local')->assertMissing($image->disk_path);
        $this->assertDatabaseMissing('context_images', ['id' => $image->id]);
    }
}
