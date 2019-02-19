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
class SlubBotTest extends KernelTestCase
{
    /** @var PRRepositoryInterface */
    private $PRRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->PRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
    }

    /**
     * @test
     */
    public function it_starts_a_bot_that_listens_for_new_PR(): void
    {
        $slubBot = $this->get('slub.infrastructure.chat.slack.slub_bot');
        $this->assertFalse($slubBot->isStarted());
        $this->startBot();
        $this->assertTrue($slubBot->isStarted());
    }

    /**
     * @test
     */
    public function it_answers_to_new_PR_messages(): void
    {
        $botTester = $this->startBot();
        $botTester->receives('TR pliz https://github.com/akeneo/pim-community-dev/pull/9590')->assertReplyNothing();
        $this->assertNewPRRequestReceived('akeneo/pim-community-dev/pull/9590');
    }

    /**
     * @test
     */
    public function it_answers_to_health_check_message(): void
    {
        $botTester = $this->startBot();
        $botTester->receives('alive')->assertReply('yes :+1:');
    }

    /**
     * @test
     */
    public function it_throws_if_you_create_a_slub_bot_twice(): void
    {
        $this->expectException(\LogicException::class);
        $this->startBot();
        $this->startBot();
    }

    private function assertNewPRRequestReceived(string $prIdentifier): void
    {
        $this->PRRepository->getBy(PRIdentifier::fromString($prIdentifier));
    }

    private function startBot(): BotManTester
    {
        DriverManager::loadDriver(ProxyDriver::class);
        $fakeDriver = new FakeDriver();
        ProxyDriver::setInstance($fakeDriver);
        $bot = $this->get('slub.infrastructure.chat.slack.slub_bot')->start();
        $botManTester = new BotmanTester($bot, $fakeDriver);

        return $botManTester;
    }
}
