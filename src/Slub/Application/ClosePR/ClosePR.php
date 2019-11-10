<?php

declare(strict_types=1);

namespace Slub\Application\ClosePR;

use ConvenientImmutability\Immutable;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class ClosePR
{
    use Immutable;

    /** @var string */
    public $repositoryIdentifier;

    /** @var string */
    public $PRIdentifier;

    /** @var bool */
    public $isMerged;
}
