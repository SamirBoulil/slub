<?php

declare(strict_types=1);

namespace Slub\Application\PutPRToReview;

use ConvenientImmutability\Immutable;

class PutPRToReview
{
    use Immutable;

    /** @var string */
    public $organization;

    /** @var string */
    public $repository;

    /** @var string */
    public $pullRequest;

    public function __construct(string $organization, string $repository, string $pullRequest)
    {
        $this->organization = $organization;
        $this->repository = $repository;
        $this->pullRequest = $pullRequest;
    }
}
