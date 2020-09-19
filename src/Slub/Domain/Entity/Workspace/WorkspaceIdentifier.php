<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\Workspace;

use Webmozart\Assert\Assert;

class WorkspaceIdentifier
{
    /** @var string */
    private $workspaceIdentifier;

    private function __construct(string $workspaceIdentifier)
    {
        Assert::notEmpty($workspaceIdentifier);
        $this->workspaceIdentifier = $workspaceIdentifier;
    }

    public static function fromString(string $workspaceIdentifier): self
    {
        return new self($workspaceIdentifier);
    }

    public function stringValue(): string
    {
        return $this->workspaceIdentifier;
    }

    public function equals(WorkspaceIdentifier $identifier): bool
    {
        return $identifier->workspaceIdentifier === $this->workspaceIdentifier;
    }
}
