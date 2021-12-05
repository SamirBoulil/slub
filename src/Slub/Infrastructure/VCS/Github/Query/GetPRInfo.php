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
    private GetPRDetails $getPRDetails;

    private FindReviews $findReviews;

    private GetCIStatus $getCIStatus;

    public function __construct(GetPRDetails $getPRDetails, FindReviews $findReviews, GetCIStatus $getCIStatus)
    {
        $this->getPRDetails = $getPRDetails;
        $this->findReviews = $findReviews;
        $this->getCIStatus = $getCIStatus;
    }

    public function fetch(PRIdentifier $PRIdentifier): PRInfo
    {
        $PRDetails = $this->getPRDetails->fetch($PRIdentifier);
        $repositoryIdentifier = $this->repositoryIdentifier($PRIdentifier);
        $reviews = $this->findReviews->fetch($PRIdentifier);
        $ciStatus = $this->getCIStatus->fetch($PRIdentifier, $this->getPRCommitRef($PRDetails));
        $isMerged = $this->isMerged($PRDetails);
        $isClosed = $this->isClosed($PRDetails);
        $authorIdentifier = $this->authorIdentifier($PRDetails);
        $title = $this->title($PRDetails);
        $additions = $this->additions($PRDetails);
        $deletions = $this->deletions($PRDetails);

        return $this->createPRInfo(
            $PRIdentifier,
            $repositoryIdentifier,
            $authorIdentifier,
            $title,
            $reviews,
            $ciStatus,
            $isMerged,
            $isClosed,
            $additions, $deletions
        );
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
        string $repositoryIdentifier,
        string $authorIdentifier,
        string $title,
        array $reviews,
        CheckStatus $ciStatus,
        bool $isMerged,
        bool $isClosed,
        int $additions,
        int $deletions
    ): PRInfo {
        $result = new PRInfo();
        $result->repositoryIdentifier = $repositoryIdentifier;
        $result->PRIdentifier = $PRIdentifier->stringValue();
        $result->authorIdentifier = $authorIdentifier;
        $result->title = $title;
        $result->GTMCount = $reviews[FindReviews::GTMS];
        $result->notGTMCount = $reviews[FindReviews::NOT_GTMS];
        $result->comments = $reviews[FindReviews::COMMENTS];
        $result->CIStatus = $ciStatus;
        $result->isMerged = $isMerged;
        $result->isClosed = $isClosed;
        $result->additions = $additions;
        $result->deletions = $deletions;

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

    private function additions(array $PRDetails): int
    {
        return $PRDetails['additions'];
    }

    private function deletions(array $PRDetails): int
    {
        return $PRDetails['deletions'];
    }

    private function repositoryIdentifier(PRIdentifier $PRIdentifier): string
    {
        $breakout = GithubAPIHelper::breakoutPRIdentifier($PRIdentifier);

        return sprintf('%s/%s', $breakout[0], $breakout[1]);
    }
}
