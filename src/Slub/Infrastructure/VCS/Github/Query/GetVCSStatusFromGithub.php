<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetVCSStatus;
use Slub\Domain\Query\VCSStatus;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CheckStatus;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetVCSStatusFromGithub implements GetVCSStatus
{
    /** @var GetPRDetails */
    private $getPRDetails;

    /** @var FindReviews */
    private $findReviews;

    /** @var GetCIStatus */
    private $getCIStatus;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(GetPRDetails $getPRDetails, FindReviews $findReviews, GetCIStatus $getCIStatus, LoggerInterface $logger)
    {
        $this->getPRDetails = $getPRDetails;
        $this->findReviews = $findReviews;
        $this->getCIStatus = $getCIStatus;
        $this->logger = $logger;
    }

    public function fetch(PRIdentifier $PRIdentifier): VCSStatus
    {
        $PRDetails = $this->getPRDetails->fetch($PRIdentifier);
        $this->logger->critical('Fetched PR details.');

        $reviews = $this->findReviews->fetch($PRIdentifier);
        $this->logger->critical('Fetched reviews');

        $ciStatus = $this->getCIStatus->fetch($PRIdentifier, $this->getPRCommitRef($PRDetails));
        $this->logger->critical('Fetched ci status');

        $isMerged = $this->isMerged($PRDetails);
        $this->logger->critical('Fetched is merged');

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
        CheckStatus $ciStatus,
        bool $isMerged
    ): VCSStatus {
        $result = new VCSStatus();
        $result->PRIdentifier = $PRIdentifier->stringValue();
        $result->GTMCount = $reviews[FindReviews::GTMS];
        $result->notGTMCount = $reviews[FindReviews::NOT_GTMS];
        $result->comments = $reviews[FindReviews::COMMENTS];
        $result->checkStatus = $ciStatus;
        $result->isMerged = $isMerged;

        return $result;
    }
}
