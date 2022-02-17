<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query\CIStatus;

use ConvenientImmutability\Immutable;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class CheckStatus
{
    use Immutable;

    // introduce green / red(build link) / pending constructors ?
    public function __construct(public string $status, public string $buildLink = '')
    {
    }
}
