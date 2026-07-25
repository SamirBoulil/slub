<?php

declare(strict_types=1);

namespace Slub\Domain\Repository;

use Slub\Domain\Entity\Document\Document;
use Slub\Domain\Entity\Document\DocumentIdentifier;

interface DocumentRepositoryInterface
{
    public function save(Document $document): void;

    /**
     * @return Document[]
     */
    public function all(): array;

    public function unpublishDocument(DocumentIdentifier $documentIdentifier): void;

    public function reset(): void;
}
