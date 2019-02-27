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
use Slub\Domain\Entity\PR\MessageId;
use Slub\Domain\Event\PRGTMed;
use Slub\Domain\Query\GetChannelInformationInterface;
use Slub\Domain\Query\GetMessageIdsForPR;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class SlubBot implements EventSubscriberInterface
{
    /** @var PutPRToReviewHandler */
    private $putPRToReviewHandler;

    /** @var GetChannelInformationInterface */
    private $getChannelInformation;

    /** @var GetMessageIdsForPR */
    private $getMessageIdsForPR;

    /** @var LoggerInterface */
    private $logger;

    /** @var BotMan */
    private $bot;

    /** @var string */
    private $slackToken;

    public function __construct(
        PutPRToReviewHandler $putPRToReviewHandler,
        GetChannelInformationInterface $getChannelInformation,
        GetMessageIdsForPR $getMessageIdsForPR,
        LoggerInterface $logger,
        string $slackToken
    ) {
        $this->putPRToReviewHandler = $putPRToReviewHandler;
        $this->getChannelInformation = $getChannelInformation;
        $this->getMessageIdsForPR = $getMessageIdsForPR;
        $this->logger = $logger;

        DriverManager::loadDriver(SlackDriver::class);
        $this->bot = BotManFactory::create(['slack' => $slackToken]);
        $this->listensToNewPR($this->bot);
        $this->healthCheck($this->bot);
        $this->bot->listen();
        $this->slackToken = $slackToken;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PRGTMed::class => 'notifySquadThePRIsGTMed',
        ];
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

    public function notifySquadThePRIsGTMed(PRGTMed $event): void
    {
        $PRIdentifier = $event->PRIdentifier();
        $messageIds = $this->getMessageIdsForPR->fetch($PRIdentifier);
        foreach ($messageIds as $messageId) {
            $this->send($messageId);
        }
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

    private function getMessageId($bot)
    {
        return $bot->getMessage()->getPayload()['channel']
            . '@'
            . $bot->getMessage()->getPayload()['ts'];
    }

    public function send(MessageId $messageId): void
    {
        $message = explode('@', $messageId->stringValue());
        $this->bot->sendPayload([
            'token'     => $this->slackToken,
            'channel'   => $message[0],
            'text'      => 'PR is GTMed',
            'thread_ts' => $message[1],
        ]);
    }
}
