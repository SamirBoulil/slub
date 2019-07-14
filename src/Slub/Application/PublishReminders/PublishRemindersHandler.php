<?php

declare(strict_types=1);

namespace Slub\Application\PublishReminders;

use Psr\Log\LoggerInterface;
use Slub\Application\Common\ChatClient;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Repository\PRRepositoryInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class PublishRemindersHandler
{
    /** @var PRRepositoryInterface */
    private $PRRepository;

    /** @var LoggerInterface */
    private $logger;

    /** @var ChatClient */
    private $chatClient;

    public function __construct(
        PRRepositoryInterface $PRRepository,
        ChatClient $chatClient,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->chatClient = $chatClient;
        $this->PRRepository = $PRRepository;
    }

    public function handle(): void
    {
        $PRsInReview = $this->PRRepository->findPRToReviewNotGTMed();
        $channelIdentifiers = $this->channelIdentifiers($PRsInReview);
        foreach ($channelIdentifiers as $channelIdentifier) {
            $this->publishReminderForChannel($channelIdentifier, $PRsInReview);
        }

        $this->logger->info('Reminders published');
    }

    /**
     * @return ChannelIdentifier[]
     */
    private function channelIdentifiers(array $PRsInReview): array
    {
        $channelIdentifiers = [];
        /** @var PR $pr */
        foreach ($PRsInReview as $pr) {
            foreach ($pr->channelIdentifiers() as $channelIdentifier) {
                $channelIdentifiers[$channelIdentifier->stringValue()] = $channelIdentifier;
            }
        }

        return array_values($channelIdentifiers);
    }

    private function prsPutToReviewInChannel(ChannelIdentifier $expectedChannelIdentifier, array $PRsInReview): array
    {
        return array_filter(
            $PRsInReview,
            function (PR $PR) use ($expectedChannelIdentifier) {
                return array_filter(
                    $PR->channelIdentifiers(),
                    function (ChannelIdentifier $actualChannelIdentifier) use ($expectedChannelIdentifier) {
                        return $expectedChannelIdentifier->equals($actualChannelIdentifier);
                    }
                );
            }
        );
    }

    private function formatReminder(array $prs): string
    {
        $reminder = <<<CHAT
Yop, these PRs need reviews!
 - %s
CHAT;
        $identifiers = implode(
            "\n - ",
            array_map(function (PR $PR) {
                $split = explode('/', $PR->PRIdentifier()->stringValue());

                return sprintf('https://github.com/%s/%s/pulls/%s', ...$split);

            },
                $prs
            )
        );

        return sprintf($reminder, $identifiers);
    }

    private function publishReminderForChannel(ChannelIdentifier $channelIdentifier, array $PRsInReview): void
    {
        $PRsToPublish = $this->prsPutToReviewInChannel($channelIdentifier, $PRsInReview);
        $this->chatClient->publishInChannel($channelIdentifier, $this->formatReminder($PRsToPublish));
    }
}
