<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Slack;

use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Drivers\Tests\FakeDriver;
use BotMan\BotMan\Drivers\Tests\ProxyDriver;
use BotMan\Studio\Testing\BotManTester;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRRepositoryInterface;
use Tests\Integration\Infrastructure\KernelTestCase;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class SlubBotFactoryTest extends KernelTestCase
{
    /** @var BotManTester */
    private $botTester;

    /** @var PRRepositoryInterface */
    private $PRRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->PRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $this->botTester = $this->startBot();
    }

    /**
     * @test
     */
    public function it_starts_a_bot_that_listens_for_new_PR()
    {
        $this->botTester->receives('TR pliz https://github.com/akeneo/pim-community-dev/pull/9590')->assertReplyNothing();
        $this->assertNewPRRequestReceived('akeneo/pim-community-dev/pull/9590');
    }

    private function assertNewPRRequestReceived(string $prIdentifier): void
    {
        $this->PRRepository->getBy(PRIdentifier::fromString($prIdentifier));
    }

    /**
     * @return BotManTester
     *
     */
    private function startBot(): BotManTester
    {
        DriverManager::loadDriver(ProxyDriver::class);
        $fakeDriver = new FakeDriver();
        ProxyDriver::setInstance($fakeDriver);
        $bot = $this->get('slub.infrastructure.chat.slack.slub_bot_factory')->start();
        $botManTester = new BotmanTester($bot, $fakeDriver);

        return $botManTester;
    }
}
