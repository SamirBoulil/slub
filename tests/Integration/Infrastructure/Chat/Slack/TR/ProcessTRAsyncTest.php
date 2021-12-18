<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Slack\TR;

use Slub\Domain\Repository\PRRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Tests\WebTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class ProcessTRAsyncTest extends WebTestCase
{
    private PRRepositoryInterface $PRRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->PRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
    }

    /**
     * @test
     */
    public function it_handles_pr_to_review_submission(): void
    {
        $client = self::getClient();
        $client->request('POST', '/chat/slack/tr', $this->PRToReviewSubmission());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertPRHasBeenPutToReview();
        $this->assertSuccessulSubmissionMessageIsDisplayedToTheAuthor($client);
    }

    // Case where no PR is detected

    /**
     * @return string[]
     */
    private function PRToReviewSubmission(): array
    {
        return [
            'text' => 'blabla https://github.com/SamirBoulil/slub/pull/153 blabla',
            'user_id' => 'user_123123',
            'team_id' => 'team_123',
            'channel_id' => 'channel_123',
            'trigger_id' => '123123.123123',
        ];
    }

    private function assertSuccessulSubmissionMessageIsDisplayedToTheAuthor(KernelBrowser $client)
    {
        self::assertJsonStringEqualsJsonString(
            json_encode([
                            'response_type' => 'in_channel',
                            'text' => [
                                'type' => 'section',
                                'text' => '<@user_123123> needs review for "Add new feature"',
                            ],
                            'accessory' => [
                                'type' => 'button',
                                'text' => [
                                    'type' => 'plain_text',
                                    'text' => 'Review',
                                    'emoji' => true,
                                    'style' => 'primary',
                                ],
                                'value' => 'show_pr',
                                'url' => 'https://github.akeeo/1212',
                                'action_id' => 'button_action',
                            ],
                        ], JSON_THROW_ON_ERROR),
            $client->getResponse()->getContent()
        );
    }

    private function assertPRHasBeenPutToReview()
    {
        $PRS = $this->PRRepository->all();
        $this->assertNotEmpty($PRS);

        $PRToReview = current($PRS);
        self::assertEquals('SamirBoulil/slub/153', $PRToReview->PRIdentifier()->stringValue());
        self::assertEquals('team_123@channel_123@123123.123123', $PRToReview->messageIdentifiers()[0]->stringValue());
        self::assertEquals('team_123', $PRToReview->channelIdentifiers()[0]->stringValue());
        self::assertEquals('sam', $PRToReview->authorIdentifier()->stringValue());
    }
}
