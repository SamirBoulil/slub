<?php

declare(strict_types=1);

namespace Slub\Domain\Query;

use Slub\Infrastructure\VCS\Github\Query\CIStatus\CheckStatus;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class VCSStatus
{
    /** @var string */
    public $PRIdentifier;

    /** @var int */
    public $GTMCount;

    /** @var int */
    public $comments;

    /** @var int */
    public $notGTMCount;

    /** @var CheckStatus */
    public $checkStatus;

    /** @var bool */
    public $isMerged;
}
