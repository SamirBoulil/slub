<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\Document;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\Document\Document;
use Slub\Domain\Entity\Document\DocumentIdentifier;
use Slub\Domain\Entity\Document\DocumentURL;
use Slub\Domain\Entity\PR\AuthorIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class DocumentTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_a_document_and_normalizes_itself(): void
    {
        $url = new DocumentURL('https://www.notion.so/xxx/my-doc');

        $document = Document::create(
            DocumentIdentifier::fromURL($url),
            $url,
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            MessageIdentifier::fromString('akeneo@squad-raccoons@1111.2222'),
            AuthorIdentifier::fromString('sam'),
        );
        $normalizedDocument = $document->normalize();

        self::assertEquals(md5('https://www.notion.so/xxx/my-doc'), $normalizedDocument['IDENTIFIER']);
        self::assertEquals('https://www.notion.so/xxx/my-doc', $normalizedDocument['URL']);
        self::assertEquals('sam', $normalizedDocument['AUTHOR_IDENTIFIER']);
        self::assertEquals(['squad-raccoons'], $normalizedDocument['CHANNEL_IDS']);
        self::assertEquals(['akeneo'], $normalizedDocument['WORKSPACE_IDS']);
        self::assertEquals(['akeneo@squad-raccoons@1111.2222'], $normalizedDocument['MESSAGE_IDS']);
        self::assertNotEmpty($normalizedDocument['PUT_TO_REVIEW_AT']);
    }

    /**
     * @test
     */
    public function it_normalizes_itself_back_and_forth(): void
    {
        $normalizedDocument = [
            'IDENTIFIER' => md5('https://www.notion.so/xxx/my-doc'),
            'URL' => 'https://www.notion.so/xxx/my-doc',
            'AUTHOR_IDENTIFIER' => 'sam',
            'CHANNEL_IDS' => ['squad-raccoons'],
            'WORKSPACE_IDS' => ['akeneo'],
            'MESSAGE_IDS' => ['akeneo@squad-raccoons@1111.2222'],
            'PUT_TO_REVIEW_AT' => '1700000000',
        ];

        $document = Document::fromNormalized($normalizedDocument);

        self::assertSame($normalizedDocument, $document->normalize());
    }

    /**
     * @test
     */
    public function it_tells_how_many_days_it_has_been_in_review(): void
    {
        $putToReviewTimestamp = (string) (new \DateTime('now', new \DateTimeZone('UTC')))
            ->modify('-2 day')
            ->getTimestamp();
        $document = Document::fromNormalized([
            'IDENTIFIER' => md5('https://www.notion.so/xxx/my-doc'),
            'URL' => 'https://www.notion.so/xxx/my-doc',
            'AUTHOR_IDENTIFIER' => 'sam',
            'CHANNEL_IDS' => ['squad-raccoons'],
            'WORKSPACE_IDS' => ['akeneo'],
            'MESSAGE_IDS' => ['akeneo@squad-raccoons@1111.2222'],
            'PUT_TO_REVIEW_AT' => $putToReviewTimestamp,
        ]);

        self::assertEquals(2, $document->numberOfDaysInReview());
    }
}
