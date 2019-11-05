<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\PR;

use Webmozart\Assert\Assert;

/**
 * A build link is link to a failing CI build.
 *
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class BuildLink
{
    /** @var string */
    private $buildLink;

    private function __construct(string $buildLink)
    {
        $this->buildLink = $buildLink;
    }

    public static function fromURL(string $buildLink): self
    {
        Assert::stringNotEmpty($buildLink);
        Assert::contains($buildLink, 'http');
        return new self($buildLink);
    }

    public static function none(): self
    {
        return new self('');
    }

    public static function fromNormalized(?string $normalizedBuildLink): self
    {
        return new self($normalizedBuildLink ?? '');
    }

    public function stringValue(): string
    {
        return $this->buildLink;
    }

    public function equals(BuildLink $otherBuildLink): bool
    {
        return $this->buildLink === $otherBuildLink->buildLink;
    }
}
