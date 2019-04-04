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
use Slub\Domain\Entity\Channel\ChannelIdentifier;
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
        $createNewPr = function (Botman $bot, string $repository, $PRNumber) {
            $this->putPRToReview($PRNumber, $repository, $bot);
        };
        $bot->hears('.*TR.*<https://github.com/(.*)/pull/(\d+).*>.*$', $createNewPr);
        $bot->hears('.*review.*<https://github.com/(.*)/pull/(\d+).*>.*$', $createNewPr);
        $bot->hears('.*PR.*<https://github.com/(.*)/pull/(\d+).*>.*$', $createNewPr);
    }

    private function putPRToReview(string $PRNumber, string $repositoryIdentifier, BotMan $bot): void
    {
        $prToReview = new PutPRToReview();
        $prToReview->PRIdentifier = sprintf('%s/%s', $repositoryIdentifier, $PRNumber);
        $prToReview->repositoryIdentifier = $repositoryIdentifier;
        $prToReview->channelIdentifier = $this->getChannelIdentifier($bot);
        $prToReview->messageIdentifier = $this->getMessageIdentifier($bot);

        $this->logger->info(
            sprintf(
                'New PR to review detected from channel "%s" for repository "%s", PR "%s".',
                $this->getChannelIdentifier($bot),
                $repositoryIdentifier,
                $prToReview->PRIdentifier
            )
        );

        $this->putPRToReviewHandler->handle($prToReview);
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
        $channelIdentifier = ChannelIdentifier::fromString($bot->getMessage()->getPayload()['channel']);
        $channelInformation = $this->getChannelInformation->fetch($channelIdentifier);

        return $channelInformation->channelName;
    }

    private function getMessageIdentifier(Botman $bot): string
    {
        $channel = $bot->getMessage()->getPayload()['channel'];
        $ts = $bot->getMessage()->getPayload()['ts'];

        return MessageIdentifierHelper::from($channel, $ts);
    }
}
