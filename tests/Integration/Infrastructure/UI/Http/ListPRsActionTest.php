<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\UI\Http;

use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Repository\PRRepositoryInterface;
use Tests\WebTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class ListPRsActionTest extends WebTestCase
{
    private PRRepositoryInterface $PRRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->PRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $this->PRRepository->reset();
    }

    /**
     * @test
     */
    public function it_lists_all_the_prs_and_calculates_the_time_to_merge(): void
    {
        $this->PRRepository->save(
            PR::fromNormalized(
                [
                    'IDENTIFIER' => 'akeneo/pim-community-dev/1111',
                    'AUTHOR_IDENTIFIER' => 'sam',
                    'TITLE' => 'Add new feature',
                    'GTMS' => 1,
                    'NOT_GTMS' => 1,
                    'COMMENTS' => 1,
                    'CI_STATUS' => [
                        'BUILD_RESULT' => 'PENDING',
                        'BUILD_LINK' => '',
                    ],
                    'IS_MERGED' => true,
                    'CHANNEL_IDS' => ['squad-raccoons'],
                    'WORKSPACE_IDS' => ['akeneo'],
                    'MESSAGE_IDS' => ['1', '2'],
                    'PUT_TO_REVIEW_AT' => '1560177798',
                    'CLOSED_AT' => '1561363426',
                    'IS_TOO_LARGE' => false
                ]
            )
        );
        $this->PRRepository->save(
            PR::fromNormalized(
                [
                    'IDENTIFIER' => 'akeneo/pim-community-dev/2222',
                    'AUTHOR_IDENTIFIER' => 'sam',
                    'TITLE' => 'Add new feature',
                    'GTMS' => 1,
                    'NOT_GTMS' => 1,
                    'COMMENTS' => 1,
                    'CI_STATUS' => [
                        'BUILD_RESULT' => 'PENDING',
                        'BUILD_LINK' => '',
                    ],
                    'IS_MERGED' => false,
                    'CHANNEL_IDS' => ['squad-raccoons'],
                    'WORKSPACE_IDS' => ['akeneo'],
                    'MESSAGE_IDS' => ['1', '2'],
                    'PUT_TO_REVIEW_AT' => '1560177798',
                    'CLOSED_AT' => null,
                    'IS_TOO_LARGE' => false
                ]
            )
        );

        $client = self::getClient();
        $client->request('GET', '/');
        $response = $client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(
            [
                'AVERAGE_TIME_TO_MERGE' => 13,
                [
                    'IDENTIFIER' => 'akeneo/pim-community-dev/2222',
                    'AUTHOR_IDENTIFIER' => 'sam',
                    'TITLE' => 'Add new feature',
                    'GTMS' => 1,
                    'NOT_GTMS' => 1,
                    'COMMENTS' => 1,
                    'CI_STATUS' => [
                        'BUILD_RESULT' => 'PENDING',
                        'BUILD_LINK' => '',
                    ],
                    'IS_MERGED' => false,
                    'MESSAGE_IDS' => ['1', '2'],
                    'CHANNEL_IDS' => ['squad-raccoons'],
                    'WORKSPACE_IDS' => ['akeneo'],
                    'PUT_TO_REVIEW_AT' => '1560177798',
                    'CLOSED_AT' => null,
                    'IS_TOO_LARGE' => false
                ],
                [
                    'IDENTIFIER' => 'akeneo/pim-community-dev/1111',
                    'AUTHOR_IDENTIFIER' => 'sam',
                    'TITLE' => 'Add new feature',
                    'GTMS' => 1,
                    'NOT_GTMS' => 1,
                    'COMMENTS' => 1,
                    'CI_STATUS' => [
                        'BUILD_RESULT' => 'PENDING',
                        'BUILD_LINK' => '',
                    ],
                    'IS_MERGED' => true,
                    'MESSAGE_IDS' => ['1', '2'],
                    'CHANNEL_IDS' => ['squad-raccoons'],
                    'WORKSPACE_IDS' => ['akeneo'],
                    'PUT_TO_REVIEW_AT' => '1560177798',
                    'CLOSED_AT' => '1561363426',
                    'IS_TOO_LARGE' => false
                ],
            ],
            json_decode($response->getContent(), true)
        );
    }
}
