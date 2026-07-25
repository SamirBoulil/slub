<?php

declare(strict_types=1);

namespace Slub\Application\PublishReminders;

use Psr\Log\LoggerInterface;
use Slub\Application\Common\ChatClient;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\Document\Document;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Query\ClockInterface;
use Slub\Domain\Repository\DocumentRepositoryInterface;
use Slub\Domain\Repository\PRRepositoryInterface;
use Slub\Infrastructure\Chat\Common\ChatHelper;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class PublishRemindersHandler
{
    private const CHECK_MARK = 'white_check_mark';

    public function __construct(private PRRepositoryInterface $PRRepository, private DocumentRepositoryInterface $documentRepository, private ChatClient $chatClient, private LoggerInterface $logger, private ClockInterface $clock)
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
        $documentsInReview = $this->documentRepository->all();
        $channelIdentifiers = $this->channelIdentifiers($PRsInReview, $documentsInReview);
        foreach ($channelIdentifiers as $channelIdentifier) {
            $this->publishReminder($channelIdentifier, $PRsInReview, $documentsInReview);
        }
        // $this->logger->info('Reminders published');
    }

    private function publishReminder(ChannelIdentifier $channelIdentifier, array $PRsInReview, array $documentsInReview): void
    {
        $blocks = array_merge(
            $this->formatPRsReminderInBlocks($this->prsPutToReviewInChannel($channelIdentifier, $PRsInReview)),
            $this->formatDocumentsReminderInBlocks($this->documentsPutToReviewInChannel($channelIdentifier, $documentsInReview))
        );
        try {
            $this->chatClient->publishMessageWithBlocksInChannel($channelIdentifier, $blocks);
        } catch (\throwable $e) {
            // $this->logger->alert(sprintf('Was not able to publish reminder, "%s"', $e->getMessage()));
        }
    }

    private function formatPRsReminderInBlocks(array $PRsToPublish): array
    {
        if ([] === $PRsToPublish) {
            return [];
        }

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

    private function formatDocumentsReminderInBlocks(array $documentsToPublish): array
    {
        if ([] === $documentsToPublish) {
            return [];
        }

        $documents = $this->sortDocumentsByNumberOfDaysInReview($documentsToPublish);
        $reminderInBlocks = array_map(fn (Document $document) => $this->formatDocumentReminderBlock($document), $documents);
        array_unshift($reminderInBlocks, [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => 'Yeee, these documents need reviews!',
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

    private function documentsPutToReviewInChannel(ChannelIdentifier $expectedChannelIdentifier, array $documentsInReview): array
    {
        return array_filter(
            $documentsInReview,
            static fn (Document $document) => array_filter(
                $document->channelIdentifiers(),
                static fn (ChannelIdentifier $actualChannelIdentifier) => $expectedChannelIdentifier->equals(
                    $actualChannelIdentifier
                )
            )
        );
    }

    /**
     * @return ChannelIdentifier[]
     */
    private function channelIdentifiers(array $PRsInReview, array $documentsInReview): array
    {
        $channelIdentifiers = [];
        /** @var PR $pr */
        foreach ($PRsInReview as $pr) {
            foreach ($pr->channelIdentifiers() as $channelIdentifier) {
                $channelIdentifiers[$channelIdentifier->stringValue()] = $channelIdentifier;
            }
        }
        /** @var Document $document */
        foreach ($documentsInReview as $document) {
            foreach ($document->channelIdentifiers() as $channelIdentifier) {
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
        $title = ChatHelper::escapeHtmlChars(
            ChatHelper::elipsisIfTooLong(
                $PR->title()->stringValue(),
                80
            )
        ); // TODO: Big no no here, Apps -> Infra 😱
        $githubLink = $githubLink($PR);
        $timeInReview = $this->formatDuration($PR->numberOfDaysInReview());

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

    private function formatDocumentReminderBlock(Document $document): array
    {
        $documentURL = $document->url()->asString();
        $timeInReview = $this->formatDuration($document->numberOfDaysInReview());
        $text = sprintf('*<%s|Document>*, _%s_', $documentURL, $timeInReview);
        try {
            $messageIdentifier = current($document->messageIdentifiers());
            $permalink = $this->chatClient->getMessagePermalink($messageIdentifier);
            $numberOfCheckMarks = $this->chatClient->getReactionCountForMessage($messageIdentifier, self::CHECK_MARK);
            $text = sprintf(
                '*<%s|Document>* (<%s|View message>), %d :white_check_mark:, _%s_',
                $documentURL,
                $permalink,
                $numberOfCheckMarks,
                $timeInReview
            );
        } catch (\Throwable $e) {
            // The announcement message might be gone or Slack unreachable: fall back to the document link only.
        }

        return [
            'type' => 'context',
            'elements' => [
                [
                    'type' => 'mrkdwn',
                    'text' => $text,
                ],
            ],
        ];
    }

    private function formatDuration(int $numberOfDaysInReview): string
    {
        return match ($numberOfDaysInReview) {
            0 => 'Today',
            1 => 'Yesterday',
            default => sprintf('%d days ago', $numberOfDaysInReview),
        };
    }

    private function sortPRsByNumberOfDaysInReview(array $prs): array
    {
        usort($prs, fn (PR $pr1, PR $pr2) => $pr1->numberOfDaysInReview() <=> $pr2->numberOfDaysInReview());

        return $prs;
    }

    private function sortDocumentsByNumberOfDaysInReview(array $documents): array
    {
        usort($documents, fn (Document $document1, Document $document2) => $document1->numberOfDaysInReview() <=> $document2->numberOfDaysInReview());

        return $documents;
    }
}
