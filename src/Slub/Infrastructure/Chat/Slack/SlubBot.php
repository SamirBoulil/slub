<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Slack\SlackDriver;
use Psr\Log\LoggerInterface;
use Slub\Application\Common\ChatClient;
use Slub\Application\PutPRToReview\PutPRToReview;
use Slub\Application\PutPRToReview\PutPRToReviewHandler;
use Slub\Application\UnpublishPR\UnpublishPR;
use Slub\Application\UnpublishPR\UnpublishPRHandler;
use Slub\Domain\Entity\PR\MessageIdentifier;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class SlubBot
{
    public const UNPUBLISH_CONFIRMATION_MESSAGES = ['Okaay! :ok_hand:', 'Will do! :+1:', 'Oki doki!', 'Yeeee '];

    /** @var PutPRToReviewHandler */
    private $putPRToReviewHandler;

    /** @var UnpublishPRHandler */
    private $unpublishPRHandler;

    /** @var GetChannelInformationInterface */
    private $getPublicChannelInformation;

    /** @var GetChannelInformationInterface */
    private $getPrivateChannelInformation;

    /** @var LoggerInterface */
    private $logger;

    /** @var BotMan */
    private $bot;

    /** @var string */
    private $slackToken;

    /** @var string */
    private $botUserId;

    /** @var ChatClient */
    private $chatClient;

    public function __construct(
        PutPRToReviewHandler $putPRToReviewHandler,
        UnpublishPRHandler $unpublishPRHandler,
        ChatClient $chatClient,
        GetChannelInformationInterface $getPublicChannelInformation,
        GetChannelInformationInterface $getPrivateChannelInformation,
        LoggerInterface $logger,
        string $slackToken,
        string $botUserId
    ) {
        $this->putPRToReviewHandler = $putPRToReviewHandler;
        $this->unpublishPRHandler = $unpublishPRHandler;
        $this->getPublicChannelInformation = $getPublicChannelInformation;
        $this->getPrivateChannelInformation = $getPrivateChannelInformation;
        $this->logger = $logger;
        $this->botUserId = $botUserId;
        $this->slackToken = $slackToken;
        $this->chatClient = $chatClient;

        DriverManager::loadDriver(SlackDriver::class);
        $this->bot = BotManFactory::create(['slack' => ['token' => $this->slackToken]]);
        $this->listensToNewPR($this->bot);
        $this->listenToPRToUnpublish($this->bot);
        $this->healthCheck($this->bot);
        $this->bot->listen();
    }

    public function start(): void
    {
        $this->logger->info('Bot is now listening...');
    }

    public function getBot(): BotMan
    {
        return $this->bot;
    }

    private function listensToNewPR(BotMan $bot): void
    {
        $createNewPr = function (Botman $bot, string $repository, $PRNumber) {
            $channelIdentifier = $this->getChannelIdentifier($bot);
            $messageIdentifier = $this->getMessageIdentifier($bot);
            $this->putPRToReview($PRNumber, $repository, $channelIdentifier, $messageIdentifier);
        };
        $bot->hears('.*TR.*<https://github.com/(.*)/pull/(\d+).*>.*$', $createNewPr);
        $bot->hears('.*review.*<https://github.com/(.*)/pull/(\d+).*>.*$', $createNewPr);
        $bot->hears('.*PR.*<https://github.com/(.*)/pull/(\d+).*>.*$', $createNewPr);
    }

    private function listenToPRToUnpublish(BotMan $bot): void
    {
        $unpublishPR = function (Botman $bot, string $repository, $PRNumber) {
            $this->unpublishPR($PRNumber, $repository);
            $message = self::UNPUBLISH_CONFIRMATION_MESSAGES[array_rand(self::UNPUBLISH_CONFIRMATION_MESSAGES)];
            $this->chatClient->replyInThread(MessageIdentifier::fromString($this->getMessageIdentifier($this->bot)), $message);
        };
        $unpublishMessage = sprintf('<@%s>.*unpublish.*<https://github.com/(.*)/pull/(\d+).*>.*', $this->botUserId);
        $bot->hears($unpublishMessage, $unpublishPR);
    }

    private function putPRToReview(string $PRNumber, string $repositoryIdentifier, string $channelIdentifier, string $messageIdentifier): void
    {
        $prToReview = new PutPRToReview();
        $prToReview->PRIdentifier = sprintf('%s/%s', $repositoryIdentifier, $PRNumber);
        $prToReview->repositoryIdentifier = $repositoryIdentifier;
        $prToReview->channelIdentifier = $channelIdentifier;
        $prToReview->messageIdentifier = $messageIdentifier;

        $this->logger->info(
            sprintf(
                'Bot hears a new PR to review detected in channel "%s" for repository "%s" and PR "%s".',
                $channelIdentifier,
                $repositoryIdentifier,
                $prToReview->PRIdentifier
            )
        );

        $this->putPRToReviewHandler->handle($prToReview);
    }

    private function unpublishPR(string $PRNumber, string $repositoryIdentifier): void
    {
        $unpublishPR = new UnpublishPR();
        $unpublishPR->PRIdentifier = sprintf('%s/%s', $repositoryIdentifier, $PRNumber);
        $this->unpublishPRHandler->handle($unpublishPR);
        $this->logger->info(sprintf('Bot hears an unpublish request for "%s".', $unpublishPR->PRIdentifier));
    }

    private function healthCheck(BotMan $bot): void
    {
        $bot->hears(
            'alive',
            function (Botman $bot) {
                $bot->reply('yes :+1:');
            }
        );
    }

    private function getChannelIdentifier(BotMan $bot): string
    {
        $this->logger->info('Now fetching channel information for channel');
        $payload = $bot->getMessage()->getPayload();
        $channel = $payload['channel'];
        $channelType = $payload['channel_type'];
        if ($this->isPublicChannel($channelType)) {
            $channelInformation = $this->getPublicChannelInformation->fetch($channel);
        } else {
            $channelInformation = $this->getPrivateChannelInformation->fetch($channel);
        }

        return $channelInformation->channelName;
    }

    private function getMessageIdentifier(Botman $bot): string
    {
        $channel = $bot->getMessage()->getPayload()['channel'];
        $ts = $bot->getMessage()->getPayload()['ts'];

        return MessageIdentifierHelper::from($channel, $ts);
    }

    private function isPublicChannel(string $channelType): bool
    {
        return 'channel' === $channelType;
    }
}
