<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Common;

use PHPUnit\Framework\TestCase;
use Slub\Infrastructure\Chat\Common\ChatHelper;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class ChatHelperTest extends TestCase
{
    public function test_it_does_not_add_elipsis_when_the_text_is_empty()
    {
        $this->assertEmpty(ChatHelper::elipsisIfTooLong('', 1));
    }

    public function test_it_does_not_add_elipsis_when_the_first_line_of_the_text_length_is_equals_to_the_limit()
    {
        $shortFirstLine = "a\r\naaaaaaa";
        $this->assertEquals('a', ChatHelper::elipsisIfTooLong($shortFirstLine, 1));
    }

    public function test_it_adds_elipsis_when_the_first_line_of_the_text_length_exceeds_the_limit()
    {
        $shortFirstLine = "aa\r\naaaaaaa";
        $this->assertEquals('aa ...', ChatHelper::elipsisIfTooLong($shortFirstLine, 1));
    }
}
