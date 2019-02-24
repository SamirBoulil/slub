<?php

declare(strict_types=1);

namespace Slub\Domain\Query;

use ConvenientImmutability\Immutable;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 */
class ChannelInformation
{
    use Immutable;

    /** @var string */
    public $channelId;

    /** @var string */
    public $channelName;
}
