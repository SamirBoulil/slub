<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\InMemory;

use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetPRInfoInterface;
use Slub\Domain\Query\PRInfo;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CheckStatus;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 * // TODO: Should be a stub for sure.
 */
class InMemoryGetPRInfo implements GetPRInfoInterface
{
    private PRInfo $PRInfo;

    public function __construct()
    {
        $this->PRInfo = new PRInfo();
        $this->PRInfo->repositoryIdentifier = 'slub';
        $this->PRInfo->authorIdentifier = 'sam';
        $this->PRInfo->authorImageUrl = 'https://author_image_url';
        $this->PRInfo->title = 'Add new feature';
        $this->PRInfo->description = 'Amazing description';
        $this->PRInfo->CIStatus = CheckStatus::green();
        $this->PRInfo->comments = 0;
        $this->PRInfo->GTMCount = 0;
        $this->PRInfo->notGTMCount = 0;
        $this->PRInfo->isMerged = false;
        $this->PRInfo->isClosed = false;
        $this->PRInfo->additions = 10;
        $this->PRInfo->deletions = 10;
    }

    public function fetch(PRIdentifier $PRIdentifier): PRInfo
    {
        $this->PRInfo->PRIdentifier = $PRIdentifier->stringValue();

        return $this->PRInfo;
    }
}
