<?php

declare(strict_types=1);

namespace Slub\Application\ChangePRSize;

use ConvenientImmutability\Immutable;

/**
 * @author    Pierrick Martos <pierrick.martos@gmail.com>
 */
class ChangePRSize
{
    use Immutable;

    public string $repositoryIdentifier;

    public string $PRIdentifier;

    public int $additions;

    public int $deletions;
}
