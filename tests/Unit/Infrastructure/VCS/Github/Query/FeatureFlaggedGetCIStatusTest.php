<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\VCS\Github\Query;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CIStatus;
use Slub\Infrastructure\VCS\Github\Query\FeatureFlaggedGetCIStatus;
use Slub\Infrastructure\VCS\Github\Query\GetCIStatus;
use Slub\Infrastructure\VCS\Github\Query\GraphQL\GetCIStatusViaGraphQL;

/**
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class FeatureFlaggedGetCIStatusTest extends TestCase
{
    use ProphecyTrait;

    private const PR_IDENTIFIER = 'SamirBoulil/slub/36';
    private const COMMIT_REF = 'commit_ref';

    private ObjectProphecy|GetCIStatus $legacyGetCIStatus;

    private ObjectProphecy|GetCIStatusViaGraphQL $getCIStatusViaGraphQL;

    public function setUp(): void
    {
        parent::setUp();
        $this->legacyGetCIStatus = $this->prophesize(GetCIStatus::class);
        $this->getCIStatusViaGraphQL = $this->prophesize(GetCIStatusViaGraphQL::class);
    }

    /** @test */
    public function it_uses_the_graphql_implementation_when_the_feature_flag_is_on(): void
    {
        $PRIdentifier = PRIdentifier::fromString(self::PR_IDENTIFIER);
        $this->getCIStatusViaGraphQL->fetch($PRIdentifier, self::COMMIT_REF)
            ->willReturn(CIStatus::green())
            ->shouldBeCalled();
        $this->legacyGetCIStatus->fetch($PRIdentifier, self::COMMIT_REF)->shouldNotBeCalled();

        $actualCIStatus = $this->featureFlaggedGetCIStatus(true)->fetch($PRIdentifier, self::COMMIT_REF);

        self::assertEquals('GREEN', $actualCIStatus->status);
    }

    /** @test */
    public function it_uses_the_legacy_implementation_when_the_feature_flag_is_off(): void
    {
        $PRIdentifier = PRIdentifier::fromString(self::PR_IDENTIFIER);
        $this->legacyGetCIStatus->fetch($PRIdentifier, self::COMMIT_REF)
            ->willReturn(CIStatus::pending())
            ->shouldBeCalled();
        $this->getCIStatusViaGraphQL->fetch($PRIdentifier, self::COMMIT_REF)->shouldNotBeCalled();

        $actualCIStatus = $this->featureFlaggedGetCIStatus(false)->fetch($PRIdentifier, self::COMMIT_REF);

        self::assertEquals('PENDING', $actualCIStatus->status);
    }

    private function featureFlaggedGetCIStatus(bool $useGraphQL): FeatureFlaggedGetCIStatus
    {
        return new FeatureFlaggedGetCIStatus(
            $this->legacyGetCIStatus->reveal(),
            $this->getCIStatusViaGraphQL->reveal(),
            $useGraphQL
        );
    }
}
