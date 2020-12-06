<?php

declare(strict_types=1);

namespace Slub\Application\CIStatusUpdate;

use ConvenientImmutability\Immutable;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class CIStatusUpdate
{
    use Immutable;

    public string $repositoryIdentifier;

    public string $PRIdentifier;

    public string $status;

    public ?string $buildLink = null;
}
