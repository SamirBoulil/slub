<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\Reviewer;

use Webmozart\Assert\Assert;

class ReviewerName
{
    /** @var string */
    private $name;

    private function __construct(string $name)
    {
        Assert::stringNotEmpty($name);
        $this->name = $name;
    }

    public static function fromString(string $name): self
    {
        return new self($name);
    }

    public function stringValue(): string
    {
        return $this->name;
    }
}
