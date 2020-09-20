<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\PR;

use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\Reviewer\ReviewerName;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;
use Slub\Domain\Event\CIGreen;
use Slub\Domain\Event\CIPending;
use Slub\Domain\Event\CIRed;
use Slub\Domain\Event\GoodToMerge;
use Slub\Domain\Event\PRClosed;
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
    private const TITLE_KEY = 'TITLE';
    private const AUTHOR_ID_KEY = 'AUTHOR_IDENTIFIER';
    private const GTM_KEY = 'GTMS';
    private const NOT_GTM_KEY = 'NOT_GTMS';
    private const CI_STATUS_KEY = 'CI_STATUS';
    private const IS_MERGED_KEY = 'IS_MERGED';
    private const MESSAGE_IDS = 'MESSAGE_IDS';
    private const CHANNEL_IDS = 'CHANNEL_IDS';
    private const WORKSPACE_IDS = 'WORKSPACE_IDS';
    private const COMMENTS_KEY = 'COMMENTS';
    private const PUT_TO_REVIEW_AT = 'PUT_TO_REVIEW_AT';
    private const CLOSED_AT = 'CLOSED_AT';

    /** @var Event[] */
    private $events = [];

    /** @var PRIdentifier */
    private $PRIdentifier;

    /** @var ChannelIdentifier[] */
    private $channelIdentifiers;

    /** @var WorkspaceIdentifier[] */
    private $workspaceIdentifiers;

    /** @var MessageIdentifier[] */
    private $messageIdentifiers;

    /** @var AuthorIdentifier */
    private $authorIdentifier;

    /** @var Title */
    private $title;

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

    /** @var ClosedAt */
    private $closedAt;

    private function __construct(
        PRIdentifier $PRIdentifier,
        array $channelIdentifiers,
        array $workspaceIdentifiers,
        array $messageIds,
        AuthorIdentifier $authorIdentifier,
        Title $title,
        int $GTMCount,
        int $notGTMCount,
        int $comments,
        CIStatus $CIStatus,
        bool $isMerged,
        PutToReviewAt $putToReviewAt,
        ClosedAt $closedAt
    ) {
        $this->PRIdentifier = $PRIdentifier;
        $this->authorIdentifier = $authorIdentifier;
        $this->title = $title;
        $this->GTMCount = $GTMCount;
        $this->notGTMCount = $notGTMCount;
        $this->comments = $comments;
        $this->CIStatus = $CIStatus;
        $this->channelIdentifiers = $channelIdentifiers;
        $this->workspaceIdentifiers = $workspaceIdentifiers;
        $this->messageIdentifiers = $messageIds;
        $this->putToReviewAt = $putToReviewAt;
        $this->closedAt = $closedAt;
        $this->isMerged = $isMerged;
    }

    public static function create(
        PRIdentifier $PRIdentifier,
        ChannelIdentifier $channelIdentifier,
        WorkspaceIdentifier $workspaceIdentifier,
        MessageIdentifier $messageIdentifier,
        AuthorIdentifier $authorIdentifier,
        Title $title,
        int $GTMs = 0,
        int $notGTMs = 0,
        int $comments = 0,
        string $CIStatus = 'PENDING',
        bool $isMerged = false
    ): self {
        $pr = new self(
            $PRIdentifier,
            [$channelIdentifier],
            [$workspaceIdentifier],
            [$messageIdentifier],
            $authorIdentifier,
            $title,
            $GTMs,
            $notGTMs,
            $comments,
            CIStatus::endedWith(
                BuildResult::fromNormalized($CIStatus),
                BuildLink::none()
            ),
            $isMerged,
            PutToReviewAt::create(),
            ClosedAt::none()
        );
        $pr->events[] = PRPutToReview::forPR($PRIdentifier, $messageIdentifier);

        return $pr;
    }

    public static function fromNormalized(array $normalizedPR): self
    {
        Assert::keyExists($normalizedPR, self::IDENTIFIER_KEY);
        Assert::keyExists($normalizedPR, self::AUTHOR_ID_KEY);
        Assert::keyExists($normalizedPR, self::TITLE_KEY);
        Assert::keyExists($normalizedPR, self::GTM_KEY);
        Assert::keyExists($normalizedPR, self::NOT_GTM_KEY);
        Assert::keyExists($normalizedPR, self::COMMENTS_KEY);
        Assert::keyExists($normalizedPR, self::CI_STATUS_KEY);
        Assert::keyExists($normalizedPR, self::IS_MERGED_KEY);
        Assert::keyExists($normalizedPR, self::MESSAGE_IDS);
        Assert::keyExists($normalizedPR, self::WORKSPACE_IDS);
        Assert::keyExists($normalizedPR, self::CHANNEL_IDS);
        Assert::keyExists($normalizedPR, self::PUT_TO_REVIEW_AT);
        Assert::keyExists($normalizedPR, self::CLOSED_AT);
        Assert::isArray($normalizedPR[self::MESSAGE_IDS]);

        $identifier = PRIdentifier::fromString($normalizedPR[self::IDENTIFIER_KEY]);
        $author = AuthorIdentifier::fromString($normalizedPR[self::AUTHOR_ID_KEY]);
        $title = Title::fromString($normalizedPR[self::TITLE_KEY]);
        $GTM = $normalizedPR[self::GTM_KEY];
        $NOTGTM = $normalizedPR[self::NOT_GTM_KEY];
        $CIStatus = $normalizedPR[self::CI_STATUS_KEY];
        $comments = $normalizedPR[self::COMMENTS_KEY];
        $isMerged = $normalizedPR[self::IS_MERGED_KEY];
        $messageIds = array_map(
            static function (string $messageId) {
                return MessageIdentifier::fromString($messageId);
            },
            $normalizedPR[self::MESSAGE_IDS]
        );
        $channelIdentifiers = array_map(
            static function (string $channelIdentifier) {
                return ChannelIdentifier::fromString($channelIdentifier);
            },
            $normalizedPR[self::CHANNEL_IDS]
        );
        $workspaceIdentifiers = array_map(
            static function (string $workspaceIdentifier) {
                return WorkspaceIdentifier::fromString($workspaceIdentifier);
            },
            $normalizedPR[self::WORKSPACE_IDS]
        );
        $putToReviewAt = PutToReviewAt::fromTimestamp($normalizedPR[self::PUT_TO_REVIEW_AT]);
        $closedAt = ClosedAt::fromTimestampIfAny($normalizedPR[self::CLOSED_AT]);

        return new self(
            $identifier,
            $channelIdentifiers,
            $workspaceIdentifiers,
            $messageIds,
            $author,
            $title,
            $GTM,
            $NOTGTM,
            $comments,
            CIStatus::fromNormalized($CIStatus),
            $isMerged,
            $putToReviewAt,
            $closedAt
        );
    }

    public function normalize(): array
    {
        return [
            self::IDENTIFIER_KEY => $this->PRIdentifier()->stringValue(),
            self::AUTHOR_ID_KEY => $this->authorIdentifier->stringValue(),
            self::TITLE_KEY => $this->title->stringValue(),
            self::GTM_KEY => $this->GTMCount,
            self::NOT_GTM_KEY => $this->notGTMCount,
            self::COMMENTS_KEY => $this->comments,
            self::CI_STATUS_KEY => $this->CIStatus->normalize(),
            self::IS_MERGED_KEY => $this->isMerged,
            self::CHANNEL_IDS => array_map(
                static function (ChannelIdentifier $channelIdentifier) {
                    return $channelIdentifier->stringValue();
                },
                $this->channelIdentifiers
            ),
            self::WORKSPACE_IDS => array_map(
                static function (WorkspaceIdentifier $workspaceIdentifier) {
                    return $workspaceIdentifier->stringValue();
                },
                $this->workspaceIdentifiers
            ),
            self::MESSAGE_IDS => array_map(
                function (MessageIdentifier $messageIdentifier) {
                    return $messageIdentifier->stringValue();
                },
                $this->messageIdentifiers
            ),
            self::PUT_TO_REVIEW_AT => $this->putToReviewAt->toTimestamp(),
            self::CLOSED_AT => $this->closedAt->toTimestamp(),
        ];
    }

    public function PRIdentifier(): PRIdentifier
    {
        return $this->PRIdentifier;
    }

    public function authorIdentifier(): AuthorIdentifier
    {
        return $this->authorIdentifier;
    }

    public function title(): Title
    {
        return $this->title;
    }

    public function GTM(ReviewerName $reviewerName): void
    {
        if ($this->isMerged) {
            return;
        }

        ++$this->GTMCount;
        $this->events[] = PRGTMed::forPR($this->PRIdentifier, $reviewerName);
        $this->checkThePRIsGoodToMerge();
    }

    public function notGTM(ReviewerName $reviewerName): void
    {
        if ($this->isMerged) {
            return;
        }

        ++$this->notGTMCount;
        $this->events[] = PRNotGTMed::forPR($this->PRIdentifier, $reviewerName);
    }

    public function comment(ReviewerName $reviewerName): void
    {
        if ($this->isMerged) {
            return;
        }

        ++$this->comments;
        $this->events[] = PRCommented::forPR($this->PRIdentifier, $reviewerName);
    }

    public function green(): void
    {
        if ($this->isMerged) {
            return;
        }
        if ($this->CIStatus->isGreen()) {
            return;
        }

        $this->CIStatus = CIStatus::endedWith(
            BuildResult::green(),
            BuildLink::none()
        );
        $this->events[] = CIGreen::ForPR($this->PRIdentifier);
        $this->checkThePRIsGoodToMerge();
    }

    public function red(BuildLink $buildLink): void
    {
        if ($this->isMerged) {
            return;
        }
        if ($this->CIStatus->isRedWithLink($buildLink)) {
            return;
        }

        $this->CIStatus = CIStatus::endedWith(BuildResult::red(), $buildLink);
        $this->events[] = CIRed::ForPR($this->PRIdentifier, $buildLink);
    }

    public function pending(): void
    {
        if ($this->isMerged) {
            return;
        }

        if ($this->CIStatus->isPending()) {
            return;
        }

        $this->CIStatus = CIStatus::endedWith(
            BuildResult::pending(),
            BuildLink::none()
        );
        $this->events[] = CIPending::ForPR($this->PRIdentifier);
    }

    public function close(bool $isMerged): void
    {
        if ($isMerged) {
            $this->isMerged = true;
            $this->events[] = PRMerged::ForPR($this->PRIdentifier);
        }
        $this->closedAt = ClosedAt::create();
        $this->events[] = PRClosed::ForPR($this->PRIdentifier);
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

    public function numberOfDaysInReview(): int
    {
        return $this->putToReviewAt->numberOfDaysInReview();
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

    public function reopen(): void
    {
        $this->isMerged = false;
        $this->closedAt = ClosedAt::none();
    }

    private function checkThePRIsGoodToMerge(): void
    {
        $isGoodToMerge = 0 === $this->notGTMCount && $this->GTMCount >= 2 && $this->CIStatus->isGreen();
        if ($isGoodToMerge) {
            $this->events[] = GoodToMerge::forPR($this->PRIdentifier);
        }
    }
}
