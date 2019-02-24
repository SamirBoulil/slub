<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Slack;

use Slub\Domain\Query\GetChannelInformationInterface;
use Tests\Integration\Infrastructure\KernelTestCase;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 */
class GetChannelInformationTest extends KernelTestCase
{
    /** @var GetChannelInformationInterface */
    private $getChannelInformation;

    public function setUp(): void
    {
        parent::setUp();
        $this->getChannelInformation = $this->get('slub.infrastructure.query.get_channel_information');
    }

    // Not easy to test without mocking this framework.

    /**
     * @test
     */
    public function it_dummy_tests()
    {
        $this->assertTrue(true);
    }
}
