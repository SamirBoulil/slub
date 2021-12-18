<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Slack\Common;

use PHPUnit\Framework\TestCase;
use Slub\Infrastructure\Chat\Slack\Common\ChannelIdentifierHelper;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class ChannelIdentifierHelperTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_a_string_out_of_a_workspace_and_channel(): void
    {
        $this->assertEquals('akeneo@general', ChannelIdentifierHelper::from('akeneo', 'general'));
    }

    /**
     * @test
     */
    public function it_returns_the_channel_and_ts_out_of_a_normalized_channel_identifier(): void
    {
        $this->assertEquals(['workspace' => 'akeneo', 'channel' => 'general'], ChannelIdentifierHelper::split('akeneo@general'));
    }

    /**
     * @test
     * @dataProvider invalidChannelIdentifier
     */
    public function it_throws_if_the_channel_identifier_is_malformed(string $invalidChannelIdentifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ChannelIdentifierHelper::split($invalidChannelIdentifier);
    }

    public function invalidChannelIdentifier(): array
    {
        return [['channel_identifier_without_separator'], ['channel_with_separator_but_missing_info@']];
    }
}
