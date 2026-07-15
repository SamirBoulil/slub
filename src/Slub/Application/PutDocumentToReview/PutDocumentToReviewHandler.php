<?php

declare(strict_types=1);

namespace Slub\Application\PutDocumentToReview;

use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\Document\Document;
use Slub\Domain\Entity\Document\DocumentIdentifier;
use Slub\Domain\Entity\Document\DocumentURL;
use Slub\Domain\Entity\PR\AuthorIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;
use Slub\Domain\Repository\DocumentRepositoryInterface;

final readonly class PutDocumentToReviewHandler
{
    public function __construct(
        private DocumentRepositoryInterface $documentRepository,
    ) {
    }

    public function handle(PutDocumentToReview $command): void
    {
        $url = new DocumentURL($command->documentURL);

        $document = Document::create(
            DocumentIdentifier::fromURL($url),
            $url,
            ChannelIdentifier::fromString($command->channelIdentifier),
            WorkspaceIdentifier::fromString($command->workspaceIdentifier),
            MessageIdentifier::fromString($command->messageIdentifier),
            AuthorIdentifier::fromString($command->slackUserId),
        );

        $this->documentRepository->save($document);
    }
}
