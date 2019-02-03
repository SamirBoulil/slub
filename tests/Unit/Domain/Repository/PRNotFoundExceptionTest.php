<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Repository;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRNotFoundException;

class PRNotFoundExceptionTest extends TestCase
{
    /**
     * @test
     */
    public function it_is_able_to_create_itself_with_an_error_message()
    {
        $identifier = PRIdentifier::create('akeneo/pim-community-dev/1111');

        $exception = PRNotFoundException::create($identifier);

        $this->assertNotNull($exception);
        $this->assertSame(
            'PR with identifier "akeneo/pim-community-dev/1111" not found',
            $exception->getMessage()
        );
    }
}
