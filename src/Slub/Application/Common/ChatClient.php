<?php
declare(strict_types=1);

namespace Slub\Application\Common;

use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;

interface ChatClient
{
    public function replyInThread(MessageIdentifier $messageIdentifier, string $text): void;
    public function setReactionsToMessageWith(MessageIdentifier $messageIdentifier, array $reactionsToSet): void;
    public function publishInChannel(ChannelIdentifier $channelIdentifier, string $text): void;
    public function answerWithEphemeralMessage(string $url, string $text): void;
    public function publishMessageWithBlocksInChannel(ChannelIdentifier $channelIdentifier, array $blocks): string;
    public function explainPRURLCannotBeParsed(string $url, string $usage): void;
    public function explainAppNotInstalled(string $url, string $usage): void;
    public function explainSomethingWentWrong(string $url, string $usage, string $action): void;
    public function publishToReviewMessage(
        string $channelIdentifier,
        string $PRUrl,
        string $title,
        string $repositoryIdentifier,
        int $additions,
        int $deletions,
        string $authorIdentifier,
        string $authorImageUrl,
        string $description
    ): string;
}
