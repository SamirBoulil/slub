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
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetPRInfoInterface;
use Slub\Domain\Query\PRInfo;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class SlubBot
{
    public const UNPUBLISH_CONFIRMATION_MESSAGES = ['Okaay! :ok_hand:', 'Will do! :+1:', 'Oki doki!', 'Yeeee '];

    private PutPRToReviewHandler $putPRToReviewHandler;

    private UnpublishPRHandler $unpublishPRHandler;

    private GetChannelInformationInterface $getChannelInformation;

    private LoggerInterface $logger;

    private BotMan $bot;

    private GetBotUserIdInterface $getBotUserId;

    private ChatClient $chatClient;

    private GetPRInfoInterface $getPRInfo;

    public function __construct(
        PutPRToReviewHandler $putPRToReviewHandler,
        UnpublishPRHandler $unpublishPRHandler,
        ChatClient $chatClient,
        GetBotUserIdInterface $getBotUserId,
        GetChannelInformationInterface $getChannelInformation,
        GetPRInfoInterface $getPRInfo,
        LoggerInterface $logger
    ) {
        $this->putPRToReviewHandler = $putPRToReviewHandler;
        $this->unpublishPRHandler = $unpublishPRHandler;
        $this->getChannelInformation = $getChannelInformation;
        $this->getPRInfo = $getPRInfo;
        $this->logger = $logger;
        $this->chatClient = $chatClient;
        $this->getBotUserId = $getBotUserId;

        DriverManager::loadDriver(SlackDriver::class);
        $this->bot = BotManFactory::create(['slack' => ['token' => 'dummyToken']]);
        $this->listensToNewPR($this->bot);
        $this->listenToPRToUnpublish($this->bot);
        $this->answersToHealthChecks($this->bot);
//        $this->providesToHelp($this->bot);
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
        $putToReviewUsageChangeWarning = <<<TEXT
Hey, did you know I won't be listening to your review requests using classic Slack messages anymore ?

Starting the *january the 1st 2022*, you'll need to use the new `/tr` Slack command ***to .

:yeee:!
TEXT;

        $createNewPr = function (BotMan $bot, string $repositoryIdentifier, string $PRNumber) use ($putToReviewUsageChangeWarning) {
            $workspaceIdentifier = $this->getWorkspaceIdentifier($bot);
            $channelIdentifier = $this->getChannelIdentifier($bot);
            $messageIdentifier = $this->getMessageIdentifier($bot);
            $PRInfo = $this->PRInfo($PRNumber, $repositoryIdentifier);
            $this->putPRToReview(
                $PRNumber,
                $repositoryIdentifier,
                $channelIdentifier,
                $workspaceIdentifier,
                $messageIdentifier,
                $PRInfo
            );
//            $bot->reply($putToReviewUsageChangeWarning);
        };
//        $bot->hears('.*TR.*<https://github.com/(.*)/pull/(\d+).*>.*$', $createNewPr);
//        $bot->hears('.*review.*<https://github.com/(.*)/pull/(\d+).*>.*$', $createNewPr);
//        $bot->hears('.*PR.*<https://github.com/(.*)/pull/(\d+).*>.*$', $createNewPr);
    }

    private function listenToPRToUnpublish(BotMan $bot): void
    {
        $unpublishPR = function (BotMan $bot, string $repository, $PRNumber) {
            $this->unpublishPR($PRNumber, $repository);
            $message = self::UNPUBLISH_CONFIRMATION_MESSAGES[array_rand(self::UNPUBLISH_CONFIRMATION_MESSAGES)];
            $this->chatClient->replyInThread(
                MessageIdentifier::fromString($this->getMessageIdentifier($this->bot)),
                $message
            );
        };
        $unpublishMessage = sprintf('<@%s>.*unpublish.*<https://github.com/(.*)/pull/(\d+).*>.*', $this->botUserId ?? 'FIX ME');
        $bot->hears($unpublishMessage, $unpublishPR);
    }

    private function putPRToReview(
        string $PRNumber,
        string $repositoryIdentifier,
        string $channelIdentifier,
        string $workspaceIdentifier,
        string $messageIdentifier,
        PRInfo $PRInfo
    ): void {
        $PRToReview = new PutPRToReview();
        $PRToReview->PRIdentifier = $this->PRIdentifier($PRNumber, $repositoryIdentifier);
        $PRToReview->repositoryIdentifier = $repositoryIdentifier;
        $PRToReview->channelIdentifier = $channelIdentifier;
        $PRToReview->workspaceIdentifier = $workspaceIdentifier;
        $PRToReview->messageIdentifier = $messageIdentifier;
        $PRToReview->authorIdentifier = $PRInfo->authorIdentifier;
        $PRToReview->title = $PRInfo->title;
        $PRToReview->GTMCount = $PRInfo->GTMCount;
        $PRToReview->notGTMCount = $PRInfo->notGTMCount;
        $PRToReview->comments = $PRInfo->comments;
        $PRToReview->CIStatus = $PRInfo->CIStatus->status;
        $PRToReview->isMerged = $PRInfo->isMerged;
        $PRToReview->isClosed = $PRInfo->isClosed;
        $PRToReview->additions = $PRInfo->additions;
        $PRToReview->deletions = $PRInfo->deletions;

        $this->logger->info(
            sprintf(
                'Bot hears a new PR to review detected in channel "%s" for repository "%s" and PR "%s".',
                $channelIdentifier,
                $repositoryIdentifier,
                $PRToReview->PRIdentifier
            )
        );

        $this->putPRToReviewHandler->handle($PRToReview);
    }

    private function unpublishPR(string $PRNumber, string $repositoryIdentifier): void
    {
        $unpublishPR = new UnpublishPR();
        $unpublishPR->PRIdentifier = sprintf('%s/%s', $repositoryIdentifier, $PRNumber);
        $this->unpublishPRHandler->handle($unpublishPR);
        $this->logger->info(sprintf('Bot hears an unpublish request for "%s".', $unpublishPR->PRIdentifier));
    }

    private function answersToHealthChecks(BotMan $bot): void
    {
        $bot->hears(
            'alive',
            function (BotMan $bot) {
                $bot->reply('yes :+1:');
            }
        );
        $bot->listen();
    }

    private function getChannelIdentifier(BotMan $bot): string
    {
        $payload = $bot->getMessage()->getPayload();
        $workspace = $this->getWorkspaceIdentifier($bot);
        $channel = $payload['channel'];
        $this->logger->info(sprintf('Now fetching channel information for workspace "%s" and channel "%s"', $workspace, $channel));

        return ChannelIdentifierHelper::from($workspace, $this->getChannelInformation->fetch($workspace, $channel)->channelName);
    }

    private function getMessageIdentifier(BotMan $bot): string
    {
        $messageBody = $bot->getMessage()->getPayload();
        $workspace = $this->getWorkspaceIdentifier($bot);
        $channel = $messageBody['channel'];
        $ts = $messageBody['ts'];

        return MessageIdentifierHelper::from($workspace, $channel, $ts);
    }

    private function PRInfo(string $PRNumber, string $repositoryIdentifier): PRInfo
    {
        $PRIdentifier = $this->PRIdentifier($PRNumber, $repositoryIdentifier);

        return $this->getPRInfo->fetch(PRIdentifier::fromString($PRIdentifier));
    }

    private function PRIdentifier(string $PRNumber, string $repositoryIdentifier): string
    {
        return sprintf('%s/%s', $repositoryIdentifier, $PRNumber);
    }

    /**
     * TODO: To rework once the whole @botname works
     */
    private function providesToHelp(BotMan $bot): void
    {
        $userHelp = function (BotMan $bot) {
            $message = <<<MESSAGE
    *Hello I'm Yeee!*
    I'm here to improve the feedback loop between you and your PR statuses.

    Ever wonder how to work with me ? Here are some advices ;)

    *1. I track the PRs you put to review directly in slack. Make sure they have the following structure:*
    ```
    ... TR ... {PR link} ...
    ... PR ... {PR link} ...
    ... review ... {PR link} ...
    ```

    *2. I post daily reminders for you and your teams to review PRs. To unpublish a PR from it, just let me know like this:*
    ```@Yeee Unpublish {PR link}```

    *3. If you found a bug, <https://github.com/SamirBoulil/slub/issues/new|you can open a new issue>!*

    That's it! Have a wonderful day :yee:
    MESSAGE;
            $bot->reply($message);
        };
        $botUserId = $this->getBotUserId->fetch($this->getWorkspaceIdentifier($this->bot));
        $yeeeHelp = sprintf('.*help.*', $botUserId);
        $helpYeee = sprintf('.*help.*<@%s>.*', $botUserId);
        $bot->hears($yeeeHelp, $userHelp);
        $bot->hears($helpYeee, $userHelp);
    }

    private function getWorkspaceIdentifier(BotMan $bot): string
    {
        $payload = $bot->getMessage()->getPayload();

        return $payload['team'];
    }
}
