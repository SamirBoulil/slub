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

    public string $repositoryIdentifier;

    public string $PRIdentifier;

    public string $reviewerName;

    public string $reviewStatus;
}
