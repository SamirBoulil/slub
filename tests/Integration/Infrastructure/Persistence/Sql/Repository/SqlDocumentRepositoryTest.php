<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\Sql\Repository;

use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\Document\Document;
use Slub\Domain\Entity\Document\DocumentIdentifier;
use Slub\Domain\Entity\Document\DocumentURL;
use Slub\Domain\Entity\PR\AuthorIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlDocumentRepository;
use Tests\Integration\Infrastructure\KernelTestCase;

class SqlDocumentRepositoryTest extends KernelTestCase
{
    private SqlDocumentRepository $sqlDocumentRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->sqlDocumentRepository = $this->get('slub.infrastructure.persistence.document_repository');
        $this->sqlDocumentRepository->reset();
    }

    /**
     * @test
     */
    public function it_saves_a_document_and_returns_it(): void
    {
        $savedDocument = $this->createDocument('https://www.notion.so/xxx/my-doc', 'akeneo@squad-raccoons@1111.2222');

        $this->sqlDocumentRepository->save($savedDocument);
        $documents = $this->sqlDocumentRepository->all();

        self::assertCount(1, $documents);
        self::assertSame($savedDocument->normalize(), current($documents)->normalize());
    }

    /**
     * @test
     */
    public function it_upserts_a_document_saved_twice_with_the_same_url(): void
    {
        $firstDocument = $this->createDocument('https://www.notion.so/xxx/my-doc', 'akeneo@squad-raccoons@1111.2222');
        $secondDocument = $this->createDocument('https://www.notion.so/xxx/my-doc', 'akeneo@general@3333.4444');

        $this->sqlDocumentRepository->save($firstDocument);
        $this->sqlDocumentRepository->save($secondDocument);
        $documents = $this->sqlDocumentRepository->all();

        self::assertCount(1, $documents);
        self::assertSame($secondDocument->normalize(), current($documents)->normalize());
    }

    /**
     * @test
     */
    public function it_deletes_a_document_that_has_been_published(): void
    {
        $documentToDelete = $this->createDocument('https://www.notion.so/xxx/my-doc', 'akeneo@squad-raccoons@1111.2222');
        $documentToKeep = $this->createDocument('https://www.notion.so/xxx/my-doc-2', 'akeneo@squad-raccoons@3333.4444');
        $this->sqlDocumentRepository->save($documentToDelete);
        $this->sqlDocumentRepository->save($documentToKeep);

        $this->sqlDocumentRepository->unpublishDocument(
            DocumentIdentifier::fromURL(new DocumentURL('https://www.notion.so/xxx/my-doc'))
        );
        $documents = $this->sqlDocumentRepository->all();

        self::assertCount(1, $documents);
        self::assertSame($documentToKeep->normalize(), current($documents)->normalize());
    }

    /**
     * @test
     */
    public function it_does_nothing_when_deleting_a_document_that_was_never_put_to_review(): void
    {
        $this->sqlDocumentRepository->unpublishDocument(
            DocumentIdentifier::fromURL(new DocumentURL('https://www.notion.so/xxx/unknown-doc'))
        );

        self::assertEmpty($this->sqlDocumentRepository->all());
    }

    /**
     * @test
     */
    public function it_resets_itself(): void
    {
        $this->sqlDocumentRepository->save(
            $this->createDocument('https://www.notion.so/xxx/my-doc', 'akeneo@squad-raccoons@1111.2222')
        );

        $this->sqlDocumentRepository->reset();

        self::assertEmpty($this->sqlDocumentRepository->all());
    }

    private function createDocument(string $url, string $messageIdentifier): Document
    {
        $documentURL = new DocumentURL($url);

        return Document::create(
            DocumentIdentifier::fromURL($documentURL),
            $documentURL,
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            MessageIdentifier::fromString($messageIdentifier),
            AuthorIdentifier::fromString('sam'),
        );
    }
}
