<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\PR;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\MessageIdentifier;

class MessageIdentifierTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_an_message_id()
    {
        $identifier = MessageIdentifier::create('1');
        $this->assertEquals('1', $identifier->stringValue());
    }

    /**
     * @test
     */
    public function it_creates_a_message_id_from_string()
    {
        $identifier = MessageIdentifier::fromString('1');
        $this->assertEquals('1', $identifier->stringValue());
    }

    /**
     * @test
     */
    public function it_creates_a_message_id_from_its_string_value()
    {
        $identifier = MessageIdentifier::fromString('1');
        $this->assertEquals('1', $identifier->stringValue());
    }

    /**
     * @test
     */
    public function it_tells_if_it_is_equal_to_another_message_id()
    {
        $identifier = MessageIdentifier::fromString('1');
        $anotherIdentifier = MessageIdentifier::fromString('19');
        $this->assertTrue($identifier->equals($identifier));
        $this->assertFalse($identifier->equals($anotherIdentifier));
    }

    /**
     * @test
     */
    public function it_cannot_be_created_out_of_an_empty_string()
    {
        $this->expectException(\InvalidArgumentException::class);
        MessageIdentifier::fromString('');
    }
}
