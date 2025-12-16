<?php
declare(strict_types=1);

namespace Slub\Domain\Entity\Document;

use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\AuthorIdentifier;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;

final readonly class Document
{
    public function __construct(
        public DocumentURL $url,
        public ChannelIdentifier $channelIdentifier,
        public WorkspaceIdentifier $workspaceIdentifier,
        public AuthorIdentifier $authorIdentifier,
    ) {
    }
}
