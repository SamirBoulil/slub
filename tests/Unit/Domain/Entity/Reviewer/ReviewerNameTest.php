<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\Reviewer;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\AuthorIdentifier;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class ReviewerNameTest extends TestCase
{
    /**
     * @test
     */
    public function it_is_created_from_string_and_normalizes_itself()
    {
        $reviewerName = 'Samir';
        self::assertEquals(AuthorIdentifier::fromString($reviewerName)->stringValue(), $reviewerName);
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
