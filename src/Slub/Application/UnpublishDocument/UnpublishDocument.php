<?php

declare(strict_types=1);

namespace Slub\Application\UnpublishDocument;

final readonly class UnpublishDocument
{
    public function __construct(
        public string $documentURL,
    ) {
    }
}
