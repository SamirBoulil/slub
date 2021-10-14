<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use GuzzleHttp\ClientInterface;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlSlackAppInstallationRepository;
use Symfony\Component\HttpFoundation\Request;

/**
 * In the OAuth Slack installation flow (https://api.slack.com/authentication/oauth-v2).
 * After a user installs & authorize the Slack app in a workspace, Slacks calls a setup redirect URL with a temporary code.
 * This code is then used to exchange against an access token used to call the Slack API on said workspace.
 *
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class OAuthInstallationAction
{
    private ClientInterface $httpClient;
    private SqlSlackAppInstallationRepository $slackAppInstallationRepository;

    public function __construct(
        ClientInterface $httpClient,
        SqlSlackAppInstallationRepository $slackAppInstallationRepository
    ) {
        $this->httpClient = $httpClient;
        $this->slackAppInstallationRepository = $slackAppInstallationRepository;
    }

    public function executeAction(Request $request): void
    {
        $temporaryCode = $this->temporaryCode($request);
        $slackAppInstallation = $this->exchangeTemporaryCode($temporaryCode);
        $this->slackAppInstallationRepository->save($slackAppInstallation);
    }

    private function temporaryCode(Request $request): string
    {
        $content = json_decode((string)$request->getContent(), true);
        if (!isset($content['code'])) {
            throw new \RuntimeException('Expected to have a "code" field in the request, none found.');
        }

        return $content['code'];
    }

    private function exchangeTemporaryCode(string $temporaryCode): SlackAppInstallation
    {
        $response = $this->httpClient->post('https://slack.com/api/oauth.v2.access', [
            'form_params' => [
                'client_id' => '',
                'client_secret' => '',
                'code' => $temporaryCode,
            ],
        ]);

        $content = APIHelper::checkResponse($response);

        return $this->slackAppInstallation($content);
    }

    private function slackAppInstallation(array $content): SlackAppInstallation
    {
        $slackAppInstallation = new SlackAppInstallation();
        $slackAppInstallation->accessToken = $content['access_token'];
        $slackAppInstallation->workspaceId = $content['team']['id'];

        return $slackAppInstallation;
    }
}
