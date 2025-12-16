<?php

declare(strict_types=1);

namespace Slub\Application\PutDocumentToReview;

final readonly class PutDocumentToReview
{
    public function __construct(
        public string $documentURL,
        public string $channelIdentifier,
        public string $workspaceIdentifier,
        public string $slackUserId,
    ) {
    }
}
