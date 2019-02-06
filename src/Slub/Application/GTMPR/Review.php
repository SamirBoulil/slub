<?php

declare(strict_types=1);

namespace Slub\Application\GTMPR;

use ConvenientImmutability\Immutable;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 */
class Review
{
    use Immutable;

    /** @var string */
    public $repositoryIdentifier;

    /** @var string */
    public $PRIdentifier;

    /** @var bool */
    public $isGTM;
}
