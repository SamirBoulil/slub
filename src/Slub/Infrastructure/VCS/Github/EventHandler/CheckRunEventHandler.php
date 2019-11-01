<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

use Slub\Application\CIStatusUpdate\CIStatusUpdate;
use Slub\Application\CIStatusUpdate\CIStatusUpdateHandler;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetPRInfoInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class CheckRunEventHandler implements EventHandlerInterface
{
    private const CHECK_RUN_EVENT_TYPE = 'check_run';

    /** @var CIStatusUpdateHandler */
    private $CIStatusUpdateHandler;

    /** @var GetPRInfoInterface */
    private $getPRInfo;

    /** @var string[] */
    private $supportedCheckRunNames;

    public function __construct(
        CIStatusUpdateHandler $CIStatusUpdateHandler,
        GetPRInfoInterface $getPRInfo,
        string $supportedCheckRunNames
    ) {
        $this->CIStatusUpdateHandler = $CIStatusUpdateHandler;
        $this->supportedCheckRunNames = explode(',', $supportedCheckRunNames);
        $this->getPRInfo = $getPRInfo;
    }

    public function supports(string $eventType): bool
    {
        return self::CHECK_RUN_EVENT_TYPE === $eventType;
    }

    public function handle(array $checkRunEvent): void
    {
        if ($this->isCICheckGreenButNotSupported($checkRunEvent)) {
            return;
        }

        $this->updateCIStatus($checkRunEvent);
    }

    private function isCICheckGreenButNotSupported(array $checkRunEvent): bool
    {
        $isGreen = 'GREEN' === $this->getStatus($checkRunEvent);
        $isSupported = in_array($checkRunEvent['check_run']['name'], $this->supportedCheckRunNames);

        return $isGreen && !$isSupported;
    }

    private function updateCIStatus(array $CIStatusUpdate): void
    {
        $PRIdentifier = $this->getPRIdentifier($CIStatusUpdate);
        $CIStatus = $this->getCIStatusFromGithub($PRIdentifier);

        $command = new CIStatusUpdate();
        $command->PRIdentifier = $PRIdentifier->stringValue();
        $command->repositoryIdentifier = $CIStatusUpdate['repository']['full_name'];
        $command->status = $CIStatus;
        $command->buildLink = $CIStatusUpdate['build_link'];
        $this->CIStatusUpdateHandler->handle($command);
    }

    private function getPRIdentifier(array $CIStatusUpdate): PRIdentifier
    {
        return PRIdentifier::fromString(
            sprintf(
                '%s/%s',
                $CIStatusUpdate['repository']['full_name'],
                $CIStatusUpdate['check_run']['check_suite']['pull_requests'][0]['number']
            )
        );
    }

    private function getStatus(array $CIStatusUpdate): string
    {
        if ('queued' === $CIStatusUpdate['check_run']['status']) {
            return 'PENDING';
        }

        $conclusion = $CIStatusUpdate['check_run']['conclusion'];
        switch ($conclusion) {
            case 'success': return 'GREEN';
            case 'failure':
            case 'error':
            case 'action_required':
            case 'default': return 'RED';
        }

        throw new \InvalidArgumentException(
            sprintf(
                'Expected conclusion to be one of "success" or "failure", but "%s" found',
                $conclusion
            )
        );
    }

    private function getCIStatusFromGithub(PRIdentifier $PRIdentifier): string
    {
        return $this->getPRInfo->fetch($PRIdentifier)->CIStatus;
    }
}
