<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\Channel;

use Webmozart\Assert\Assert;

class ChannelIdentifier
{
    /** @var string */
    private $channelIdentifier;

    private function __construct(string $channelIdentifier)
    {
        Assert::notEmpty($channelIdentifier);
        $this->channelIdentifier = $channelIdentifier;
    }

    public static function fromString(string $channelIdentifier): self
    {
        return new self($channelIdentifier);
    }

    public function stringValue(): string
    {
        return $this->channelIdentifier;
    }

    public function equals(ChannelIdentifier $identifier): bool
    {
        return $identifier->channelIdentifier === $this->channelIdentifier;
    }
}
