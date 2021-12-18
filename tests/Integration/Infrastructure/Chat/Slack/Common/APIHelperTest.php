<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Slack\Common;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Slub\Infrastructure\Chat\Slack\Common\APIHelper;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class APIHelperTest extends TestCase
{
    public function test_it_checks_if_the_response_status_code_is_2OO(): void
    {
        APIHelper::checkStatusCodeSuccess(new Response(200));
        $this->assertTrue(true, 'Response is not success, expected to be success');
    }

    public function test_it_throws_if_the_response_status_code_is_not_2OO(): void
    {
        $this->expectException(\RuntimeException::class);
        APIHelper::checkStatusCodeSuccess(new Response(204));
    }

    public function test_it_checks_if_the_slack_api_is_success(): void
    {
        APIHelper::checkResponseSuccess(new Response(200, [], json_encode(['ok' => true])));
        $this->assertTrue(true, 'Response is not success, expected to be success');
    }

    public function test_it_throws_if_the_slack_api_response_is_not_success(): void
    {
        $this->expectException(\RuntimeException::class);
        APIHelper::checkResponseSuccess(new Response(200, [], json_encode(['ok' => false])));
    }
}
