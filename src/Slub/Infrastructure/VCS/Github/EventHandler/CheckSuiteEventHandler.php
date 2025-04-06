<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

use Slub\Application\CIStatusUpdate\CIStatusUpdate;
use Slub\Application\CIStatusUpdate\CIStatusUpdateHandler;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetPRInfoInterface;
use Slub\Domain\Query\IsPRInReview;
use Slub\Domain\Query\PRInfo;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CIStatus;
use Slub\Infrastructure\VCS\Github\Query\GetCIStatus;
use Webmozart\Assert\Assert;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class CheckSuiteEventHandler implements EventHandlerInterface
{
    private const CHECK_SUITE_EVENT_TYPE = 'check_suite';

    public function __construct(
        private CIStatusUpdateHandler $CIStatusUpdateHandler,
        private GetCIStatus $getCIStatus,
        private IsPRInReview $IsPRInReview
    ) {
    }

    public function supports(string $eventType, array $eventPayload): bool
    {
        return self::CHECK_SUITE_EVENT_TYPE === $eventType;
    }

    public function handle(array $checkSuiteEvent): void
    {
        // TODO: This is wierd, if there is a checksuite. There should be a pull request.
        if ($this->noPullRequestLinked($checkSuiteEvent)) {
            return;
        }
        $PRIdentifier = $this->getPRIdentifier($checkSuiteEvent);
        if ($this->PRIsNotAlreadyInReview($PRIdentifier)) {
            return;
        }

        $CIStatus = $this->getCIStatusFromGithub($PRIdentifier, $checkSuiteEvent);
        $command = new CIStatusUpdate();
        $command->PRIdentifier = $PRIdentifier->stringValue();
        $command->repositoryIdentifier = $checkSuiteEvent['repository']['full_name'];
        $command->status = $CIStatus->status;
        $command->buildLink = $CIStatus->buildLink;
        $this->CIStatusUpdateHandler->handle($command);
    }

    private function noPullRequestLinked(array $checkSuiteEvent): bool
    {
        return empty($checkSuiteEvent['check_suite']['pull_requests']);
    }

    private function getPRIdentifier(array $checkSuiteEvent): PRIdentifier
    {
        $pullRequests = $checkSuiteEvent['check_suite']['pull_requests'];
        Assert::notEmpty($pullRequests, 'Check suite: Expected to have at least one pull request, didn\'t find any.');

        return PRIdentifier::fromString(
            sprintf(
                '%s/%s',
                $checkSuiteEvent['repository']['full_name'],
                $pullRequests[0]['number']
            )
        );
    }

    private function getCIStatusFromGithub(PRIdentifier $PRIdentifier, array $checkSuiteEvent): CIStatus
    {
        return $this->getCIStatus->fetch($PRIdentifier, $checkSuiteEvent['check_suite']['head_sha']);
    }

    /**
     * This check is done in the application layer as it should.
     * But since, deducting a PR CI status requires to fetch all the information from a PR and
     * performing multiple API and complicated calls to the github API.
     *
     * We'll save ourselves the hassle of by checking in the infra layer if the PR is already
     * in review before going further in the CI status update.
     */
    private function PRIsNotAlreadyInReview(PRIdentifier $PRIdentifier): bool
    {
        return !$this->IsPRInReview->fetch($PRIdentifier);
    }
}
