<?php

declare(strict_types=1);

namespace App\Support;

use League\CommonMark\CommonMarkConverter;

final class Markdown
{
    private static ?CommonMarkConverter $converter = null;

    public static function toHtml(?string $markdown): string
    {
        if ($markdown === null || $markdown === '') {
            return '';
        }

        self::$converter ??= new CommonMarkConverter();

        return (string) self::$converter->convert($markdown);
    }
}
