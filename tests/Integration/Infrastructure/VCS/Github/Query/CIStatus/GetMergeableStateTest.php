<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Github\Query\CIStatus;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\NullLogger;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClient;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetCheckRunStatus;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetMergeableState;
use Slub\Infrastructure\VCS\Github\Query\GetPRDetails;
use Slub\Infrastructure\VCS\Github\Query\GetPRInfo;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;
use Tests\WebTestCase;

class GetMergeableStateTest extends TestCase
{
    use ProphecyTrait;
    private ObjectProphecy|GetPRDetails $getPRDetails;
    private GetMergeableState $getMergeableState;

    public function setUp(): void
    {
        parent::setUp();
        $this->getPRDetails = $this->prophesize(GetPRDetails::class);
        $this->getMergeableState = new GetMergeableState($this->getPRDetails->reveal());
    }

    public function test_it_returns_true_if_the_pr_is_mergeable_and_in_clean_state()
    {
        $PRIdentifier = PRIdentifier::fromString('dummy');
        $this->getPRDetails
            ->fetch($PRIdentifier)
            ->willReturn(
                [
                    'mergeable' => true,
                    'mergeable_state' => 'clean',
                ]
            );

        self::assertTrue($this->getMergeableState->fetch($PRIdentifier));
    }

    /**
     * @dataProvider notMergeableState
     */
    public function test_it_returns_false_if_the_pr_is_not_mergeable_nor_in_a_clean_state($prDetailsNotMergeable)
    {
        $PRIdentifier = PRIdentifier::fromString('dummy');
        $this->getPRDetails
            ->fetch($PRIdentifier)
            ->willReturn($prDetailsNotMergeable);

        self::assertFalse($this->getMergeableState->fetch($PRIdentifier));
    }

    public function notMergeableState()
    {
        return [
            'mergeable but dirty' => [
                [
                    'mergeable' => true,
                    'mergeable_state' => 'dirty',
                ],
            ],
            'not_mergeable and clean' => [
                [
                    'mergeable' => false,
                    'mergeable_state' => 'clean',
                ],
            ],
        ];
    }
}
