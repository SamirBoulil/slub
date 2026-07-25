<?php

declare(strict_types=1);

namespace Tests\Acceptance\Context;

use PHPUnit\Framework\Assert;
use Slub\Application\UnpublishDocument\UnpublishDocument;
use Slub\Application\UnpublishDocument\UnpublishDocumentHandler;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\Document\Document;
use Slub\Domain\Entity\Document\DocumentIdentifier;
use Slub\Domain\Entity\Document\DocumentURL;
use Slub\Domain\Entity\PR\AuthorIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;
use Slub\Domain\Repository\DocumentRepositoryInterface;
use Slub\Domain\Repository\PRRepositoryInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class UnpublishDocumentContext extends FeatureContext
{
    private const DOCUMENT_URL_MISTAKENLY_TO_REVIEW = 'https://www.notion.so/xxx/my-super-doc';

    public function __construct(
        PRRepositoryInterface $PRRepository,
        private DocumentRepositoryInterface $documentRepository,
        private UnpublishDocumentHandler $unpublishDocumentHandler,
    ) {
        parent::__construct($PRRepository);
    }

    /**
     * @BeforeScenario
     */
    public function cleanDocuments(): void
    {
        $this->documentRepository->reset();
    }

    /**
     * @Given /^a document has been put to review by mistake$/
     */
    public function aDocumentHasBeenPutToReviewByMistake(): void
    {
        $url = new DocumentURL(self::DOCUMENT_URL_MISTAKENLY_TO_REVIEW);
        $this->documentRepository->save(
            Document::create(
                DocumentIdentifier::fromURL($url),
                $url,
                ChannelIdentifier::fromString('squad-raccoons'),
                WorkspaceIdentifier::fromString('akeneo'),
                MessageIdentifier::fromString('akeneo@squad-raccoons@1111.2222'),
                AuthorIdentifier::fromString('sam'),
            )
        );
    }

    /**
     * @Given /^a document not in review$/
     */
    public function aDocumentNotInReview(): void
    {
        // No document is put to review.
    }

    /**
     * @When /^an author unpublishes the document$/
     */
    public function anAuthorUnpublishesTheDocument(): void
    {
        $this->unpublishDocumentHandler->handle(new UnpublishDocument(self::DOCUMENT_URL_MISTAKENLY_TO_REVIEW));
    }

    /**
     * @Then /^the document is unpublished$/
     */
    public function theDocumentIsUnpublished(): void
    {
        Assert::assertEmpty($this->documentRepository->all());
    }
}
