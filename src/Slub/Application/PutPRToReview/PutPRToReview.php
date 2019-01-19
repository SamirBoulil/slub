<?php

declare(strict_types=1);

namespace Slub\Application\PutPRToReview;

use ConvenientImmutability\Immutable;

class PutPRToReview
{
    use Immutable;

    /** @var string */
    public $repository;

    /** @var string */
    public $prIdentifier;

    /** @var string  */
    public $channel;

    public function __construct(string $channel, string $repository, string $pullRequest)
    {
        $this->repository = $repository;
        $this->prIdentifier = $pullRequest;
        $this->channel = $channel;
    }
}
