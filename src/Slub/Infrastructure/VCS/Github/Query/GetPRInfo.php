<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query;

use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetPRInfoInterface;
use Slub\Domain\Query\PRInfo;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CheckStatus;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetPRInfo implements GetPRInfoInterface
{
    /** @var GetPRDetails */
    private $getPRDetails;

    /** @var FindReviews */
    private $findReviews;

    /** @var GetCIStatus */
    private $getCIStatus;

    public function __construct(GetPRDetails $getPRDetails, FindReviews $findReviews, GetCIStatus $getCIStatus)
    {
        $this->getPRDetails = $getPRDetails;
        $this->findReviews = $findReviews;
        $this->getCIStatus = $getCIStatus;
    }

    public function fetch(PRIdentifier $PRIdentifier): PRInfo
    {
        $PRDetails = $this->getPRDetails->fetch($PRIdentifier);
        $reviews = $this->findReviews->fetch($PRIdentifier);
        $ciStatus = $this->getCIStatus->fetch($PRIdentifier, $this->getPRCommitRef($PRDetails));
        $isMerged = $this->isMerged($PRDetails);
        $isClosed = $this->isClosed($PRDetails);
        $authorIdentifier = $this->authorIdentifier($PRDetails);
        $title = $this->title($PRDetails);
        $result = $this->createPRInfo($PRIdentifier, $authorIdentifier, $title, $reviews, $ciStatus, $isMerged, $isClosed);

        return $result;
    }

    private function getPRCommitRef(array $PRDetails): string
    {
        return $PRDetails['head']['sha'];
    }

    private function isMerged(array $PRDetails): bool
    {
        return 'closed' === $PRDetails['state'];
    }

    private function isClosed(array $PRDetails): bool
    {
        return 'closed' === $PRDetails['state'];
    }

    private function createPRInfo(
        PRIdentifier $PRIdentifier,
        string $authorIdentifier,
        string $title,
        array $reviews,
        CheckStatus $ciStatus,
        bool $isMerged,
        bool $isClosed
    ): PRInfo {
        $result = new PRInfo();
        $result->PRIdentifier = $PRIdentifier->stringValue();
        $result->authorIdentifier = $authorIdentifier;
        $result->title = $title;
        $result->GTMCount = $reviews[FindReviews::GTMS];
        $result->notGTMCount = $reviews[FindReviews::NOT_GTMS];
        $result->comments = $reviews[FindReviews::COMMENTS];
        $result->CIStatus = $ciStatus;
        $result->isMerged = $isMerged;
        $result->isClosed = $isClosed;

        return $result;
    }

    private function title(array $PRDetails): string
    {
        return $PRDetails['title'];
    }

    private function authorIdentifier(array $PRDetails): string
    {
        return $PRDetails['user']['login'];
    }
}
