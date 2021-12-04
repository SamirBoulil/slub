<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use Slub\Application\PutPRToReview\PutPRToReview;
use Slub\Application\PutPRToReview\PutPRToReviewHandler;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @copyright 2021 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class TRAction
{
    public function __construct(private PutPRToReviewHandler $putPRToReviewHandler) {}

    public function executeAction(Request $request): Response
    {
        $putPRToReviewCommand = new PutPRToReview();
        $this->putPRToReviewHandler->handle($putPRToReviewCommand);

        return new JsonResponse(['message' => 'thank you :)']);
    }
}
