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

    /** @var string */
    public $repositoryIdentifier;

    /** @var string */
    public $PRIdentifier;

    /** @var string */
    public $status;

    /** @var string|null */
    public $buildLink;
}
