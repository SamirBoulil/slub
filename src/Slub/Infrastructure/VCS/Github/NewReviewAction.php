<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github;

use Psr\Log\LoggerInterface;
use Slub\Application\NewReview\NewReview;
use Slub\Application\NewReview\NewReviewHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class NewReviewAction
{
    private const SECRET_HEADER = 'X-Hub-Signature';

    /** @var NewReviewHandler */
    private $newReviewHandler;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $secret;

    public function __construct(NewReviewHandler $newReviewHandler, LoggerInterface $logger, string $secret)
    {
        $this->newReviewHandler = $newReviewHandler;
        $this->logger = $logger;
        $this->secret = $secret;
    }

    public function executeAction(Request $request): Response
    {
        $this->checkSecret($request);
        $PRStatusUpdate = $this->getPRStatusUpdate($request);

        $newPRReview = new NewReview();
        $newPRReview->PRIdentifier = $this->getPRIdentifier($PRStatusUpdate);
        $newPRReview->repositoryIdentifier = $this->getRepositoryIdentifier($PRStatusUpdate);
        $newPRReview->reviewStatus = $this->reviewStatus($PRStatusUpdate);
        $this->newReviewHandler->handle($newPRReview);

        return new Response();
    }

    private function getPRStatusUpdate(Request $request): array
    {
        return json_decode((string) $request->getContent(), true);
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

    private function reviewStatus(array $PRStatusUpdate): string
    {
        $PRStatus = $PRStatusUpdate['review']['state'];

        switch ($PRStatus) {
            case 'approved':
                return 'accepted';
            case 'request_changes':
                return 'refused';
            case 'commented':
                return 'commented';
            default:
                throw new BadRequestHttpException(
                    sprintf(
                        'Unkown review status "%s", expected one of "approved", "request_changes", "commented"',
                        $PRStatus
                    )
                );
        }
    }

    private function checkSecret(Request $request): void
    {
        $secretHeader = $request->headers->get(self::SECRET_HEADER);
        if (null === $secretHeader || empty($secretHeader) || !is_string($secretHeader)) {
            throw new BadRequestHttpException();
        }
        $actualSHA1 = last(explode('=', $secretHeader));
        $expectedSHA1 = hash_hmac('sha1', (string) $request->getContent(), $this->secret);

        if ($expectedSHA1 !== $actualSHA1) {
            throw new BadRequestHttpException();
        }
    }
}
