<?php

declare(strict_types=1);

namespace Slub\Application\PutDocumentToReview;

use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\Document\Document;
use Slub\Domain\Entity\Document\DocumentURL;
use Slub\Domain\Entity\PR\AuthorIdentifier;
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
        // @TODO: do a slack call to get the username from the user id
        $username = $command->slackUserId;

        $document = new Document(
            new DocumentURL($command->documentURL),
            ChannelIdentifier::fromString($command->channelIdentifier),
            WorkspaceIdentifier::fromString($command->workspaceIdentifier),
            AuthorIdentifier::fromString($username),
        );

        $this->documentRepository->save($document);
    }
}
