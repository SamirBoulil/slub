<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\Document;

use Webmozart\Assert\Assert;

class DocumentIdentifier
{
    private function __construct(private string $documentIdentifier)
    {
        Assert::notEmpty($documentIdentifier);
    }

    public static function create(string $documentIdentifier): self
    {
        return new self($documentIdentifier);
    }

    public static function fromString(string $documentIdentifier): self
    {
        return new self($documentIdentifier);
    }

    public static function fromURL(DocumentURL $url): self
    {
        return new self(md5($url->asString()));
    }

    public function stringValue(): string
    {
        return $this->documentIdentifier;
    }

    public function equals(DocumentIdentifier $identifier): bool
    {
        return $identifier->documentIdentifier === $this->documentIdentifier;
    }
}
