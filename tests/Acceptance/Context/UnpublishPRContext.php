<?php

declare(strict_types=1);

namespace Tests\Acceptance\Context;

use PHPUnit\Framework\Assert;
use Slub\Application\UnpublishPR\UnpublishPR;
use Slub\Application\UnpublishPR\UnpublishPRHandler;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\AuthorIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\PR\Title;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;
use Slub\Domain\Repository\PRNotFoundException;
use Slub\Domain\Repository\PRRepositoryInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class UnpublishPRContext extends FeatureContext
{
    private const PR_IDENTIFIER_MISTAKENLY_TO_REVIEW = 'akeneo/pim-community-dev/1010';

    public function __construct(PRRepositoryInterface $PRRepository, private UnpublishPRHandler $unpublishPRHandler)
    {
        parent::__construct($PRRepository);
        $this->PRRepository = $PRRepository;
    }

    /**
     * @Given /^a PR has been put to review by mistake$/
     */
    public function aPRHasBeenPutToReviewByMistake(): void
    {
        $this->PRRepository->save(
            PR::create(
                PRIdentifier::create(self::PR_IDENTIFIER_MISTAKENLY_TO_REVIEW),
                ChannelIdentifier::fromString('squad-raccoons'),
                WorkspaceIdentifier::fromString('akeneo'),
                MessageIdentifier::fromString('CHANNEL_ID@1'),
                AuthorIdentifier::fromString('sam'),
                Title::fromString('Add new feature')
            )
        );
    }

    /**
     * @When /^an author unpublishes a PR$/
     */
    public function anAuthorUnpublishesAPR(): void
    {
        $unpublishPRCommand = new UnpublishPR();
        $unpublishPRCommand->PRIdentifier = self::PR_IDENTIFIER_MISTAKENLY_TO_REVIEW;
        $this->unpublishPRHandler->handle($unpublishPRCommand);
    }

    /**
     * @Then /^the PR is is unpublished$/
     */
    public function thePRIsIsRemoved(): void
    {
        $isFound = true;
        try {
            $this->PRRepository->getBy(PRIdentifier::fromString(self::PR_IDENTIFIER_MISTAKENLY_TO_REVIEW));
        } catch (PRNotFoundException) {
            $isFound = false;
        }

        Assert::assertFalse($isFound);
    }
}
