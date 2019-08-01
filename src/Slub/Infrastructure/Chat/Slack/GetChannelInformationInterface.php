<?php
declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use Slub\Domain\Query\ChannelInformation;

interface GetChannelInformationInterface
{
    public function fetch(string $channelIdentifier): ChannelInformation;
}
