<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\PR;

use Webmozart\Assert\Assert;

/**
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class CIStatus
{
    private const BUILD_RESULT_KEY = 'BUILD_RESULT';
    private const BUILD_LINK_KEY = 'BUILD_LINK';

    /** @var BuildResult */
    private $buildResult;

    /** @var BuildLink */
    private $buildLink;

    private function __construct(
        BuildResult $buildResult,
        BuildLink $buildLink
    ) {
        $this->buildResult = $buildResult;
        $this->buildLink = $buildLink;
    }

    public static function endedWith(BuildResult $buildResult, BuildLink $buildLink): self
    {
        return new self($buildResult, $buildLink);
    }

    public static function fromNormalized(array $normalizedCIStatus): self
    {
        Assert::keyExists($normalizedCIStatus, self::BUILD_LINK_KEY);
        Assert::keyExists($normalizedCIStatus, self::BUILD_RESULT_KEY);

        return new self(
            BuildResult::fromNormalized($normalizedCIStatus[self::BUILD_RESULT_KEY]),
            BuildLink::fromNormalized($normalizedCIStatus[self::BUILD_LINK_KEY])
        );
    }

    public function normalize(): array
    {
        return [
            self::BUILD_RESULT_KEY => $this->buildResult->stringValue(),
            self::BUILD_LINK_KEY   => $this->buildLink->stringValue(),
        ];
    }

    public function isGreen(): bool
    {
        return $this->buildResult->isGreen();
    }

    public function isPending(): bool
    {
        return $this->buildResult->isPending();
    }

    public function isRedWithLink(BuildLink $buildLink): bool
    {
        return $this->buildLink->equals($buildLink);
    }
}
