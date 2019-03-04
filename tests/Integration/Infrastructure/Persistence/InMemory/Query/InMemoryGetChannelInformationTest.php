<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\InMemory\Query;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\Repository\RepositoryIdentifier;
use Slub\Domain\Query\GetChannelInformationInterface;
use Slub\Domain\Query\IsSupportedInterface;
use Slub\Infrastructure\Persistence\InMemory\Query\InMemoryGetChannelInformation;
use Slub\Infrastructure\Persistence\InMemory\Query\InMemoryIsSupported;

class InMemoryGetChannelInformationTest extends TestCase
{
    /** @var GetChannelInformationInterface */
    private $getChannelInformation;

    public function setUp()
    {
        parent::setUp();
        $this->getChannelInformation = new InMemoryGetChannelInformation(
            ['squad-raccoons', 'squad-chipmunks']
        );
    }

    /**
     * @test
     */
    public function it_retrieves_the_name_of_the_channel_for_an_id()
    {
        $channelInformation = $this->getChannelInformation->fetch(ChannelIdentifier::fromString('11111'));
        $this->assertEquals('squad-raccoons', $channelInformation->channelName);
        $this->assertEquals('11111', $channelInformation->channelIdentifier);
    }
}
