<?php

declare(strict_types=1);

namespace Slub\Application\WarnLargePR;

use ConvenientImmutability\Immutable;

/**
 * @author    Pierrick Martos <pierrick.martos@gmail.com>
 */
class WarnLargePR
{
    use Immutable;

    public string $repositoryIdentifier;

    public string $PRIdentifier;

    public int $additions = 0;

    public int $deletions = 0;
}
