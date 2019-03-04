<?php
declare(strict_types=1);

namespace Slub\Domain\Query;

use Slub\Domain\Entity\Channel\ChannelIdentifier;

interface GetChannelInformationInterface
{
    public function fetch(ChannelIdentifier $channelIdentifier): ChannelInformation;
}
