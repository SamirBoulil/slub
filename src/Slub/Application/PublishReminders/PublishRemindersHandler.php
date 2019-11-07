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

    /** @var string[] */
    private $supportedChannelsForFeature;

    public function __construct(
        PRRepositoryInterface $PRRepository,
        ChatClient $chatClient,
        LoggerInterface $logger,
        string $activatedChannels
    ) {
        $this->logger = $logger;
        $this->chatClient = $chatClient;
        $this->PRRepository = $PRRepository;
        $this->supportedChannelsForFeature = explode(',', $activatedChannels);
    }

    public function handle(): void
    {
        $PRsInReview = $this->PRRepository->findPRToReviewNotGTMed();
        $channelIdentifiers = $this->channelIdentifiers($PRsInReview);
        foreach ($channelIdentifiers as $channelIdentifier) {
            if ($this->isChannelIsSupportedForFeature($channelIdentifier)) {
                $this->publishReminderForChannel($channelIdentifier, $PRsInReview);
            }
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

    private function formatReminders(array $prs): string
    {
        $PRReminders = array_map(function (PR $PR) {
            return $this->formatReminder($PR);
        }, $prs);
        $reminder = <<<CHAT
Yop, these PRs need reviews!
%s
CHAT;

        $result = sprintf($reminder, implode("\n", $PRReminders));

        return $result;
    }

    private function formatReminder(PR $PR): string
    {
        $githubLink = function (PR $PR) {
            $split = explode('/', $PR->PRIdentifier()->stringValue());

            return sprintf('https://github.com/%s/%s/pull/%s', ...$split);
        };
        $author = $PR->authorIdentifier()->stringValue();
        $title = $PR->title()->stringValue();
        $githubLink = $githubLink($PR);
        $numberOfDaysInReview = 0 === $PR->numberOfDaysInReview() ? 'Today' : $PR->numberOfDaysInReview();

        return sprintf(' - %s, "%s" (%s) %s', $author, $title, $numberOfDaysInReview, $githubLink);
    }

    private function isChannelIsSupportedForFeature(ChannelIdentifier $channelIdentifier): bool
    {
        return in_array($channelIdentifier->stringValue(), $this->supportedChannelsForFeature);
    }
}
