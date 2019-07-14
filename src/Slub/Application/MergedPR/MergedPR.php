<?php

declare(strict_types=1);

namespace Slub\Application\MergedPR;

use ConvenientImmutability\Immutable;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class MergedPR
{
    use Immutable;

    /** @var string */
    public $repositoryIdentifier;

    /** @var string */
    public $PRIdentifier;
}
