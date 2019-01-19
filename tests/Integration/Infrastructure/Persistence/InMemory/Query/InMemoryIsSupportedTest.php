<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\InMemory\Query;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\Repository\RepositoryIdentifier;
use Slub\Domain\Query\IsSupportedInterface;
use Slub\Infrastructure\Persistence\InMemory\Query\InMemoryIsSupported;

class InMemoryIsSupportedTest extends TestCase
{
    /** @var IsSupportedInterface */
    private $isSupportedQuery;

    public function setUp()
    {
        parent::setUp();
        $this->isSupportedQuery = new InMemoryIsSupported(
            ['akeneo/pim-community-dev', 'akeneo/pim-enterprise-dev'],
            ['squad-raccoons', 'squad-chipmunks']
        );
    }

    /**
     * @test
     */
    public function it_tells_if_the_repository_is_supported()
    {
        $this->assertTrue(
            $this->isSupportedQuery->repository(RepositoryIdentifier::fromString('akeneo/pim-community-dev'))
        );
        $this->assertFalse(
            $this->isSupportedQuery->repository(RepositoryIdentifier::fromString('SamirBoulil/pim-community-dev'))
        );
    }

    /**
     * @test
     */
    public function it_tells_if_the_channel_is_supported()
    {
        $this->assertTrue(
            $this->isSupportedQuery->channel(ChannelIdentifier::fromString('squad-raccoons'))
        );
        $this->assertFalse(
            $this->isSupportedQuery->channel(ChannelIdentifier::fromString('unknown'))
        );
    }
}
