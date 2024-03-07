<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

use Slub\Application\CIStatusUpdate\CIStatusUpdate;
use Slub\Application\CIStatusUpdate\CIStatusUpdateHandler;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetPRInfoInterface;
use Slub\Domain\Query\IsPRInReview;
use Slub\Domain\Query\PRInfo;
use Webmozart\Assert\Assert;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 *
 * Listening to Check Runs is deactivated as it can lead to Github
 * triggering way too many events and that the platform isn't able to process.
 *  - The request ends up in timeout as their number increase
 *  - The max DB call limit is reached
 */
class CheckRunEventHandler implements EventHandlerInterface
{
    private const CHECK_RUN_EVENT_TYPE = 'check_run';

    public function __construct(
        private CIStatusUpdateHandler $CIStatusUpdateHandler,
        private GetPRInfoInterface $getPRInfo,
        private IsPRInReview $IsPRInReview
    ) {
    }

    public function supports(string $eventType): bool
    {
        return self::CHECK_RUN_EVENT_TYPE === $eventType;
    }

    public function handle(array $checkRunEvent): void
    {
        $PRIdentifier = $this->getPRIdentifier($checkRunEvent);
        if ($this->PRIsNotAlreadyInReview($PRIdentifier)) {
            return;
        }

        $PRInfo = $this->getCIStatusFromGithub($PRIdentifier);

        $command = new CIStatusUpdate();
        $command->PRIdentifier = $PRIdentifier->stringValue();
        $command->repositoryIdentifier = $checkRunEvent['repository']['full_name'];
        $command->status = $PRInfo->CIStatus->status;
        $command->buildLink = $PRInfo->CIStatus->buildLink;
        $this->CIStatusUpdateHandler->handle($command);
    }

    private function getPRIdentifier(array $CIStatusUpdate): PRIdentifier
    {
        $pullRequests = $CIStatusUpdate['check_run']['check_suite']['pull_requests'];
        Assert::notEmpty($pullRequests, 'Expected to have at least one pull request, didn\'t find any.');

        return PRIdentifier::fromString(
            sprintf(
                '%s/%s',
                $CIStatusUpdate['repository']['full_name'],
                $pullRequests[0]['number']
            )
        );
    }

    private function getCIStatusFromGithub(PRIdentifier $PRIdentifier): PRInfo
    {
        return $this->getPRInfo->fetch($PRIdentifier);
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
