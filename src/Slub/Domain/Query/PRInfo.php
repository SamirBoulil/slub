<?php

declare(strict_types=1);

namespace Slub\Domain\Query;

use Slub\Infrastructure\VCS\Github\Query\CIStatus\CheckStatus;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class PRInfo
{
    /** @var string */
    public $PRIdentifier;

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

    /** @var CheckStatus */
    public $CIStatus;

    /** @var bool */
    public $isMerged;

    /** @var bool */
    public $isClosed;
}
