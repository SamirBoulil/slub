<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github;

use Psr\Log\LoggerInterface;
use Slub\Application\NewReview\NewReview;
use Slub\Application\NewReview\NewReviewHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class NewReviewAction
{
    /** @var NewReviewHandler */
    private $newReviewHandler;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(NewReviewHandler $newReviewHandler, LoggerInterface $logger)
    {
        $this->newReviewHandler = $newReviewHandler;
        $this->logger = $logger;
    }

    public function executeAction(Request $request): Response
    {
        $PRStatusUpdate = $this->getPRStatusUpdate($request);
        if (!$this->isStatusSupported($PRStatusUpdate)) {
            return new Response();
        }

        $newPRReview = new NewReview();
        $newPRReview->PRIdentifier = $this->getPRIdentifier($PRStatusUpdate);
        $newPRReview->repositoryIdentifier = $this->getRepositoryIdentifier($PRStatusUpdate);
        $newPRReview->isGTM = $this->isGTM($PRStatusUpdate);
        $this->newReviewHandler->handle($newPRReview);

        return new Response();
    }

    private function getPRStatusUpdate(Request $request): array
    {
        return json_decode((string) $request->getContent(), true);
    }

    private function isStatusSupported(array $PRStatusUpdate): bool
    {
        $PRStatus = $PRStatusUpdate['review']['state'];

        return 'APPROVE' === $PRStatus || 'REQUEST_CHANGES' === $PRStatus;
    }

    private function getPRIdentifier(array $PRStatusUpdate): string
    {
        $PRUrl = $PRStatusUpdate['review']['html_url'];
        preg_match('|https://github.com/(.*)/pull/(.*)#.*$|', $PRUrl, $matches);

        return $matches[1] . '/' . $matches[2];
    }

    private function getRepositoryIdentifier(array $PRStatusUpdate): string
    {
        $PRUrl = $PRStatusUpdate['review']['html_url'];
        preg_match('#https://github.com/(.*)/pull/.*$#', $PRUrl, $matches);

        return $matches[1];
    }

    private function isGTM(array $PRStatusUpdate): bool
    {
        $PRStatus = $PRStatusUpdate['review']['state'];

        return 'APPROVE' === $PRStatus || 'REQUEST_CHANGES' === $PRStatus;
    }
}
