<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\InMemory\Query;

use PHPUnit\Framework\TestCase;
use Slub\Infrastructure\Chat\Slack\Query\GetChannelInformation;
use Slub\Infrastructure\Persistence\InMemory\Query\InMemoryGetChannelInformation;

class InMemoryGetChannelInformationTest extends TestCase
{
    /** @var GetChannelInformation */
    private $getChannelInformation;

    public function setUp(): void
    {
        parent::setUp();
        $this->getChannelInformation = new InMemoryGetChannelInformation();
    }

    /**
     * @test
     */
    public function it_retrieves_the_name_of_the_channel_for_an_id(): void
    {
        $channelInformation = $this->getChannelInformation->fetch('workspace_id', '11111');
        $this->assertEquals('akeneo', $channelInformation->channelName);
        $this->assertEquals('11111', $channelInformation->channelIdentifier);
    }
}
