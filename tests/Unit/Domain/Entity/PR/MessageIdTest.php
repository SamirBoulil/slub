<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\PR;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\MessageId;

class MessageIdTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_an_message_id()
    {
        $identifier = MessageId::create('1');
        $this->assertEquals('1', $identifier->stringValue());
    }

    /**
     * @test
     */
    public function it_creates_a_message_id_from_string()
    {
        $identifier = MessageId::fromString('1');
        $this->assertEquals('1', $identifier->stringValue());
    }

    /**
     * @test
     */
    public function it_creates_a_message_id_from_its_string_value()
    {
        $identifier = MessageId::fromString('1');
        $this->assertEquals('1', $identifier->stringValue());
    }

    /**
     * @test
     */
    public function it_tells_if_it_is_equal_to_another_message_id()
    {
        $identifier = MessageId::fromString('1');
        $anotherIdentifier = MessageId::fromString('19');
        $this->assertTrue($identifier->equals($identifier));
        $this->assertFalse($identifier->equals($anotherIdentifier));
    }

    /**
     * @test
     */
    public function it_cannot_be_created_out_of_an_empty_string()
    {
        $this->expectException(\InvalidArgumentException::class);
        MessageId::fromString('');
    }
}
