<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Slack\SlackDriver;
use Psr\Log\LoggerInterface;
use Slub\Application\PutPRToReview\PutPRToReview;
use Slub\Application\PutPRToReview\PutPRToReviewHandler;
use Slub\Domain\Query\GetChannelInformationInterface;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class SlubBot
{
    /** @var PutPRToReviewHandler */
    private $putPRToReviewHandler;

    /** @var GetChannelInformationInterface */
    private $getChannelInformation;

    /** @var LoggerInterface */
    private $logger;

    /** @var BotMan */
    private $bot;

    /** @var string */
    private $slackToken;

    public function __construct(
        PutPRToReviewHandler $putPRToReviewHandler,
        GetChannelInformationInterface $getChannelInformation,
        LoggerInterface $logger,
        string $slackToken
    ) {
        $this->putPRToReviewHandler = $putPRToReviewHandler;
        $this->getChannelInformation = $getChannelInformation;
        $this->logger = $logger;

        DriverManager::loadDriver(SlackDriver::class);
        $this->bot = BotManFactory::create(['slack' => ['token' => $slackToken]]);
        $this->listensToNewPR($this->bot);
        $this->healthCheck($this->bot);
        $this->bot->listen();
        $this->slackToken = $slackToken;
    }

    public function start(): void
    {
        $this->logger->info('Infra - SlubBot is now listening...');
    }

    public function getBot(): BotMan
    {
        return $this->bot;
    }

    private function listensToNewPR(BotMan $bot): void
    {
        $bot->hears(
            'TR .* <https://github.com/(.*)/pull/(.*)>$',
            function (Botman $bot, string $repository, $prIdentifier) {
                $channelIdentifier = $this->getChannelIdentifier($bot);
                $prToReview = new PutPRToReview();
                $prToReview->PRIdentifier = $repository . '/' . $prIdentifier;
                $prToReview->repositoryIdentifier = $repository;
                $prToReview->channelIdentifier = $channelIdentifier;
                $prToReview->messageId = $this->getMessageId($bot);

                $this->logger->critical(
                    sprintf(
                        'Infra - NEW PR TO REVIEW detected (channel "%s", repository "%s", PR "%s")',
                        $channelIdentifier,
                        $repository,
                        $prIdentifier
                    )
                );

                $this->putPRToReviewHandler->handle($prToReview);
            }
        );
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
        $channelId = $bot->getMessage()->getPayload()['channel'];
        $this->getChannelInformation->setSlubBot($this);
        $channelInformation = $this->getChannelInformation->fetch($channelId);

        return $channelInformation->channelName;
    }

    private function getMessageId($bot): string
    {
        $channel = $bot->getMessage()->getPayload()['channel'];
        $ts = $bot->getMessage()->getPayload()['ts'];

        return MessageIdentifierHelper::from($channel, $ts);
    }
}
