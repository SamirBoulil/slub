<?php

declare(strict_types=1);

namespace Slub\Domain\Query;

use Slub\Infrastructure\VCS\Github\Query\CIStatus\CheckStatus;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class PRInfo
{
    public string $PRIdentifier;

    public string $authorIdentifier;

    public string $title;

    public int $GTMCount;

    public int $comments;

    public int $notGTMCount;

    public CheckStatus $CIStatus;

    public bool $isMerged;

    public bool $isClosed;

    public int $additions;

    public int $deletions;
}
