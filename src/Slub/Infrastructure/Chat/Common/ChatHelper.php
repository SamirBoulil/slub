<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Common;

use Slub\Domain\Entity\Document\DocumentURL;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\Chat\Slack\Common\ImpossibleToParseRepositoryURL;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;
use Webmozart\Assert\Assert;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class ChatHelper
{
    public static function elipsisIfTooLong(string $possiblyLongText, int $maxLength): string
    {
        $firstLine = current(explode("\r\n", $possiblyLongText));

        return strlen($firstLine) > $maxLength ?
            sprintf("%s ...", current(explode("\r\n", wordwrap($firstLine, $maxLength, "\r\n"))))
            : $firstLine;
    }

    public static function escapeHtmlChars(string $text): string
    {
        return str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $text);
    }

    public static function extractPRIdentifier(string $text): PRIdentifier
    {
        try {
            $text = trim($text);
            preg_match('#.*(?:https://)?github\.com/(.*)/pull/(\d+).*$#', $text, $matches);
            Assert::stringNotEmpty($matches[1]);
            Assert::stringNotEmpty($matches[2]);
            $repositoryIdentifier = $matches[1];
            $PRNumber = $matches[2];
            $PRIdentifier = GithubAPIHelper::PRIdentifierFrom($repositoryIdentifier, $PRNumber);
        } catch (\Exception) {
            throw new ImpossibleToParseRepositoryURL($text);
        }

        return $PRIdentifier;
    }

    public static function isGithubPR(string $text): bool
    {
        $text = trim($text);

        return 1 === preg_match('#.*https://github.com/(.*)/pull/(\d+).*$#', $text);
    }

    public static function extractURL(string $text): DocumentURL
    {
        $text = trim($text);
        $res = preg_match('#(https?://\S*)#', $text, $matches);
        if (1 !== $res) {
            throw new ImpossibleToParseRepositoryURL($text);
        }

        return new DocumentURL($matches[0]);
    }
}
