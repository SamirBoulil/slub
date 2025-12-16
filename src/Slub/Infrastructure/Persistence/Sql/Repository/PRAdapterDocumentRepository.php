<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\Sql\Repository;

use Slub\Domain\Entity\Document\Document;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\PR\Title;
use Slub\Domain\Repository\DocumentRepositoryInterface;
use Slub\Domain\Repository\PRRepositoryInterface;

final readonly class PRAdapterDocumentRepository implements DocumentRepositoryInterface
{
    public function __construct(
        private PRRepositoryInterface $PRRepository,
    ) {
    }

    public function save(Document $document): void
    {
        $prIdentifier = PRIdentifier::create($document->url->asString());
        $messageIdentifier = MessageIdentifier::create('document-'.md5($document->url->asString()));
        $title = Title::fromString('Document: '.$document->url->asString());

        $pr = PR::create(
            $prIdentifier,
            $document->channelIdentifier,
            $document->workspaceIdentifier,
            $messageIdentifier,
            $document->authorIdentifier,
            $title,
            0,
            0,
            0,
            'PENDING',
            false,
            false,
        );

        $this->PRRepository->save($pr);
    }
}
