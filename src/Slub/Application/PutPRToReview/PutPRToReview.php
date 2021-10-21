<?php

declare(strict_types=1);

namespace Slub\Application\PutPRToReview;

use ConvenientImmutability\Immutable;

class PutPRToReview
{
    use Immutable;

    public string $repositoryIdentifier;

    public string $PRIdentifier;

    public string $channelIdentifier;

    public string $workspaceIdentifier;

    public string $messageIdentifier;

    public string $authorIdentifier;

    public string $title;

    public int $GTMCount;

    public int $comments;

    public int $notGTMCount;

    public string $CIStatus;

    public bool $isMerged;

    public bool $isClosed;

    public int $additions;

    public int $deletions;
}
