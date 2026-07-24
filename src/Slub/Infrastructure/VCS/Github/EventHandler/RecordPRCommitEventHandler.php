<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

use Psr\Log\LoggerInterface;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlPRCommitsRepository;

/**
 * Records the association between a PR and its head commit each time Github tells us
 * about it, at no API cost. It feeds the pr_commits table CachedFindPRNumber reads,
 * so that "status" events (which carry no PR number) can be resolved without calling
 * the Github API.
 *
 * Best effort only: failing to warm the cache should never fail the event processing,
 * status events fall back to the Github API when the commit is not found.
 *
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class RecordPRCommitEventHandler implements EventHandlerInterface
{
    private const PULL_REQUEST_EVENT_TYPE = 'pull_request';
    private const SUPPORTED_ACTIONS = ['opened', 'reopened', PRSynchronizedEventHandler::PR_SYNCHRONIZED_ACTION];

    public function __construct(
        private SqlPRCommitsRepository $prCommitsRepository,
        private LoggerInterface $logger
    ) {
    }

    public function supports(string $eventType, array $eventPayload): bool
    {
        return self::PULL_REQUEST_EVENT_TYPE === $eventType
            && in_array($eventPayload['action'] ?? '', self::SUPPORTED_ACTIONS, true)
            && isset(
                $eventPayload['repository']['full_name'],
                $eventPayload['pull_request']['head']['sha'],
                $eventPayload['pull_request']['number']
            );
    }

    public function handle(array $PREvent): void
    {
        try {
            $this->prCommitsRepository->save(
                $PREvent['repository']['full_name'],
                $PREvent['pull_request']['head']['sha'],
                (string) $PREvent['pull_request']['number']
            );
        } catch (\Exception|\Error $e) {
            $this->logger->warning(
                sprintf(
                    'Unable to record the head commit of PR "%s/%s": %s',
                    $PREvent['repository']['full_name'],
                    $PREvent['pull_request']['number'],
                    $e->getMessage()
                )
            );
        }
    }
}
