<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\InMemory\Query;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;
use Slub\Infrastructure\Chat\Slack\GetChannelInformationInterface;
use Slub\Infrastructure\Persistence\InMemory\Query\InMemoryGetChannelInformation;

class InMemoryGetChannelInformationTest extends TestCase
{
    /** @var GetChannelInformationInterface */
    private $getChannelInformation;

    public function setUp()
    {
        parent::setUp();
        $this->getChannelInformation = new InMemoryGetChannelInformation('akeneo,squad-chipmunks');
    }

    /**
     * @test
     */
    public function it_retrieves_the_name_of_the_channel_for_an_id()
    {
        $channelInformation = $this->getChannelInformation->fetch('11111');
        $this->assertEquals('akeneo', $channelInformation->channelName);
        $this->assertEquals('11111', $channelInformation->channelIdentifier);
    }
}
