<?php

declare(strict_types=1);

namespace Slub\Infrastructure\UI\Http;

use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Repository\PRRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class ListPRsAction
{
    /** @var PRRepositoryInterface */
    private $PRRepository;

    public function __construct(PRRepositoryInterface $PRRepository)
    {
        $this->PRRepository = $PRRepository;
    }

    public function executeAction(Request $request): Response
    {
        return new JsonResponse(
            array_map(
                function (PR $PR) {
                    return $PR->normalize();
                },
                $this->PRRepository->all()
            )
        );
    }
}
