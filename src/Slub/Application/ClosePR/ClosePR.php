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

    public string $repositoryIdentifier;

    public string $PRIdentifier;

    public bool $isMerged;
}
