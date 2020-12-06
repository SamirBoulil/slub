<?php

declare(strict_types=1);

namespace Slub\Domain\Query;

use Slub\Infrastructure\VCS\Github\Query\CIStatus\CheckStatus;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class VCSStatus
{
    public string $PRIdentifier;

    public int $GTMCount;

    public int $comments;

    public int $notGTMCount;

    public CheckStatus $checkStatus;

    public bool $isMerged;
}
