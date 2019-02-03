<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\PR;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;

class PRTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_a_PR()
    {
        $identifier = PRIdentifier::create('akeneo/pim-community-dev/1111');

        $pr = PR::create($identifier);

        $this->assertNotNull($pr);
    }

    /**
     * @test
     */
    public function it_is_created_from_normalized()
    {
        $normalizedPR = ['identifier' => 'akeneo/pim-community-dev/1111', 'GTM' => 2];

        $pr = PR::fromNormalized($normalizedPR);

        $this->assertSame($normalizedPR, $pr->normalize());
    }

    /**
     * @test
     */
    public function it_can_be_GTM_multiple_times()
    {
        $pr = PR::create(PRIdentifier::create('akeneo/pim-community-dev/1111'));
        $this->assertEquals(['identifier' => 'akeneo/pim-community-dev/1111', 'GTM' => 0], $pr->normalize());

        $pr->GTM();
        $this->assertEquals(['identifier' => 'akeneo/pim-community-dev/1111', 'GTM' => 1], $pr->normalize());

        $pr->GTM();
        $this->assertEquals(['identifier' => 'akeneo/pim-community-dev/1111', 'GTM' => 2], $pr->normalize());
    }

    /**
     * @test
     */
    public function it_normalizes_itself()
    {
        $pr = PR::create(PRIdentifier::create('akeneo/pim-community-dev/1111'));

        $this->assertSame(['identifier' => 'akeneo/pim-community-dev/1111', 'GTM' => 0], $pr->normalize());
    }

    /**
     * @test
     */
    public function it_returns_its_identifier()
    {
        $identifier = PRIdentifier::create('akeneo/pim-community-dev/1111');

        $pr = PR::create($identifier);

        $this->assertTrue($pr->PRIdentifier()->equals($identifier));
    }
}
