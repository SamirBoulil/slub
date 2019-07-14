<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Slack;

use Symfony\Component\HttpKernel\Client;
use Tests\WebTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class SlubBotActionTest extends WebTestCase
{
    /**
     * @test
     */
    public function it_answers_the_slack_challenge_if_there_is_one()
    {
        $client = static::createClient();
        $client->request('POST', '/chat/slack/messages', [], [], [], (string) json_encode(['challenge' => 'reply with this string']));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('{"challenge":"reply with this string"}', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function it_starts_slubbot_if_there_is_no_challenge()
    {
        $client = static::createClient();
        $client->request('POST', '/chat/slack/messages', [], [], [], (string) json_encode(['message' => 'Message coming from Slack']));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('', $client->getResponse()->getContent());
    }
}
