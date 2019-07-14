<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Slack;

use PHPUnit\Framework\TestCase;
use Slub\Infrastructure\Chat\Slack\MessageIdentifierHelper;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class MessageIdentifierHelperTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_a_string_out_of_a_channel_and_ts()
    {
        $this->assertEquals('general@12345', MessageIdentifierHelper::from('general', '12345'));
    }

    /**
     * @test
     */
    public function it_returns_the_channel_and_ts_out_of_a_normalized_channel_identifier()
    {
        $this->assertEquals(['channel' => 'general', 'ts' => '12345'], MessageIdentifierHelper::split('general@12345'));
    }
}
