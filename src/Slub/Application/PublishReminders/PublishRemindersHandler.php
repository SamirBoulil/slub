<?php

declare(strict_types=1);

namespace Slub\Application\PublishReminders;

use Psr\Log\LoggerInterface;
use Slub\Application\Common\ChatClient;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Query\ClockInterface;
use Slub\Domain\Repository\PRRepositoryInterface;
use Slub\Infrastructure\Chat\Common\ChatHelper;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class PublishRemindersHandler
{
    public function __construct(private PRRepositoryInterface $PRRepository, private ChatClient $chatClient, private LoggerInterface $logger, private ClockInterface $clock)
    {
    }

    public function handle(): void
    {
        if ($this->clock->areWeOnWeekEnd()) {
            return;
        }
        $this->publishReminders();
    }

    private function publishReminders(): void
    {
        $PRsInReview = $this->PRRepository->findPRToReviewNotGTMed();
        $channelIdentifiers = $this->channelIdentifiers($PRsInReview);
        foreach ($channelIdentifiers as $channelIdentifier) {
            $this->publishReminder($channelIdentifier, $PRsInReview);
        }
        // $this->logger->info('Reminders published');
    }

    private function publishReminder(ChannelIdentifier $channelIdentifier, array $PRsInReview): void
    {
        $PRsToPublish = $this->prsPutToReviewInChannel($channelIdentifier, $PRsInReview);
        $blocks = $this->formatReminderInBlocks($PRsToPublish);
        try {
            $this->chatClient->publishMessageWithBlocksInChannel($channelIdentifier, $blocks);
        } catch (\throwable $e) {
            // $this->logger->alert(sprintf('Was not able to publish reminder, "%s"', $e->getMessage()));
        }
    }

    private function formatReminderInBlocks(array $PRsToPublish): array
    {
        $prs = $this->sortPRsByNumberOfDaysInReview($PRsToPublish);
        $reminderInBlocks = array_map(fn (PR $PR) => $this->formatReminderBlock($PR), $prs);
        array_unshift($reminderInBlocks, [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => 'Yeee, these PRs need reviews!',
            ],
        ]);

        return $reminderInBlocks;
    }

    private function prsPutToReviewInChannel(ChannelIdentifier $expectedChannelIdentifier, array $PRsInReview): array
    {
        return array_filter(
            $PRsInReview,
            static fn (PR $PR) => array_filter(
                $PR->channelIdentifiers(),
                static fn (ChannelIdentifier $actualChannelIdentifier) => $expectedChannelIdentifier->equals(
                    $actualChannelIdentifier
                )
            )
        );
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

    private function formatReminderBlock(PR $PR): array
    {
        $githubLink = static function (PR $PR) {
            $split = explode('/', $PR->PRIdentifier()->stringValue());

            return sprintf('https://github.com/%s/%s/pull/%s', ...$split);
        };
        $author = ucfirst($PR->authorIdentifier()->stringValue());
        $title = ChatHelper::elipsisIfTooLong(
            $PR->title()->stringValue(),
            80
        ); // TODO: Big no no here, Apps -> Infra 😱
        $githubLink = $githubLink($PR);
        $timeInReview = $this->formatDuration($PR);

        return [
            'type' => 'context',
            'elements' => [
                [
                    'type' => 'image',
                    'image_url' => sprintf('https://avatars.githubusercontent.com/%s', $author),
                    'alt_text' => sprintf('%s is the author of the PR', $author),
                ],
                [
                    'type' => 'mrkdwn',
                    'text' => sprintf('*<%s|%s>*, _%s_', $githubLink, $title, $timeInReview),
                ],
            ],
        ];
    }

    private function formatDuration(PR $PR): string
    {
        return match ($PR->numberOfDaysInReview()) {
            0 => 'Today',
            1 => 'Yesterday',
            default => sprintf('%d days ago', $PR->numberOfDaysInReview()),
        };
    }

    private function sortPRsByNumberOfDaysInReview(array $prs): array
    {
        usort($prs, fn (PR $pr1, PR $pr2) => $pr1->numberOfDaysInReview() <=> $pr2->numberOfDaysInReview());

        return $prs;
    }
}
