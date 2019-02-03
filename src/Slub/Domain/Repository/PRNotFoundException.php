<?php

declare(strict_types=1);

namespace Slub\Domain\Repository;

use Slub\Domain\Entity\PR\PRIdentifier;

class PRNotFoundException extends \Exception
{
    public static function create(PRIdentifier $identifier): self
    {
        $message = sprintf('PR with identifier "%s" not found', $identifier->stringValue());

        return new self($message);
    }
}
