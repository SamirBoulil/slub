<?php

declare(strict_types=1);

namespace Slub\Domain\Query;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
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

    /** @var string */
    public $CIStatus;

    /** @var bool */
    public $isMerged;
}
