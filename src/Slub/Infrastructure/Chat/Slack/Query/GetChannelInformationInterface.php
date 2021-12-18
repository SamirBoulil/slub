<?php
declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack\Query;

use Slub\Domain\Query\ChannelInformation;

interface GetChannelInformationInterface
{
    public function fetch(string $workspaceId, string $channelIdentifier): ChannelInformation;
}
