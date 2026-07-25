<?php

declare(strict_types=1);

namespace Slub\Application\UnpublishDocument;

use Slub\Domain\Entity\Document\DocumentIdentifier;
use Slub\Domain\Entity\Document\DocumentURL;
use Slub\Domain\Repository\DocumentRepositoryInterface;

final readonly class UnpublishDocumentHandler
{
    public function __construct(private DocumentRepositoryInterface $documentRepository)
    {
    }

    public function handle(UnpublishDocument $command): void
    {
        $documentIdentifier = DocumentIdentifier::fromURL(new DocumentURL($command->documentURL));
        $this->documentRepository->unpublishDocument($documentIdentifier);
    }
}
