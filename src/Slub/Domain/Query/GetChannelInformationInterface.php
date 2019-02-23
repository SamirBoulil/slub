<?php
declare(strict_types=1);

namespace Slub\Domain\Query;

interface GetChannelInformationInterface
{
    public function fetch(string $channelId): ChannelInformation;
}
