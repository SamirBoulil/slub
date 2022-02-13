<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Slack\Common;

use PHPUnit\Framework\TestCase;
use Slub\Infrastructure\Chat\Slack\Common\MessageIdentifierHelper;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class MessageIdentifierHelperTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_a_string_out_of_a_workspace_and_channel_and_ts(): void
    {
        $this->assertEquals('akeneo@general@12345', MessageIdentifierHelper::from('akeneo', 'general', '12345'));
    }

    /**
     * @test
     */
    public function it_returns_the_channel_and_ts_out_of_a_normalized_channel_identifier(): void
    {
        $this->assertEquals(['workspace' => 'akeneo', 'channel' => 'general', 'ts' => '12345'], MessageIdentifierHelper::split('akeneo@general@12345'));
    }
}
