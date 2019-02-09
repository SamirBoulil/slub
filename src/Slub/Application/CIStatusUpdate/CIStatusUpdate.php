<?php

declare(strict_types=1);

namespace Slub\Application\CIStatusUpdate;

use ConvenientImmutability\Immutable;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class CIStatusUpdate
{
    use Immutable;

    /** @var string */
    public $repository;

    /** @var string */
    public $PRIdentifier;

    /** @var bool */
    public $isGreen;
}
