<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\PR;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\Title;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class TitleTest extends TestCase
{
    /**
     * @test
     */
    public function it_is_created_from_string_and_normalizes_itself()
    {
        $title = 'Add new feature';
        self::assertEquals(Title::fromString($title)->stringValue(), $title);
    }

    /**
     * @test
     */
    public function it_cannot_be_created_from_an_empty_string()
    {
        $this->expectException(\InvalidArgumentException::class);
        Title::fromString('');
    }
}
