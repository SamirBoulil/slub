<?php

declare(strict_types=1);

namespace Slub\Domain\Repository;

use Slub\Domain\Entity\Document\Document;

interface DocumentRepositoryInterface
{
    public function save(Document $document): void;
}
