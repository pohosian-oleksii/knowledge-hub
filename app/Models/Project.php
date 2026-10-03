<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'overview',
        'repo_path',
    ];

    public function contextEntries(): HasMany
    {
        return $this->hasMany(ContextEntry::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
