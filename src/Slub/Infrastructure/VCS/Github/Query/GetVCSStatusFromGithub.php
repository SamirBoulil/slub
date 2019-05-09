<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query;

use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetVCSStatus;
use Slub\Domain\Query\VCSStatus;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class GetVCSStatusFromGithub implements GetVCSStatus
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

    public function fetch(PRIdentifier $PRIdentifier): VCSStatus
    {
        $PRDetails = $this->getPRDetails->fetch($PRIdentifier);
        $reviews = $this->findReviews->fetch($PRIdentifier);
        $ciStatus = $this->getCIStatus->fetch($PRIdentifier, $this->getPRCommitRef($PRDetails));
        $isMerged = $this->isMerged($PRDetails);
        $result = $this->createVCSStatus($PRIdentifier, $reviews, $ciStatus, $isMerged);

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

    private function createVCSStatus(
        PRIdentifier $PRIdentifier,
        array $reviews,
        string $ciStatus,
        bool $isMerged
    ): VCSStatus {
        $result = new VCSStatus();
        $result->PRIdentifier = $PRIdentifier->stringValue();
        $result->GTMCount = $reviews[FindReviews::GTMS];
        $result->notGTMCount = $reviews[FindReviews::NOT_GTMS];
        $result->comments = $reviews[FindReviews::COMMENTS];
        $result->CIStatus = $ciStatus;
        $result->isMerged = $isMerged;

        return $result;
    }
}