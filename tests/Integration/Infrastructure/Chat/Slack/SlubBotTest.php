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
 * @author    Samir Boulil <samir.boulil@gmail.com>
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
    public function it_answers_to_new_PR_messages_in_public_channels(string $message): void
    {
        $botTester = $this->startBot();
        $botTester->receives($message, ['channel' => 'channelId', 'ts' => '1234', 'channel_type' => 'channel'])->assertReplyNothing();
        $this->assertNewPRRequestReceived('akeneo/pim-community-dev/9609', 'channelId@1234');
    }

    /**
     * @test
     */
    public function it_answers_to_new_PR_messages_in_private_channels(): void
    {
        $botTester = $this->startBot();
        $botTester->receives('TR please <https://github.com/akeneo/pim-community-dev/pull/9609>', ['channel' => 'channelId', 'ts' => '1234', 'channel_type' => 'group'])->assertReplyNothing();
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

    public function newPRmessages(): array
    {
        return [
            'TR please' => ['TR please <https://github.com/akeneo/pim-community-dev/pull/9609>'],
            'TR' => ['TR <https://github.com/akeneo/pim-community-dev/pull/9609>'],
            'TR {url}/files' => ['TR <https://github.com/akeneo/pim-community-dev/pull/9609/files>'],
            'Yo guys TR please' => ['Yo guys TR <https://github.com/akeneo/pim-community-dev/pull/9609/files>'],
            'Yo guys tr please' => ['Yo guys tr <https://github.com/akeneo/pim-community-dev/pull/9609/files>'],
            'TR {url} it\'s about something new' => ['TR <https://github.com/akeneo/pim-community-dev/pull/9609/files> it\'s about something new...'],
            'review' => ['review <https://github.com/akeneo/pim-community-dev/pull/9609>'],
            'review {url}/files' => ['review <https://github.com/akeneo/pim-community-dev/pull/9609/files>'],
            'review {url} explanations' => ['review <https://github.com/akeneo/pim-community-dev/pull/9609> It\'s about something new...'],
            'PR {url} explanations' => ['PR please <https://github.com/akeneo/pim-community-dev/pull/9609/files> yolo'],
        ];
    }
}
