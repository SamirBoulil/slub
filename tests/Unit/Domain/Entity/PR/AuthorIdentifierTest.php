<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\PR;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\AuthorIdentifier;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class AuthorIdentifierTest extends TestCase
{
    /**
     * @test
     */
    public function it_is_created_from_string_and_normalizes_itself()
    {
        $AuthorIdentifier = 'Add new feature';
        self::assertEquals(AuthorIdentifier::fromString($AuthorIdentifier)->stringValue(), $AuthorIdentifier);
    }

    /**
     * @test
     */
    public function it_cannot_be_created_from_an_empty_string()
    {
        $this->expectException(\InvalidArgumentException::class);
        AuthorIdentifier::fromString('');
    }
}
