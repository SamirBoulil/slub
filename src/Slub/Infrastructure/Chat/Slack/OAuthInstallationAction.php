<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use GuzzleHttp\ClientInterface;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlSlackAppInstallationRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
    private string $slackClientId;
    private string $slackClientSecret;
    private string $githubAppHomePageURL;

    public function __construct(
        ClientInterface $httpClient,
        SqlSlackAppInstallationRepository $slackAppInstallationRepository,
        string $slackClientId,
        string $slackClientSecret,
        string $GithubAppHomePageURL
    ) {
        $this->httpClient = $httpClient;
        $this->slackAppInstallationRepository = $slackAppInstallationRepository;
        $this->slackClientId = $slackClientId;
        $this->slackClientSecret = $slackClientSecret;
        $this->githubAppHomePageURL = $GithubAppHomePageURL;
    }

    public function executeAction(Request $request): Response
    {
        $temporaryCode = $this->temporaryCode($request);
        $slackAppInstallation = $this->exchangeTemporaryCode($temporaryCode);
        $this->slackAppInstallationRepository->save($slackAppInstallation);

        return new RedirectResponse($this->githubAppHomePageURL);
    }

    private function temporaryCode(Request $request): string
    {
        $code = $request->query->get('code');
        if (null === $code) {
            throw new \RuntimeException('Expected to have a "code" field in the request, none found.');
        }

        return $code;
    }

    private function exchangeTemporaryCode(string $temporaryCode): SlackAppInstallation
    {
        $response = $this->httpClient->post('https://slack.com/api/oauth.v2.access', [
            'form_params' => [
                'client_id' => $this->slackClientId,
                'client_secret' => $this->slackClientSecret,
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
