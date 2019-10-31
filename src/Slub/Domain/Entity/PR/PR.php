<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\PR;

use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Event\CIGreen;
use Slub\Domain\Event\CIPending;
use Slub\Domain\Event\CIRed;
use Slub\Domain\Event\PRCommented;
use Slub\Domain\Event\PRGTMed;
use Slub\Domain\Event\PRMerged;
use Slub\Domain\Event\PRNotGTMed;
use Slub\Domain\Event\PRPutToReview;
use Symfony\Component\EventDispatcher\Event;
use Webmozart\Assert\Assert;

class PR
{
    private const IDENTIFIER_KEY = 'IDENTIFIER';
    private const GTM_KEY = 'GTMS';
    private const NOT_GTM_KEY = 'NOT_GTMS';
    private const CI_STATUS_KEY = 'CI_STATUS';
    private const IS_MERGED_KEY = 'IS_MERGED';
    private const MESSAGE_IDS = 'MESSAGE_IDS';
    private const CHANNEL_IDS = 'CHANNEL_IDS';
    private const COMMENTS_KEY = 'COMMENTS';
    private const PUT_TO_REVIEW_AT = 'PUT_TO_REVIEW_AT';
    private const MERGED_AT = 'MERGED_AT';

    /** @var Event[] */
    private $events = [];

    /** @var PRIdentifier */
    private $PRIdentifier;

    /** @var ChannelIdentifier[] */
    private $channelIdentifiers;

    /** @var MessageIdentifier[] */
    private $messageIdentifiers;

    /** @var int */
    private $GTMCount;

    /** @var int */
    private $comments;

    /** @var int */
    private $notGTMCount;

    /** @var CIStatus */
    private $CIStatus;

    /** @var bool */
    private $isMerged;

    /** @var PutToReviewAt */
    private $putToReviewAt;

    /** @var MergedAt */
    private $mergedAt;

    private function __construct(
        PRIdentifier $PRIdentifier,
        array $channelIdentifiers,
        array $messageIds,
        int $GTMCount,
        int $notGTMCount,
        int $comments,
        CIStatus $CIStatus,
        bool $isMerged,
        PutToReviewAt $putToReviewAt,
        MergedAt $mergedAt
    ) {
        $this->PRIdentifier = $PRIdentifier;
        $this->GTMCount = $GTMCount;
        $this->notGTMCount = $notGTMCount;
        $this->comments = $comments;
        $this->CIStatus = $CIStatus;
        $this->isMerged = $isMerged;
        $this->channelIdentifiers = $channelIdentifiers;
        $this->messageIdentifiers = $messageIds;
        $this->putToReviewAt = $putToReviewAt;
        $this->mergedAt = $mergedAt;
    }

    public static function create(
        PRIdentifier $PRIdentifier,
        ChannelIdentifier $channelIdentifier,
        MessageIdentifier $messageIdentifier,
        int $GTMs = 0,
        int $notGTMs = 0,
        int $comments = 0,
        string $CIStatus = 'PENDING',
        bool $isMerged = false
    ): self {
        $pr = new self(
            $PRIdentifier, [$channelIdentifier], [$messageIdentifier], $GTMs, $notGTMs, $comments,
            CIStatus::endedWith(
                BuildResult::fromNormalized($CIStatus),
                BuildLink::none()
            ),
            $isMerged, PutToReviewAt::create(), MergedAt::none()
        );
        $pr->events[] = PRPutToReview::forPR($PRIdentifier, $messageIdentifier);

        return $pr;
    }

    public static function fromNormalized(array $normalizedPR): self
    {
        Assert::keyExists($normalizedPR, self::IDENTIFIER_KEY);
        Assert::keyExists($normalizedPR, self::GTM_KEY);
        Assert::keyExists($normalizedPR, self::NOT_GTM_KEY);
        Assert::keyExists($normalizedPR, self::COMMENTS_KEY);
        Assert::keyExists($normalizedPR, self::CI_STATUS_KEY);
        Assert::keyExists($normalizedPR, self::IS_MERGED_KEY);
        Assert::keyExists($normalizedPR, self::MESSAGE_IDS);
        Assert::keyExists($normalizedPR, self::CHANNEL_IDS);
        Assert::keyExists($normalizedPR, self::PUT_TO_REVIEW_AT);
        Assert::keyExists($normalizedPR, self::MERGED_AT);
        Assert::isArray($normalizedPR[self::MESSAGE_IDS]);

        $identifier = PRIdentifier::fromString($normalizedPR[self::IDENTIFIER_KEY]);
        $GTM = $normalizedPR[self::GTM_KEY];
        $NOTGTM = $normalizedPR[self::NOT_GTM_KEY];
        $CIStatus = $normalizedPR[self::CI_STATUS_KEY];
        $comments = $normalizedPR[self::COMMENTS_KEY];
        $isMerged = $normalizedPR[self::IS_MERGED_KEY];
        $messageIds = array_map(
            function (string $messageId) {
                return MessageIdentifier::fromString($messageId);
            },
            $normalizedPR[self::MESSAGE_IDS]
        );
        $channelIdentifiers = array_map(
            function (string $channelIdentifiers) {
                return ChannelIdentifier::fromString($channelIdentifiers);
            },
            $normalizedPR[self::CHANNEL_IDS]
        );
        $putToReviewAt = PutToReviewAt::fromTimestamp($normalizedPR[self::PUT_TO_REVIEW_AT]);
        $mergedAt = MergedAt::fromTimestampIfAny($normalizedPR[self::MERGED_AT]);

        return new self(
            $identifier,
            $channelIdentifiers,
            $messageIds,
            $GTM,
            $NOTGTM,
            $comments,
            CIStatus::fromNormalized($CIStatus),
            $isMerged,
            $putToReviewAt, $mergedAt
        );
    }

    public function normalize(): array
    {
        return [
            self::IDENTIFIER_KEY => $this->PRIdentifier()->stringValue(),
            self::GTM_KEY => $this->GTMCount,
            self::NOT_GTM_KEY => $this->notGTMCount,
            self::COMMENTS_KEY => $this->comments,
            self::CI_STATUS_KEY => $this->CIStatus->normalize(),
            self::IS_MERGED_KEY => $this->isMerged,
            self::CHANNEL_IDS => array_map(
                function (ChannelIdentifier $channelIdentifier) {
                    return $channelIdentifier->stringValue();
                },
                $this->channelIdentifiers
            ),
            self::MESSAGE_IDS => array_map(
                function (MessageIdentifier $messageIdentifier) {
                    return $messageIdentifier->stringValue();
                },
                $this->messageIdentifiers
            ),
            self::PUT_TO_REVIEW_AT => $this->putToReviewAt->toTimestamp(),
            self::MERGED_AT => $this->mergedAt->toTimestamp(),
        ];
    }

    public function PRIdentifier(): PRIdentifier
    {
        return $this->PRIdentifier;
    }

    public function GTM(): void
    {
        $this->GTMCount++;
        $this->events[] = PRGTMed::forPR($this->PRIdentifier);
    }

    public function notGTM(): void
    {
        $this->notGTMCount++;
        $this->events[] = PRNotGTMed::forPR($this->PRIdentifier);
    }

    public function comment(): void
    {
        $this->comments++;
        $this->events[] = PRCommented::forPR($this->PRIdentifier);
    }

    public function green(): void
    {
        if ($this->CIStatus->isGreen()) {
            return;
        }

        $this->CIStatus = CIStatus::endedWith(
            BuildResult::green(),
            BuildLink::none()
        );
        $this->events[] = CIGreen::ForPR($this->PRIdentifier);
    }

    public function red(): void
    {
        if ($this->CIStatus->isRed()) {
            return;
        }

        $this->CIStatus = CIStatus::endedWith(
            BuildResult::red(),
            BuildLink::none()
        );
        $this->events[] = CIRed::ForPR($this->PRIdentifier);
    }

    public function pending(): void
    {
        if ($this->CIStatus->isPending()) {
            return;
        }

        $this->CIStatus = CIStatus::endedWith(
            BuildResult::pending(),
            BuildLink::none()
        );
        $this->events[] = CIPending::ForPR($this->PRIdentifier);
    }

    public function merged(): void
    {
        $this->isMerged = true;
        $this->mergedAt = MergedAt::create();
        $this->events[] = PRMerged::ForPR($this->PRIdentifier);
    }

    /**
     * @return MessageIdentifier[]
     */
    public function messageIdentifiers(): array
    {
        return $this->messageIdentifiers;
    }

    /**
     * @return Event[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    public function putToReviewAgainViaMessage(
        ChannelIdentifier $newChannelIdentifier,
        MessageIdentifier $newMessageIdentifier
    ): void {
        if ($this->hasMessageIdentifier($newMessageIdentifier) && $this->hasChannelIdentifier($newChannelIdentifier)) {
            return;
        }

        $hasPRBeenPutToReviewAgain = false;
        if (!$this->hasChannelIdentifier($newChannelIdentifier)) {
            $hasPRBeenPutToReviewAgain = true;
            $this->channelIdentifiers[] = $newChannelIdentifier;
        }
        if (!$this->hasMessageIdentifier($newMessageIdentifier)) {
            $hasPRBeenPutToReviewAgain = true;
            $this->messageIdentifiers[] = $newMessageIdentifier;
        }

        if ($hasPRBeenPutToReviewAgain) {
            $this->events[] = PRPutToReview::forPR($this->PRIdentifier, $newMessageIdentifier);
        }
    }

    /**
     * @return ChannelIdentifier[]
     */
    public function channelIdentifiers(): array
    {
        return $this->channelIdentifiers;
    }

    private function hasMessageIdentifier(MessageIdentifier $newMessageIdentifier): bool
    {
        return in_array(
            $newMessageIdentifier->stringValue(),
            array_map(
                function (MessageIdentifier $messageIdentifier) {
                    return $messageIdentifier->stringValue();
                },
                $this->messageIdentifiers
            ),
            true
        );
    }

    private function hasChannelIdentifier(ChannelIdentifier $newChannelIdentifier): bool
    {
        return in_array(
            $newChannelIdentifier->stringValue(),
            array_map(
                function (ChannelIdentifier $channelIdentifier) {
                    return $channelIdentifier->stringValue();
                },
                $this->channelIdentifiers
            ),
            true
        );
    }
}
