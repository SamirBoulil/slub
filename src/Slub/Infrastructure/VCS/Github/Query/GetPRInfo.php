<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query;

use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetPRInfoInterface;
use Slub\Domain\Query\PRInfo;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
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
        $result = $this->createPRInfo($PRIdentifier, $reviews, $ciStatus, $isMerged);

        return $result;
    }

    private function getPRCommitRef(array $PRDetails): string
    {
        return $PRDetails['head']['sha'];
    }

    private function isMerged(array $PRdetails): bool
    {
        return 'closed' === $PRdetails['state'];
    }

    private function createPRInfo(
        PRIdentifier $PRIdentifier,
        array $reviews,
        string $ciStatus,
        bool $isMerged
    ): PRInfo {
        $result = new PRInfo();
        $result->PRIdentifier = $PRIdentifier->stringValue();
        $result->GTMCount = $reviews[FindReviews::GTMS];
        $result->notGTMCount = $reviews[FindReviews::NOT_GTMS];
        $result->comments = $reviews[FindReviews::COMMENTS];
        $result->CIStatus = $ciStatus;
        $result->isMerged = $isMerged;

        return $result;
    }
}
