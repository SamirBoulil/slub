<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\PR;

use Webmozart\Assert\Assert;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class AuthorIdentifier
{
    /** @var string */
    private $title;

    private function __construct(string $title)
    {
        Assert::stringNotEmpty($title);
        $this->title = $title;
    }

    public static function fromString(string $title): self
    {
        return new self($title);
    }

    public function stringValue(): string
    {
        return $this->title;
    }
}
