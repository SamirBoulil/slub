<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\InMemory;

use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetPRInfoInterface;
use Slub\Domain\Query\PRInfo;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CheckStatus;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class InMemoryGetPRInfo implements GetPRInfoInterface
{
    /** @var PRInfo */
    private $PRInfo;

    public function __construct()
    {
        $this->PRInfo = new PRInfo();
        $this->PRInfo->authorIdentifier = 'sam';
        $this->PRInfo->title = 'Add new feature';
        $this->PRInfo->CIStatus = new CheckStatus('GREEN');
        $this->PRInfo->comments = 0;
        $this->PRInfo->GTMCount = 0;
        $this->PRInfo->notGTMCount = 0;
        $this->PRInfo->isMerged = false;
    }

    public function fetch(PRIdentifier $PRIdentifier): PRInfo
    {
        $this->PRInfo->PRIdentifier = $PRIdentifier->stringValue();

        return $this->PRInfo;
    }
}
