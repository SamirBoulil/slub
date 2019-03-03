<?php
declare(strict_types=1);

namespace Slub\Domain\Query;

use Slub\Infrastructure\Chat\Slack\SlubBot;

interface GetChannelInformationInterface
{
    public function fetch(string $channelId): ChannelInformation;

    public function setSlubBot(SlubBot $slubBot): void;
}
