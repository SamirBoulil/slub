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
     * @dataProvider newPRmessages
     */
    public function it_answers_to_new_PR_messages(string $message): void
    {
        $botTester = $this->startBot();
        $botTester->receives(
            $message,
            ['channel' => 'channelId', 'ts' => '1234'])->assertReplyNothing();
        $this->assertNewPRRequestReceived('akeneo/pim-community-dev/9609', 'channelId@1234');
    }

    /**
     * @test
     */
    public function it_answers_to_health_check_message(): void
    {
        $botTester = $this->startBot();
        $botTester->receives('alive')->assertReply('yes :+1:');
    }

    private function startBot(): BotManTester
    {
        DriverManager::loadDriver(ProxyDriver::class);
        $fakeDriver = new FakeDriver();
        ProxyDriver::setInstance($fakeDriver);
        $bot = $this->get('slub.infrastructure.chat.slack.slub_bot')->getBot();
        $botManTester = new BotmanTester($bot, $fakeDriver);

        return $botManTester;
    }

    private function assertNewPRRequestReceived(string $prIdentifier, string $messageId): void
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString($prIdentifier));
        $this->assertEquals($prIdentifier, $PR->normalize()['IDENTIFIER']);
        $this->assertEquals([$messageId], $PR->normalize()['MESSAGE_IDS']);
    }

    public function newPRmessages()
    {
        return[
            'PR with please' => ['TR please <https://github.com/akeneo/pim-community-dev/pull/9609>'],
            'PR without please' => ['TR <https://github.com/akeneo/pim-community-dev/pull/9609>']
        ];
    }
}
