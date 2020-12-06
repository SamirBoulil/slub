<?php

declare(strict_types=1);

namespace Slub\Domain\Query;

use ConvenientImmutability\Immutable;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class ChannelInformation
{
    use Immutable;

    public string $channelIdentifier;

    public string $channelName;
}
