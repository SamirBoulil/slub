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
        $identifier = PRIdentifier::create('pim-community-dev', '1111');

        $pr = PR::create($identifier);

        $this->assertNotNull($pr);
    }

    /**
     * @test
     */
    public function it_is_created_from_normalized()
    {
        $normalizedPR = ['identifier' => 'akeneo/pim-community-dev/1111'];

        $pr = PR::fromNormalized($normalizedPR);

        $this->assertSame($normalizedPR, $pr->normalize());
    }

    /**
     * @test
     */
    public function it_normalizes_itself()
    {
        $pr = PR::create(PRIdentifier::create('akeneo/pim-community-dev', '1111'));

        $this->assertSame(['identifier' => 'akeneo/pim-community-dev/1111'], $pr->normalize());
    }
}
