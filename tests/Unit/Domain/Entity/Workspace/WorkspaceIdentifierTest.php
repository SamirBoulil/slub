<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\Workspace;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;

class WorkspaceIdentifierTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_an_identifier_from_its_string_value()
    {
        $identifier = WorkspaceIdentifier::fromString('akeneo');
        self::assertEquals('akeneo', $identifier->stringValue());
    }

    /**
     * @test
     */
    public function it_tells_if_it_is_equal_to_another_identifier()
    {
        $identifier = WorkspaceIdentifier::fromString('akeneo');
        $anotherIdentifier = WorkspaceIdentifier::fromString('unknown/unknown/unknown');
        self::assertTrue($identifier->equals($identifier));
        self::assertFalse($identifier->equals($anotherIdentifier));
    }

    /**
     * @test
     */
    public function it_cannot_be_created_out_of_an_empty_string()
    {
        $this->expectException(\InvalidArgumentException::class);
        WorkspaceIdentifier::fromString('');
    }
}
