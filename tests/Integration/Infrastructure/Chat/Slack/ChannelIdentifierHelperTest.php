<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Slack;

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
        $this->assertEquals('akeneo@general', \Slub\Infrastructure\Chat\Slack\Common\ChannelIdentifierHelper::from('akeneo', 'general'));
    }

    /**
     * @test
     */
    public function it_returns_the_channel_and_ts_out_of_a_normalized_channel_identifier(): void
    {
        $this->assertEquals(['workspace' => 'akeneo', 'channel' => 'general'], \Slub\Infrastructure\Chat\Slack\Common\ChannelIdentifierHelper::split('akeneo@general'));
    }

    /**
     * @test
     */
    public function it_throws_if_the_channel_identifier_is_malformed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        \Slub\Infrastructure\Chat\Slack\Common\ChannelIdentifierHelper::split('channel_identifier_without_separator');
    }
}
