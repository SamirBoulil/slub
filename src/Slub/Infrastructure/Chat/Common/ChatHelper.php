<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Common;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class ChatHelper
{
    public static function elipsisIfTooLong(string $possiblyLongText, int $maxLength): string
    {
        $firstLine = current(explode("\r\n", $possiblyLongText));

        return strlen($firstLine) > $maxLength ?
            sprintf("%s ...", current(explode("\r\n", wordwrap($firstLine, $maxLength, "\r\n"))))
            : $firstLine;
    }
}
