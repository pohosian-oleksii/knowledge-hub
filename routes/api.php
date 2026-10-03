<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ContextEntryController;
use App\Http\Controllers\Api\ContextImageController;
use App\Http\Controllers\Api\IndexController;
use App\Http\Controllers\Api\ProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', IndexController::class);

Route::prefix('v1')->group(function (): void {
    Route::get('projects', [ProjectController::class, 'index']);
    Route::post('projects', [ProjectController::class, 'store']);
    Route::get('projects/{project}', [ProjectController::class, 'show']);
    Route::patch('projects/{project}', [ProjectController::class, 'update']);

    Route::get('projects/{project}/context', [ContextEntryController::class, 'index']);
    Route::get('projects/{project}/context/summary', [ContextEntryController::class, 'summary']);
    Route::post('projects/{project}/context', [ContextEntryController::class, 'store']);
    Route::post('projects/{project}/context/bulk', [ContextEntryController::class, 'bulk']);
    Route::get('projects/{project}/context/{id}', [ContextEntryController::class, 'show']);
    Route::patch('projects/{project}/context/{id}', [ContextEntryController::class, 'update']);
    Route::delete('projects/{project}/context/{id}', [ContextEntryController::class, 'destroy']);

    Route::get('projects/{project}/context/{contextEntry}/images', [ContextImageController::class, 'index']);
    Route::post('projects/{project}/context/{contextEntry}/images', [ContextImageController::class, 'store']);
    Route::delete('projects/{project}/context/{contextEntry}/images/{image}', [ContextImageController::class, 'destroy']);

    Route::get('context-images/{image}', [ContextImageController::class, 'show'])->name('context-images.show');
});
