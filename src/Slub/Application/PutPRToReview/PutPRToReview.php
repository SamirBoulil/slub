<?php

declare(strict_types=1);

namespace Slub\Application\PutPRToReview;

use ConvenientImmutability\Immutable;

class PutPRToReview
{
    use Immutable;

    /** @var string */
    public $repositoryIdentifier;

    /** @var string */
    public $PRIdentifier;

    /** @var string */
    public $channelIdentifier;

    /** @var string */
    public $workspaceIdentifier;

    /** @var string */
    public $messageIdentifier;

    /** @var string */
    public $authorIdentifier;

    /** @var string */
    public $title;

    /** @var int */
    public $GTMCount;

    /** @var int */
    public $comments;

    /** @var int */
    public $notGTMCount;

    /** @var string */
    public $CIStatus;

    /** @var bool */
    public $isMerged;

    /** @var bool */
    public $isClosed;
}
