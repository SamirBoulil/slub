<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\PR;

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
    private const COMMENTS_KEY = 'COMMENTS';

    /** @var Event[] */
    private $events = [];

    /** @var PRIdentifier */
    private $PRIdentifier;

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

    private function __construct(
        PRIdentifier $PRIdentifier,
        array $messageIds,
        int $GTMCount,
        int $notGTMCount,
        int $comments,
        CIStatus $CIStatus,
        bool $isMerged
    ) {
        $this->PRIdentifier = $PRIdentifier;
        $this->GTMCount = $GTMCount;
        $this->notGTMCount = $notGTMCount;
        $this->comments = $comments;
        $this->CIStatus = $CIStatus;
        $this->isMerged = $isMerged;
        $this->messageIdentifiers = $messageIds;
    }

    public static function create(
        PRIdentifier $PRIdentifier,
        MessageIdentifier $messageIdentifier,
        int $GTMs = 0,
        int $notGTMs = 0,
        int $comments = 0,
        string $CIStatus = 'PENDING',
        bool $isMerged = false
    ): self {
        $pr = new self(
            $PRIdentifier,
            [$messageIdentifier],
            $GTMs,
            $notGTMs,
            $comments,
            CIStatus::fromNormalized($CIStatus),
            $isMerged
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
        Assert::isArray($normalizedPR[self::MESSAGE_IDS]);

        $identifier = PRIdentifier::fromString($normalizedPR[self::IDENTIFIER_KEY]);
        $GTM = $normalizedPR[self::GTM_KEY];
        $NOTGTM = $normalizedPR[self::NOT_GTM_KEY];
        $CIStatus = $normalizedPR[self::CI_STATUS_KEY];
        $comments = $normalizedPR[self::COMMENTS_KEY];
        $isMerged = $normalizedPR[self::IS_MERGED_KEY];
        $messageIds = array_map(function (string $messageId) {
            return MessageIdentifier::fromString($messageId);
        }, $normalizedPR[self::MESSAGE_IDS]);

        return new self(
            $identifier,
            $messageIds,
            $GTM,
            $NOTGTM,
            $comments,
            CIStatus::fromNormalized($CIStatus),
            $isMerged
        );
    }

    public function normalize(): array
    {
        return [
            self::IDENTIFIER_KEY => $this->PRIdentifier()->stringValue(),
            self::GTM_KEY        => $this->GTMCount,
            self::NOT_GTM_KEY    => $this->notGTMCount,
            self::COMMENTS_KEY   => $this->comments,
            self::CI_STATUS_KEY  => $this->CIStatus->stringValue(),
            self::IS_MERGED_KEY  => $this->isMerged,
            self::MESSAGE_IDS    => array_map(function (MessageIdentifier $messageId) {
                return $messageId->stringValue();
            }, $this->messageIdentifiers),
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

        $this->CIStatus = CIStatus::green();
        $this->events[] = CIGreen::ForPR($this->PRIdentifier);
    }

    public function red(): void
    {
        if ($this->CIStatus->isRed()) {
            return;
        }

        $this->CIStatus = CIStatus::red();
        $this->events[] = CIRed::ForPR($this->PRIdentifier);
    }

    public function pending(): void
    {
        if ($this->CIStatus->isPending()) {
            return;
        }

        $this->CIStatus = CIStatus::pending();
        $this->events[] = CIPending::ForPR($this->PRIdentifier);
    }

    public function merged(): void
    {
        $this->isMerged = true;
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

    public function putToReviewAgainViaMessage(MessageIdentifier $newMessageId): void
    {
        $alreadyExists = !empty(
        array_filter(
            $this->messageIdentifiers,
            function (MessageIdentifier $messageId) use ($newMessageId) {
                return $messageId->equals($newMessageId);
            }
        )
        );

        if ($alreadyExists) {
            return;
        }

        $this->messageIdentifiers[] = $newMessageId;
        $this->events[] = PRPutToReview::forPR($this->PRIdentifier, $newMessageId);
    }
}
