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

    /** @var string */
    public $status;

    /** @var string */
    public $buildLink;

    public function __construct(
        string $status,
        string $buildLink = ''
    ) {
        $this->status = $status;
        $this->buildLink = $buildLink;
    }
}
