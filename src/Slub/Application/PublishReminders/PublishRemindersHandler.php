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
    private const RACCOONS_CHANNEL_ID = 'T031L1UKF@C02NX6YF62Y';

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
            $this->publishNewReminderForChannel($channelIdentifier, $PRsInReview);
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

    private function publishNewReminderForChannel(ChannelIdentifier $channelIdentifier, array $PRsInReview): void
    {
        $PRsToPublish = $this->prsPutToReviewInChannel($channelIdentifier, $PRsInReview);
        $blocks = $this->formatReminderInBlocks($PRsToPublish);
        $this->chatClient->publishMessageWithBlocksInChannel($channelIdentifier, $blocks);
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

    private function formatReminderBlock(PR $PR): array
    {
        $githubLink = static function (PR $PR) {
            $split = explode('/', $PR->PRIdentifier()->stringValue());

            return sprintf('https://github.com/%s/%s/pull/%s', ...$split);
        };
        $author = ucfirst($PR->authorIdentifier()->stringValue());
        $title = ChatHelper::elipsisIfTooLong(
            $PR->title()->stringValue(),
            95
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
        switch ($PR->numberOfDaysInReview()) {
            case 0:
                return 'Today';
            case 1:
                return 'Yesterday';
            default:
                return sprintf('%d days ago', $PR->numberOfDaysInReview());
        }
    }

    private function formatReminderInBlocks(array $PRsToPublish)
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

    private function sortPRsByNumberOfDaysInReview(array $prs): array
    {
        usort($prs, fn (PR $pr1, PR $pr2) => $pr1->numberOfDaysInReview() <=> $pr2->numberOfDaysInReview());

        return $prs;
    }
}
