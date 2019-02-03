<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\PR;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\PRIdentifier;

class PRIdentifierTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_an_identifier()
    {
        $identifier = PRIdentifier::create('akeneo/pim-community-dev/1111');
        $this->assertEquals('akeneo/pim-community-dev/1111', $identifier->stringValue());
    }

    /**
     * @test
     */
    public function it_creates_an_identifier_from_string()
    {
        $identifier = PRIdentifier::fromString('akeneo/pim-community-dev/1111');
        $this->assertEquals('akeneo/pim-community-dev/1111', $identifier->stringValue());
    }

    /**
     * @test
     */
    public function it_creates_an_identifier_from_its_string_value()
    {
        $identifier = PRIdentifier::fromString('akeneo/pim-community-dev/1111');
        $this->assertEquals('akeneo/pim-community-dev/1111', $identifier->stringValue());
    }

    /**
     * @test
     */
    public function it_tells_if_it_is_equal_to_another_identifier()
    {
        $identifier = PRIdentifier::fromString('akeneo/pim-community-dev/1111');
        $anotherIdentifier = PRIdentifier::fromString('unknown/unknown/unknown');
        $this->assertTrue($identifier->equals($identifier));
        $this->assertFalse($identifier->equals($anotherIdentifier));
    }

    /**
     * @test
     */
    public function it_cannot_be_created_out_of_an_empty_string()
    {
        $this->expectException(\InvalidArgumentException::class);
        PRIdentifier::fromString('');
    }
}
