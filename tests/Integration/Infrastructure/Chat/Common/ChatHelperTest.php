<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Common;

use PHPUnit\Framework\TestCase;
use Slub\Infrastructure\Chat\Common\ChatHelper;
use Slub\Infrastructure\Chat\Slack\Common\ImpossibleToParseRepositoryURL;

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

    public function test_it_extracts_PR_identifiers_from_url()
    {
        $this->assertEquals('SamirBoulil/slub/1234', ChatHelper::extractPRIdentifier('https://github.com/SamirBoulil/slub/pull/1234')->stringValue());
        $this->assertEquals('SamirBoulil/slub/1234', ChatHelper::extractPRIdentifier('  https://github.com/SamirBoulil/slub/pull/1234  ')->stringValue());
    }

    public function test_it_throws_if_the_pr_identifier_cannot_be_extracted()
    {
        $this->expectException(ImpossibleToParseRepositoryURL::class);
        ChatHelper::extractPRIdentifier('pada yala yada yada');
    }
}
