<?php

declare(strict_types=1);

namespace App\Enums;

enum ContextEntryType: string
{
    case Architecture = 'architecture';
    case Convention = 'convention';
    case Decision = 'decision';
    case Gotcha = 'gotcha';
    case Dependency = 'dependency';
    case Glossary = 'glossary';
    case Todo = 'todo';
    case Other = 'other';

    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
