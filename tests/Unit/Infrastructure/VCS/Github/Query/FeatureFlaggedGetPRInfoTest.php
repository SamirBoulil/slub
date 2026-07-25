<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\VCS\Github\Query;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetPRInfoInterface;
use Slub\Domain\Query\PRInfo;
use Slub\Infrastructure\VCS\Github\Query\FeatureFlaggedGetPRInfo;
use Slub\Infrastructure\VCS\Github\Query\GraphQL\GetPRInfoViaGraphQL;

/**
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class FeatureFlaggedGetPRInfoTest extends TestCase
{
    use ProphecyTrait;

    private const PR_IDENTIFIER = 'SamirBoulil/slub/36';

    private ObjectProphecy|GetPRInfoInterface $legacyGetPRInfo;

    private ObjectProphecy|GetPRInfoViaGraphQL $getPRInfoViaGraphQL;

    public function setUp(): void
    {
        parent::setUp();
        $this->legacyGetPRInfo = $this->prophesize(GetPRInfoInterface::class);
        $this->getPRInfoViaGraphQL = $this->prophesize(GetPRInfoViaGraphQL::class);
    }

    /** @test */
    public function it_uses_the_graphql_implementation_when_the_feature_flag_is_on(): void
    {
        $PRIdentifier = PRIdentifier::fromString(self::PR_IDENTIFIER);
        $PRInfo = new PRInfo();
        $this->getPRInfoViaGraphQL->fetch($PRIdentifier)->willReturn($PRInfo)->shouldBeCalled();
        $this->legacyGetPRInfo->fetch($PRIdentifier)->shouldNotBeCalled();

        $actualPRInfo = $this->featureFlaggedGetPRInfo(true)->fetch($PRIdentifier);

        self::assertSame($PRInfo, $actualPRInfo);
    }

    /** @test */
    public function it_uses_the_legacy_implementation_when_the_feature_flag_is_off(): void
    {
        $PRIdentifier = PRIdentifier::fromString(self::PR_IDENTIFIER);
        $PRInfo = new PRInfo();
        $this->legacyGetPRInfo->fetch($PRIdentifier)->willReturn($PRInfo)->shouldBeCalled();
        $this->getPRInfoViaGraphQL->fetch($PRIdentifier)->shouldNotBeCalled();

        $actualPRInfo = $this->featureFlaggedGetPRInfo(false)->fetch($PRIdentifier);

        self::assertSame($PRInfo, $actualPRInfo);
    }

    private function featureFlaggedGetPRInfo(bool $useGraphQL): FeatureFlaggedGetPRInfo
    {
        return new FeatureFlaggedGetPRInfo(
            $this->legacyGetPRInfo->reveal(),
            $this->getPRInfoViaGraphQL->reveal(),
            $useGraphQL
        );
    }
}
