<?php

declare(strict_types=1);

namespace Slub\Application\NewReview;

use ConvenientImmutability\Immutable;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class NewReview
{
    use Immutable;

    /** @var string */
    public $repositoryIdentifier;

    /** @var string */
    public $PRIdentifier;

    /** @var string */
    public $reviewerName;

    /** @var string */
    public $reviewStatus;
}
