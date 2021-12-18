<?php

declare(strict_types=1);

namespace Slub\Application\PublishReminders;

use Psr\Log\LoggerInterface;
use Slub\Application\Common\ChatClient;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Query\ClockInterface;
use Slub\Domain\Repository\PRRepositoryInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class PublishRemindersHandler
{
    private PRRepositoryInterface $PRRepository;

    private LoggerInterface $logger;

    private ChatClient $chatClient;

    private ClockInterface $clock;

    public function __construct(
        PRRepositoryInterface $PRRepository,
        ChatClient $chatClient,
        LoggerInterface $logger,
        ClockInterface $clock
    ) {
        $this->logger = $logger;
        $this->chatClient = $chatClient;
        $this->PRRepository = $PRRepository;
        $this->clock = $clock;
    }

    public function handle(): void
    {
        if ($this->clock->areWeOnWeekEnd()) {
            return;
        }

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

    private function publishReminderForChannel(ChannelIdentifier $channelIdentifier, array $PRsInReview): void
    {
        $PRsToPublish = $this->prsPutToReviewInChannel($channelIdentifier, $PRsInReview);
        $message = $this->formatReminders($PRsToPublish);
        $this->chatClient->publishInChannel($channelIdentifier, $message);
    }

    private function prsPutToReviewInChannel(ChannelIdentifier $expectedChannelIdentifier, array $PRsInReview): array
    {
        return array_filter(
            $PRsInReview,
            static fn (PR $PR) => array_filter(
                $PR->channelIdentifiers(),
                static fn (ChannelIdentifier $actualChannelIdentifier) => $expectedChannelIdentifier->equals($actualChannelIdentifier)
            )
        );
    }

    private function formatReminders(array $prs): string
    {
        usort($prs, fn (PR $pr1, PR $pr2) => $pr1->numberOfDaysInReview() <=> $pr2->numberOfDaysInReview());

        $PRReminders = array_map(fn (PR $PR) => $this->formatReminder($PR), $prs);
        $reminder = <<<CHAT
Yop, these PRs need reviews!
%s
CHAT;

        return sprintf($reminder, implode("\n", $PRReminders));
    }

    private function formatReminder(PR $PR): string
    {
        $githubLink = function (PR $PR) {
            $split = explode('/', $PR->PRIdentifier()->stringValue());

            return sprintf('https://github.com/%s/%s/pull/%s', ...$split);
        };
        $author = ucfirst($PR->authorIdentifier()->stringValue());
        $title = $PR->title()->stringValue();
        $githubLink = $githubLink($PR);
        $numberOfDaysInReview = $this->formatDuration($PR);

        return sprintf(' - *%s*, _<%s|"%s">_ (%s)', $author, $githubLink, $title, $numberOfDaysInReview);
    }

    private function formatDuration(PR $PR): string
    {
        switch ($PR->numberOfDaysInReview()) {
            case 0: return 'Today';
            case 1: return 'Yesterday';
            default: return sprintf('%d days ago', $PR->numberOfDaysInReview());
        }
    }
}
