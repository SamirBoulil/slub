<?php

declare(strict_types=1);

namespace Slub\Application\PutPRToReview;

use ConvenientImmutability\Immutable;

class PutPRToReview
{
    use Immutable;

    /** @var string */
    public $repositoryIdentifier;

    /** @var string */
    public $PRIdentifier;

    /** @var string */
    public $channelIdentifier;

    public function __construct(string $channelIdentifier, string $repositoryIdentifier, string $PRIdentifier)
    {
        $this->repositoryIdentifier = $repositoryIdentifier;
        $this->PRIdentifier = $PRIdentifier;
        $this->channelIdentifier = $channelIdentifier;
    }
}
