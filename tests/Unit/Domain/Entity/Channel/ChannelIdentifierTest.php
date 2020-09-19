<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\Channel;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\Channel\ChannelIdentifier;

class ChannelIdentifierTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_an_identifier_from_its_string_value()
    {
        $identifier = ChannelIdentifier::fromString('squad-raccoons');
        self::assertEquals('squad-raccoons', $identifier->stringValue());
    }

    /**
     * @test
     */
    public function it_tells_if_it_is_equal_to_another_identifier()
    {
        $identifier = ChannelIdentifier::fromString('squad-raccoons');
        $anotherIdentifier = ChannelIdentifier::fromString('unknown/unknown/unknown');
        self::assertTrue($identifier->equals($identifier));
        self::assertFalse($identifier->equals($anotherIdentifier));
    }

    /**
     * @test
     */
    public function it_cannot_be_created_out_of_an_empty_string()
    {
        $this->expectException(\InvalidArgumentException::class);
        ChannelIdentifier::fromString('');
    }
}
