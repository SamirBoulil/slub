<?php

declare(strict_types=1);

namespace Slub\Infrastructure\UI\Http;

use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Query\GetAverageTimeToMergeInterface;
use Slub\Domain\Repository\PRRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class ListPRsAction
{
    public function __construct(private PRRepositoryInterface $PRRepository, private GetAverageTimeToMergeInterface $averageTimeToMerge)
    {
    }

    public function executeAction(): JsonResponse
    {
        $result = $this->allPRs();
        $result['AVERAGE_TIME_TO_MERGE'] = $this->averageTimeToMerge->fetch();

        return new JsonResponse($result);
    }

    private function allPRs(): array
    {
        return array_map(
            fn (PR $PR) => $PR->normalize(),
            $this->PRRepository->all()
        );
    }
}
