<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\PR;

use Webmozart\Assert\Assert;

class MessageIdentifier
{
    /** @var string */
    private $messageId;

    private function __construct(string $messageId)
    {
        Assert::notEmpty($messageId);
        $this->messageId = $messageId;
    }

    public static function create(string $messageId): self
    {
        return new self($messageId);
    }

    public static function fromString(string $messageId): self
    {
        return new self($messageId);
    }

    public function stringValue(): string
    {
        return $this->messageId;
    }

    public function equals(MessageIdentifier $messageId): bool
    {
        return $messageId->messageId === $this->messageId;
    }
}
