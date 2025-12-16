<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\Document;

use Webmozart\Assert\Assert;

final readonly class DocumentURL
{
    public function __construct(private string $url)
    {
        Assert::notEmpty($url);
        Assert::regex($url, '/^https?:\/\//');
    }

    public function asString(): string
    {
        return $this->url;
    }
}
