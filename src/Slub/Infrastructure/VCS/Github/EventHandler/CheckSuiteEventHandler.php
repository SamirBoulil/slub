<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

use Slub\Application\CIStatusUpdate\CIStatusUpdate;
use Slub\Application\CIStatusUpdate\CIStatusUpdateHandler;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetPRInfoInterface;
use Slub\Domain\Query\PRInfo;
use Slub\Domain\Query\IsPRInReview;
use Webmozart\Assert\Assert;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class CheckSuiteEventHandler implements EventHandlerInterface
{
    private const CHECK_SUITE_EVENT_TYPE = 'check_suite';

    public function __construct(
        private CIStatusUpdateHandler $CIStatusUpdateHandler,
        private GetPRInfoInterface $getPRInfo,
        private IsPRInReview $IsPRInReview
    ) {
    }

    public function supports(string $eventType): bool
    {
        return self::CHECK_SUITE_EVENT_TYPE === $eventType;
    }

    public function handle(array $checkSuiteEvent): void
    {
        $PRIdentifier = $this->getPRIdentifier($checkSuiteEvent);
        if ($this->PRIsNotAlreadyInReview($PRIdentifier)) {
            return;
        }

        $PRInfo = $this->getCIStatusFromGithub($PRIdentifier);
        $command = new CIStatusUpdate();
        $command->PRIdentifier = $PRIdentifier->stringValue();
        $command->repositoryIdentifier = $checkSuiteEvent['repository']['full_name'];
        $command->status = $PRInfo->CIStatus->status;
        $command->buildLink = $PRInfo->CIStatus->buildLink;
        $this->CIStatusUpdateHandler->handle($command);
    }

    private function getPRIdentifier(array $checkSuiteEvent): PRIdentifier
    {
        $pullRequests = $checkSuiteEvent['check_suite']['pull_requests'];
        Assert::notEmpty($pullRequests, 'Expected to have at least one pull request, didn\'t find any.');

        return PRIdentifier::fromString(
            sprintf(
                '%s/%s',
                $checkSuiteEvent['repository']['full_name'],
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
