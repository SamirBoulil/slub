<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Slack;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Drivers\Tests\FakeDriver;
use BotMan\BotMan\Drivers\Tests\ProxyDriver;
use BotMan\Studio\Testing\BotManTester;
use Ramsey\Uuid\Uuid;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\AuthorIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\PR\Title;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;
use Slub\Domain\Repository\PRNotFoundException;
use Slub\Domain\Repository\PRRepositoryInterface;
use Slub\Infrastructure\Chat\Slack\SlubBot;
use Tests\Acceptance\helpers\ChatClientSpy;
use Tests\Integration\Infrastructure\KernelTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class SlubBotTest extends KernelTestCase
{
    /** @var BotManTester */
    private $botTester;

    /** @var PRRepositoryInterface */
    private $PRRepository;

    /** @var ChatClientSpy */
    private $chatClientSpy;

    /** @var string */
    private $botUserId;

    public static function setUpBeforeClass()
    {
        DriverManager::loadDriver(ProxyDriver::class);
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->PRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $this->chatClientSpy = $this->get('slub.infrastructure.chat.slack.slack_client');
        $this->botTester = $this->startBot();
        $this->botTester->setUser(['id' => 'DUMMY_NAME']);
        $this->botUserId = $this->get('BOT_USER_ID');
    }

    /**
     * @test
     * @dataProvider newPRmessages
     */
    public function it_answers_to_new_PR_messages_in_public_channels(string $message): void
    {
        $this->botTester->receives($message, ['channel' => 'channelId', 'ts' => '1234', 'channel_type' => 'channel', 'team' => 'akeneo'])->assertReplyNothing();
        $this->assertNewPRRequestReceived('akeneo/pim-community-dev/9609', 'channelId@1234');
    }

    /**
     * @test
     */
    public function it_answers_to_new_PR_messages_in_private_channels(): void
    {
        $this->botTester->receives('TR please <https://github.com/akeneo/pim-community-dev/pull/9609>', ['channel' => 'channelId', 'ts' => '1234', 'channel_type' => 'group', 'team' => 'akeneo'])->assertReplyNothing();
        $this->assertNewPRRequestReceived('akeneo/pim-community-dev/9609', 'channelId@1234');
    }

    /**
     * @test
     */
    public function it_answers_to_health_check_message(): void
    {
        $this->botTester->receives('alive')->assertReply('yes :+1:');
    }

    /**
     * @test
     */
    public function it_unpublishes_a_PR(): void
    {
        $PRIdentifier = 'akeneo/pim-community-dev/9609';
        $this->createPRToReview($PRIdentifier);
        $message = sprintf(
            '<@%s> could you please unpublish this PR <https://github.com/akeneo/pim-community-dev/pull/9609>',
            $this->botUserId
        );

        $this->botTester->receives($message, ['channel' => 'channelId', 'ts' => '1234', 'channel_type' => 'group', 'team' => 'akeneo']);

        $this->assertPRHasBeenUnpublished($PRIdentifier);
        $this->assertBotHasRepliedWithOneOf(SlubBot::UNPUBLISH_CONFIRMATION_MESSAGES);
    }

    private function startBot(): BotManTester
    {
        $fakeDriver = new FakeDriver();
        $fakeDriver->setUser(['id' => $this->botUserId]);
        ProxyDriver::setInstance($fakeDriver);
        /** @var BotMan $bot */
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

    private function assertPRHasBeenUnpublished(string $prIdentifier): void
    {
        $isPRUnpublished = false;
        try {
            $this->PRRepository->getBy(PRIdentifier::fromString($prIdentifier));
        } catch (PRNotFoundException $exception) {
            $isPRUnpublished = true;
        }
        $this->assertTrue($isPRUnpublished);
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

    private function createPRToReview(string $PRIdentifier): void
    {
        $this->PRRepository->save(
            PR::create(
                PRIdentifier::create($PRIdentifier),
                ChannelIdentifier::fromString(Uuid::uuid4()->toString()),
                WorkspaceIdentifier::fromString(Uuid::uuid4()->toString()),
                MessageIdentifier::fromString(Uuid::uuid4()->toString()),
                AuthorIdentifier::fromString('sam'),
                Title::fromString('Add new feature')
            )
        );
    }

    private function assertBotHasRepliedWithOneOf(array $anyMessage): void
    {
        $this->chatClientSpy->assertRepliedWithOneOf($anyMessage);
    }
}
