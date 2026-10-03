<?php

declare(strict_types=1);

use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');
Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
