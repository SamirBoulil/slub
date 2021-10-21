<?php

declare(strict_types=1);

namespace Tests\Acceptance\Context;

use Behat\Behat\Tester\Exception\PendingException;
use Slub\Application\ChangePRSize\ChangePRSize;
use Slub\Application\ChangePRSize\ChangePRSizeHandler;
use Slub\Application\NewReview\NewReviewHandler;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\AuthorIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\PR\Title;
use Slub\Domain\Entity\Reviewer\ReviewerName;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;
use Slub\Domain\Repository\PRRepositoryInterface;
use Tests\Acceptance\helpers\ChatClientSpy;
use Tests\Acceptance\helpers\EventsSpy;

class PRSizeChangedContext extends FeatureContext
{
    private const PR_IDENTIFIER = 'akeneo/pim-community-dev/1234';

    private ChangePRSizeHandler $changePRSizeHandler;
    private EventsSpy $eventSpy;
    private ChatClientSpy $chatClientSpy;
    private PRIdentifier $currentPRIdentifier;
    private MessageIdentifier $currentMessageIdentifier;
    private PR $currentPR;

    public function __construct(
        PRRepositoryInterface $PRRepository,
        ChangePRSizeHandler $changePRSizeHandler,
        EventsSpy $eventSpy,
        ChatClientSpy $chatClientSpy
    ) {
        parent::__construct($PRRepository);
        $this->changePRSizeHandler = $changePRSizeHandler;
        $this->eventSpy = $eventSpy;
        $this->chatClientSpy = $chatClientSpy;
    }


    /**
     * @When /^the author updates the PR too the point that it becomes too large$/
     */
    public function theAuthorUpdatesThePRTooThePointThatItBecomesTooLarge()
    {
        throw new PendingException();
    }

    private function PRAlreadyTooLarge(): PR
    {
        $PR = PR::create(
            $this->currentPRIdentifier,
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            $this->currentMessageIdentifier,
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        $PR->large();

        return $PR;
    }
}
