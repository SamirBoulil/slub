<?php

declare(strict_types=1);

namespace Tests\Functional;

use Ramsey\Uuid\Uuid;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\AuthorIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\PR\Title;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;
use Slub\Domain\Repository\PRRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Client;
use Tests\WebTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class NewReviewTest extends WebTestCase
{
    private const PR_IDENTIFIER = 'SamirBoulil/slub/10';

    /** @var PRRepositoryInterface */
    private $PRRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->PRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $this->GivenAPRToReview();
    }

    /**
     * @test
     */
    public function it_listens_to_accepted_PR()
    {
        $client = $this->WhenAPRIsAcceptedByAReviewer();

        $this->assertReviews(1, 0, 0);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function it_listens_to_refused_PR()
    {
        $client = $this->WhenAPRIsRefusedByAReviewer();

        $this->assertReviews(0, 1, 0);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function it_listens_to_commented_PR()
    {
        $client = $this->WhenAPRIsCommentedByAReviewer();

        $this->assertReviews(0, 0, 1);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function it_does_not_listen_authors_own_comments()
    {
        $client = $this->WhenAPRIsCommentedByItsOwnAuthor();

        $this->assertReviews(0, 0, 0);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    private function GivenAPRToReview(): void
    {
        $this->PRRepository->save(
            PR::create(
                PRIdentifier::create(self::PR_IDENTIFIER),
                ChannelIdentifier::fromString('squad-raccoons'),
                WorkspaceIdentifier::fromString('akeneo'),
                MessageIdentifier::create('CHANNEL_ID@1111'),
                AuthorIdentifier::fromString('sam'),
                Title::fromString('Add new feature')
            )
        );
    }

    private function WhenAPRIsAcceptedByAReviewer(): Client
    {
        return $this->callAPI($this->PRAccepted());
    }

    private function WhenAPRIsRefusedByAReviewer(): Client
    {
        return $this->CallAPI($this->PRRefused());
    }

    private function WhenAPRIsCommentedByAReviewer(): Client
    {
        return $this->CallAPI($this->PRCommented());
    }

    private function WhenAPRIsCommentedByItsOwnAuthor(): Client
    {
        return $this->CallAPI($this->PRCommentedByOwnAuthor());
    }

    private function CallAPI(string $review): Client
    {
        $client = static::createClient();
        $signature = sprintf('sha1=%s', hash_hmac('sha1', $review, $this->get('GITHUB_WEBHOOK_SECRET')));
        $client->request(
            'POST',
            '/vcs/github',
            [],
            [],
            ['HTTP_X-GitHub-Event' => 'pull_request_review', 'HTTP_X-Hub-Signature' => $signature, 'HTTP_X-Github-Delivery' => Uuid::uuid4()->toString()],
            $review
        );
        return $client;
    }

    private function assertReviews(int $GTMCount, int $NotGTMCount, int $commentsCount): void
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString(self::PR_IDENTIFIER));
        $this->assertEquals($GTMCount, $PR->normalize()['GTMS']);
        $this->assertEquals($NotGTMCount, $PR->normalize()['NOT_GTMS']);
        $this->assertEquals($commentsCount, $PR->normalize()['COMMENTS']);
    }

    private function PRAccepted(): string
    {
        $json = <<<JSON
{
    "action": "submitted",
    "review": {
        "id": 207149777,
        "node_id": "MDE3OlB1bGxSZXF1ZXN0UmV2aWV3MjA3MTQ5Nzc3",
        "user": {
            "login": "SamirBoulil",
            "id": 111111,
            "node_id": "MDQ6VXNlcjE4MjY0NzM=",
            "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
            "gravatar_id": "",
            "url": "https://api.github.com/users/SamirBoulil",
            "html_url": "https://github.com/SamirBoulil",
            "followers_url": "https://api.github.com/users/SamirBoulil/followers",
            "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
            "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
            "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
            "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
            "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
            "repos_url": "https://api.github.com/users/SamirBoulil/repos",
            "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
            "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
            "type": "User",
            "site_admin": false
        },
        "body": "Pourquoi pas",
        "commit_id": "caed81fdd51f573d7235fb63959ef6a6d0aaf809",
        "submitted_at": "2019-02-24T12:33:40Z",
        "state": "approved",
        "html_url": "https://github.com/SamirBoulil/slub/pull/10#pullrequestreview-207149777",
        "pull_request_url": "https://api.github.com/repos/SamirBoulil/slub/pulls/10",
        "author_association": "OWNER",
        "_links": {
            "html": {
                "href": "https://github.com/SamirBoulil/slub/pull/10#pullrequestreview-207149777"
            },
            "pull_request": {
                "href": "https://api.github.com/repos/SamirBoulil/slub/pulls/10"
            }
        }
    },
    "pull_request": {
        "url": "https://api.github.com/repos/SamirBoulil/slub/pulls/10",
        "id": 255680940,
        "node_id": "MDExOlB1bGxSZXF1ZXN0MjU1NjgwOTQw",
        "html_url": "https://github.com/SamirBoulil/slub/pull/10",
        "diff_url": "https://github.com/SamirBoulil/slub/pull/10.diff",
        "patch_url": "https://github.com/SamirBoulil/slub/pull/10.patch",
        "issue_url": "https://api.github.com/repos/SamirBoulil/slub/issues/10",
        "number": 10,
        "state": "open",
        "locked": false,
        "title": "Add github webhook",
        "user": {
            "login": "SamirBoulil",
            "id": 1826473,
            "node_id": "MDQ6VXNlcjE4MjY0NzM=",
            "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
            "gravatar_id": "",
            "url": "https://api.github.com/users/SamirBoulil",
            "html_url": "https://github.com/SamirBoulil",
            "followers_url": "https://api.github.com/users/SamirBoulil/followers",
            "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
            "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
            "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
            "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
            "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
            "repos_url": "https://api.github.com/users/SamirBoulil/repos",
            "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
            "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
            "type": "User",
            "site_admin": false
        },
        "body": "",
        "created_at": "2019-02-24T12:33:24Z",
        "updated_at": "2019-02-24T12:33:40Z",
        "closed_at": null,
        "merged_at": null,
        "merge_commit_sha": "32ddf907072311c89ee013b4f66040a220d6b424",
        "assignee": null,
        "assignees": [],
        "requested_reviewers": [],
        "requested_teams": [],
        "labels": [],
        "milestone": null,
        "commits_url": "https://api.github.com/repos/SamirBoulil/slub/pulls/10/commits",
        "review_comments_url": "https://api.github.com/repos/SamirBoulil/slub/pulls/10/comments",
        "review_comment_url": "https://api.github.com/repos/SamirBoulil/slub/pulls/comments{/number}",
        "comments_url": "https://api.github.com/repos/SamirBoulil/slub/issues/10/comments",
        "statuses_url": "https://api.github.com/repos/SamirBoulil/slub/statuses/caed81fdd51f573d7235fb63959ef6a6d0aaf809",
        "head": {
            "label": "SamirBoulil:plug-github",
            "ref": "plug-github",
            "sha": "caed81fdd51f573d7235fb63959ef6a6d0aaf809",
            "user": {
                "login": "SamirBoulil",
                "id": 1826473,
                "node_id": "MDQ6VXNlcjE4MjY0NzM=",
                "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
                "gravatar_id": "",
                "url": "https://api.github.com/users/SamirBoulil",
                "html_url": "https://github.com/SamirBoulil",
                "followers_url": "https://api.github.com/users/SamirBoulil/followers",
                "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
                "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
                "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
                "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
                "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
                "repos_url": "https://api.github.com/users/SamirBoulil/repos",
                "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
                "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
                "type": "User",
                "site_admin": false
            },
            "repo": {
                "id": 166291213,
                "node_id": "MDEwOlJlcG9zaXRvcnkxNjYyOTEyMTM=",
                "name": "slub",
                "full_name": "SamirBoulil/slub",
                "private": false,
                "owner": {
                    "login": "SamirBoulil",
                    "id": 1826473,
                    "node_id": "MDQ6VXNlcjE4MjY0NzM=",
                    "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
                    "gravatar_id": "",
                    "url": "https://api.github.com/users/SamirBoulil",
                    "html_url": "https://github.com/SamirBoulil",
                    "followers_url": "https://api.github.com/users/SamirBoulil/followers",
                    "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
                    "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
                    "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
                    "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
                    "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
                    "repos_url": "https://api.github.com/users/SamirBoulil/repos",
                    "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
                    "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
                    "type": "User",
                    "site_admin": false
                },
                "html_url": "https://github.com/SamirBoulil/slub",
                "description": "Improve the feedback loop between Github pull requests statuses and teams using Slack.",
                "fork": false,
                "url": "https://api.github.com/repos/SamirBoulil/slub",
                "forks_url": "https://api.github.com/repos/SamirBoulil/slub/forks",
                "keys_url": "https://api.github.com/repos/SamirBoulil/slub/keys{/key_id}",
                "collaborators_url": "https://api.github.com/repos/SamirBoulil/slub/collaborators{/collaborator}",
                "teams_url": "https://api.github.com/repos/SamirBoulil/slub/teams",
                "hooks_url": "https://api.github.com/repos/SamirBoulil/slub/hooks",
                "issue_events_url": "https://api.github.com/repos/SamirBoulil/slub/issues/events{/number}",
                "events_url": "https://api.github.com/repos/SamirBoulil/slub/events",
                "assignees_url": "https://api.github.com/repos/SamirBoulil/slub/assignees{/user}",
                "branches_url": "https://api.github.com/repos/SamirBoulil/slub/branches{/branch}",
                "tags_url": "https://api.github.com/repos/SamirBoulil/slub/tags",
                "blobs_url": "https://api.github.com/repos/SamirBoulil/slub/git/blobs{/sha}",
                "git_tags_url": "https://api.github.com/repos/SamirBoulil/slub/git/tags{/sha}",
                "git_refs_url": "https://api.github.com/repos/SamirBoulil/slub/git/refs{/sha}",
                "trees_url": "https://api.github.com/repos/SamirBoulil/slub/git/trees{/sha}",
                "statuses_url": "https://api.github.com/repos/SamirBoulil/slub/statuses/{sha}",
                "languages_url": "https://api.github.com/repos/SamirBoulil/slub/languages",
                "stargazers_url": "https://api.github.com/repos/SamirBoulil/slub/stargazers",
                "contributors_url": "https://api.github.com/repos/SamirBoulil/slub/contributors",
                "subscribers_url": "https://api.github.com/repos/SamirBoulil/slub/subscribers",
                "subscription_url": "https://api.github.com/repos/SamirBoulil/slub/subscription",
                "commits_url": "https://api.github.com/repos/SamirBoulil/slub/commits{/sha}",
                "git_commits_url": "https://api.github.com/repos/SamirBoulil/slub/git/commits{/sha}",
                "comments_url": "https://api.github.com/repos/SamirBoulil/slub/comments{/number}",
                "issue_comment_url": "https://api.github.com/repos/SamirBoulil/slub/issues/comments{/number}",
                "contents_url": "https://api.github.com/repos/SamirBoulil/slub/contents/{+path}",
                "compare_url": "https://api.github.com/repos/SamirBoulil/slub/compare/{base}...{head}",
                "merges_url": "https://api.github.com/repos/SamirBoulil/slub/merges",
                "archive_url": "https://api.github.com/repos/SamirBoulil/slub/{archive_format}{/ref}",
                "downloads_url": "https://api.github.com/repos/SamirBoulil/slub/downloads",
                "issues_url": "https://api.github.com/repos/SamirBoulil/slub/issues{/number}",
                "pulls_url": "https://api.github.com/repos/SamirBoulil/slub/pulls{/number}",
                "milestones_url": "https://api.github.com/repos/SamirBoulil/slub/milestones{/number}",
                "notifications_url": "https://api.github.com/repos/SamirBoulil/slub/notifications{?since,all,participating}",
                "labels_url": "https://api.github.com/repos/SamirBoulil/slub/labels{/name}",
                "releases_url": "https://api.github.com/repos/SamirBoulil/slub/releases{/id}",
                "deployments_url": "https://api.github.com/repos/SamirBoulil/slub/deployments",
                "created_at": "2019-01-17T20:21:39Z",
                "updated_at": "2019-02-24T11:35:21Z",
                "pushed_at": "2019-02-24T12:33:25Z",
                "git_url": "git://github.com/SamirBoulil/slub.git",
                "ssh_url": "git@github.com:SamirBoulil/slub.git",
                "clone_url": "https://github.com/SamirBoulil/slub.git",
                "svn_url": "https://github.com/SamirBoulil/slub",
                "homepage": "",
                "size": 211,
                "stargazers_count": 0,
                "watchers_count": 0,
                "language": "PHP",
                "has_issues": true,
                "has_projects": true,
                "has_downloads": true,
                "has_wiki": true,
                "has_pages": false,
                "forks_count": 0,
                "mirror_url": null,
                "archived": false,
                "open_issues_count": 1,
                "license": {
                    "key": "mit",
                    "name": "MIT License",
                    "spdx_id": "MIT",
                    "url": "https://api.github.com/licenses/mit",
                    "node_id": "MDc6TGljZW5zZTEz"
                },
                "forks": 0,
                "open_issues": 1,
                "watchers": 0,
                "default_branch": "master"
            }
        },
        "base": {
            "label": "SamirBoulil:master",
            "ref": "master",
            "sha": "7a5df1cd6070a872fbc22522ceefa83c763c3f6e",
            "user": {
                "login": "SamirBoulil",
                "id": 1826473,
                "node_id": "MDQ6VXNlcjE4MjY0NzM=",
                "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
                "gravatar_id": "",
                "url": "https://api.github.com/users/SamirBoulil",
                "html_url": "https://github.com/SamirBoulil",
                "followers_url": "https://api.github.com/users/SamirBoulil/followers",
                "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
                "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
                "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
                "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
                "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
                "repos_url": "https://api.github.com/users/SamirBoulil/repos",
                "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
                "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
                "type": "User",
                "site_admin": false
            },
            "repo": {
                "id": 166291213,
                "node_id": "MDEwOlJlcG9zaXRvcnkxNjYyOTEyMTM=",
                "name": "slub",
                "full_name": "SamirBoulil/slub",
                "private": false,
                "owner": {
                    "login": "SamirBoulil",
                    "id": 1826473,
                    "node_id": "MDQ6VXNlcjE4MjY0NzM=",
                    "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
                    "gravatar_id": "",
                    "url": "https://api.github.com/users/SamirBoulil",
                    "html_url": "https://github.com/SamirBoulil",
                    "followers_url": "https://api.github.com/users/SamirBoulil/followers",
                    "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
                    "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
                    "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
                    "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
                    "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
                    "repos_url": "https://api.github.com/users/SamirBoulil/repos",
                    "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
                    "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
                    "type": "User",
                    "site_admin": false
                },
                "html_url": "https://github.com/SamirBoulil/slub",
                "description": "Improve the feedback loop between Github pull requests statuses and teams using Slack.",
                "fork": false,
                "url": "https://api.github.com/repos/SamirBoulil/slub",
                "forks_url": "https://api.github.com/repos/SamirBoulil/slub/forks",
                "keys_url": "https://api.github.com/repos/SamirBoulil/slub/keys{/key_id}",
                "collaborators_url": "https://api.github.com/repos/SamirBoulil/slub/collaborators{/collaborator}",
                "teams_url": "https://api.github.com/repos/SamirBoulil/slub/teams",
                "hooks_url": "https://api.github.com/repos/SamirBoulil/slub/hooks",
                "issue_events_url": "https://api.github.com/repos/SamirBoulil/slub/issues/events{/number}",
                "events_url": "https://api.github.com/repos/SamirBoulil/slub/events",
                "assignees_url": "https://api.github.com/repos/SamirBoulil/slub/assignees{/user}",
                "branches_url": "https://api.github.com/repos/SamirBoulil/slub/branches{/branch}",
                "tags_url": "https://api.github.com/repos/SamirBoulil/slub/tags",
                "blobs_url": "https://api.github.com/repos/SamirBoulil/slub/git/blobs{/sha}",
                "git_tags_url": "https://api.github.com/repos/SamirBoulil/slub/git/tags{/sha}",
                "git_refs_url": "https://api.github.com/repos/SamirBoulil/slub/git/refs{/sha}",
                "trees_url": "https://api.github.com/repos/SamirBoulil/slub/git/trees{/sha}",
                "statuses_url": "https://api.github.com/repos/SamirBoulil/slub/statuses/{sha}",
                "languages_url": "https://api.github.com/repos/SamirBoulil/slub/languages",
                "stargazers_url": "https://api.github.com/repos/SamirBoulil/slub/stargazers",
                "contributors_url": "https://api.github.com/repos/SamirBoulil/slub/contributors",
                "subscribers_url": "https://api.github.com/repos/SamirBoulil/slub/subscribers",
                "subscription_url": "https://api.github.com/repos/SamirBoulil/slub/subscription",
                "commits_url": "https://api.github.com/repos/SamirBoulil/slub/commits{/sha}",
                "git_commits_url": "https://api.github.com/repos/SamirBoulil/slub/git/commits{/sha}",
                "comments_url": "https://api.github.com/repos/SamirBoulil/slub/comments{/number}",
                "issue_comment_url": "https://api.github.com/repos/SamirBoulil/slub/issues/comments{/number}",
                "contents_url": "https://api.github.com/repos/SamirBoulil/slub/contents/{+path}",
                "compare_url": "https://api.github.com/repos/SamirBoulil/slub/compare/{base}...{head}",
                "merges_url": "https://api.github.com/repos/SamirBoulil/slub/merges",
                "archive_url": "https://api.github.com/repos/SamirBoulil/slub/{archive_format}{/ref}",
                "downloads_url": "https://api.github.com/repos/SamirBoulil/slub/downloads",
                "issues_url": "https://api.github.com/repos/SamirBoulil/slub/issues{/number}",
                "pulls_url": "https://api.github.com/repos/SamirBoulil/slub/pulls{/number}",
                "milestones_url": "https://api.github.com/repos/SamirBoulil/slub/milestones{/number}",
                "notifications_url": "https://api.github.com/repos/SamirBoulil/slub/notifications{?since,all,participating}",
                "labels_url": "https://api.github.com/repos/SamirBoulil/slub/labels{/name}",
                "releases_url": "https://api.github.com/repos/SamirBoulil/slub/releases{/id}",
                "deployments_url": "https://api.github.com/repos/SamirBoulil/slub/deployments",
                "created_at": "2019-01-17T20:21:39Z",
                "updated_at": "2019-02-24T11:35:21Z",
                "pushed_at": "2019-02-24T12:33:25Z",
                "git_url": "git://github.com/SamirBoulil/slub.git",
                "ssh_url": "git@github.com:SamirBoulil/slub.git",
                "clone_url": "https://github.com/SamirBoulil/slub.git",
                "svn_url": "https://github.com/SamirBoulil/slub",
                "homepage": "",
                "size": 211,
                "stargazers_count": 0,
                "watchers_count": 0,
                "language": "PHP",
                "has_issues": true,
                "has_projects": true,
                "has_downloads": true,
                "has_wiki": true,
                "has_pages": false,
                "forks_count": 0,
                "mirror_url": null,
                "archived": false,
                "open_issues_count": 1,
                "license": {
                    "key": "mit",
                    "name": "MIT License",
                    "spdx_id": "MIT",
                    "url": "https://api.github.com/licenses/mit",
                    "node_id": "MDc6TGljZW5zZTEz"
                },
                "forks": 0,
                "open_issues": 1,
                "watchers": 0,
                "default_branch": "master"
            }
        },
        "_links": {
            "self": {
                "href": "https://api.github.com/repos/SamirBoulil/slub/pulls/10"
            },
            "html": {
                "href": "https://github.com/SamirBoulil/slub/pull/10"
            },
            "issue": {
                "href": "https://api.github.com/repos/SamirBoulil/slub/issues/10"
            },
            "comments": {
                "href": "https://api.github.com/repos/SamirBoulil/slub/issues/10/comments"
            },
            "review_comments": {
                "href": "https://api.github.com/repos/SamirBoulil/slub/pulls/10/comments"
            },
            "review_comment": {
                "href": "https://api.github.com/repos/SamirBoulil/slub/pulls/comments{/number}"
            },
            "commits": {
                "href": "https://api.github.com/repos/SamirBoulil/slub/pulls/10/commits"
            },
            "statuses": {
                "href": "https://api.github.com/repos/SamirBoulil/slub/statuses/caed81fdd51f573d7235fb63959ef6a6d0aaf809"
            }
        },
        "author_association": "OWNER"
    },
    "repository": {
        "id": 166291213,
        "node_id": "MDEwOlJlcG9zaXRvcnkxNjYyOTEyMTM=",
        "name": "slub",
        "full_name": "SamirBoulil/slub",
        "private": false,
        "owner": {
            "login": "SamirBoulil",
            "id": 1826473,
            "node_id": "MDQ6VXNlcjE4MjY0NzM=",
            "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
            "gravatar_id": "",
            "url": "https://api.github.com/users/SamirBoulil",
            "html_url": "https://github.com/SamirBoulil",
            "followers_url": "https://api.github.com/users/SamirBoulil/followers",
            "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
            "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
            "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
            "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
            "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
            "repos_url": "https://api.github.com/users/SamirBoulil/repos",
            "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
            "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
            "type": "User",
            "site_admin": false
        },
        "html_url": "https://github.com/SamirBoulil/slub",
        "description": "Improve the feedback loop between Github pull requests statuses and teams using Slack.",
        "fork": false,
        "url": "https://api.github.com/repos/SamirBoulil/slub",
        "forks_url": "https://api.github.com/repos/SamirBoulil/slub/forks",
        "keys_url": "https://api.github.com/repos/SamirBoulil/slub/keys{/key_id}",
        "collaborators_url": "https://api.github.com/repos/SamirBoulil/slub/collaborators{/collaborator}",
        "teams_url": "https://api.github.com/repos/SamirBoulil/slub/teams",
        "hooks_url": "https://api.github.com/repos/SamirBoulil/slub/hooks",
        "issue_events_url": "https://api.github.com/repos/SamirBoulil/slub/issues/events{/number}",
        "events_url": "https://api.github.com/repos/SamirBoulil/slub/events",
        "assignees_url": "https://api.github.com/repos/SamirBoulil/slub/assignees{/user}",
        "branches_url": "https://api.github.com/repos/SamirBoulil/slub/branches{/branch}",
        "tags_url": "https://api.github.com/repos/SamirBoulil/slub/tags",
        "blobs_url": "https://api.github.com/repos/SamirBoulil/slub/git/blobs{/sha}",
        "git_tags_url": "https://api.github.com/repos/SamirBoulil/slub/git/tags{/sha}",
        "git_refs_url": "https://api.github.com/repos/SamirBoulil/slub/git/refs{/sha}",
        "trees_url": "https://api.github.com/repos/SamirBoulil/slub/git/trees{/sha}",
        "statuses_url": "https://api.github.com/repos/SamirBoulil/slub/statuses/{sha}",
        "languages_url": "https://api.github.com/repos/SamirBoulil/slub/languages",
        "stargazers_url": "https://api.github.com/repos/SamirBoulil/slub/stargazers",
        "contributors_url": "https://api.github.com/repos/SamirBoulil/slub/contributors",
        "subscribers_url": "https://api.github.com/repos/SamirBoulil/slub/subscribers",
        "subscription_url": "https://api.github.com/repos/SamirBoulil/slub/subscription",
        "commits_url": "https://api.github.com/repos/SamirBoulil/slub/commits{/sha}",
        "git_commits_url": "https://api.github.com/repos/SamirBoulil/slub/git/commits{/sha}",
        "comments_url": "https://api.github.com/repos/SamirBoulil/slub/comments{/number}",
        "issue_comment_url": "https://api.github.com/repos/SamirBoulil/slub/issues/comments{/number}",
        "contents_url": "https://api.github.com/repos/SamirBoulil/slub/contents/{+path}",
        "compare_url": "https://api.github.com/repos/SamirBoulil/slub/compare/{base}...{head}",
        "merges_url": "https://api.github.com/repos/SamirBoulil/slub/merges",
        "archive_url": "https://api.github.com/repos/SamirBoulil/slub/{archive_format}{/ref}",
        "downloads_url": "https://api.github.com/repos/SamirBoulil/slub/downloads",
        "issues_url": "https://api.github.com/repos/SamirBoulil/slub/issues{/number}",
        "pulls_url": "https://api.github.com/repos/SamirBoulil/slub/pulls{/number}",
        "milestones_url": "https://api.github.com/repos/SamirBoulil/slub/milestones{/number}",
        "notifications_url": "https://api.github.com/repos/SamirBoulil/slub/notifications{?since,all,participating}",
        "labels_url": "https://api.github.com/repos/SamirBoulil/slub/labels{/name}",
        "releases_url": "https://api.github.com/repos/SamirBoulil/slub/releases{/id}",
        "deployments_url": "https://api.github.com/repos/SamirBoulil/slub/deployments",
        "created_at": "2019-01-17T20:21:39Z",
        "updated_at": "2019-02-24T11:35:21Z",
        "pushed_at": "2019-02-24T12:33:25Z",
        "git_url": "git://github.com/SamirBoulil/slub.git",
        "ssh_url": "git@github.com:SamirBoulil/slub.git",
        "clone_url": "https://github.com/SamirBoulil/slub.git",
        "svn_url": "https://github.com/SamirBoulil/slub",
        "homepage": "",
        "size": 211,
        "stargazers_count": 0,
        "watchers_count": 0,
        "language": "PHP",
        "has_issues": true,
        "has_projects": true,
        "has_downloads": true,
        "has_wiki": true,
        "has_pages": false,
        "forks_count": 0,
        "mirror_url": null,
        "archived": false,
        "open_issues_count": 1,
        "license": {
            "key": "mit",
            "name": "MIT License",
            "spdx_id": "MIT",
            "url": "https://api.github.com/licenses/mit",
            "node_id": "MDc6TGljZW5zZTEz"
        },
        "forks": 0,
        "open_issues": 1,
        "watchers": 0,
        "default_branch": "master"
    },
    "sender": {
        "login": "SamirBoulil",
        "id": 1826473,
        "node_id": "MDQ6VXNlcjE4MjY0NzM=",
        "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
        "gravatar_id": "",
        "url": "https://api.github.com/users/SamirBoulil",
        "html_url": "https://github.com/SamirBoulil",
        "followers_url": "https://api.github.com/users/SamirBoulil/followers",
        "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
        "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
        "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
        "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
        "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
        "repos_url": "https://api.github.com/users/SamirBoulil/repos",
        "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
        "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
        "type": "User",
        "site_admin": false
    }
}
JSON;

        return $json;
    }

    private function PRRefused()
    {
        $json = <<<JSON
{
    "action": "submitted",
    "review": {
        "id": 207149777,
        "node_id": "MDE3OlB1bGxSZXF1ZXN0UmV2aWV3MjA3MTQ5Nzc3",
        "user": {
            "login": "SamirBoulil",
            "id": 11111,
            "node_id": "MDQ6VXNlcjE4MjY0NzM=",
            "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
            "gravatar_id": "",
            "url": "https://api.github.com/users/SamirBoulil",
            "html_url": "https://github.com/SamirBoulil",
            "followers_url": "https://api.github.com/users/SamirBoulil/followers",
            "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
            "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
            "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
            "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
            "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
            "repos_url": "https://api.github.com/users/SamirBoulil/repos",
            "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
            "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
            "type": "User",
            "site_admin": false
        },
        "body": "Pourquoi pas",
        "commit_id": "caed81fdd51f573d7235fb63959ef6a6d0aaf809",
        "submitted_at": "2019-02-24T12:33:40Z",
        "state": "request_changes",
        "html_url": "https://github.com/SamirBoulil/slub/pull/10#pullrequestreview-207149777",
        "pull_request_url": "https://api.github.com/repos/SamirBoulil/slub/pulls/10",
        "author_association": "OWNER",
        "_links": {
            "html": {
                "href": "https://github.com/SamirBoulil/slub/pull/10#pullrequestreview-207149777"
            },
            "pull_request": {
                "href": "https://api.github.com/repos/SamirBoulil/slub/pulls/10"
            }
        }
    },
    "pull_request": {
        "url": "https://api.github.com/repos/SamirBoulil/slub/pulls/10",
        "id": 255680940,
        "node_id": "MDExOlB1bGxSZXF1ZXN0MjU1NjgwOTQw",
        "html_url": "https://github.com/SamirBoulil/slub/pull/10",
        "diff_url": "https://github.com/SamirBoulil/slub/pull/10.diff",
        "patch_url": "https://github.com/SamirBoulil/slub/pull/10.patch",
        "issue_url": "https://api.github.com/repos/SamirBoulil/slub/issues/10",
        "number": 10,
        "state": "open",
        "locked": false,
        "title": "Add github webhook",
        "user": {
            "login": "SamirBoulil",
            "id": 1826473,
            "node_id": "MDQ6VXNlcjE4MjY0NzM=",
            "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
            "gravatar_id": "",
            "url": "https://api.github.com/users/SamirBoulil",
            "html_url": "https://github.com/SamirBoulil",
            "followers_url": "https://api.github.com/users/SamirBoulil/followers",
            "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
            "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
            "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
            "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
            "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
            "repos_url": "https://api.github.com/users/SamirBoulil/repos",
            "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
            "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
            "type": "User",
            "site_admin": false
        },
        "body": "",
        "created_at": "2019-02-24T12:33:24Z",
        "updated_at": "2019-02-24T12:33:40Z",
        "closed_at": null,
        "merged_at": null,
        "merge_commit_sha": "32ddf907072311c89ee013b4f66040a220d6b424",
        "assignee": null,
        "assignees": [],
        "requested_reviewers": [],
        "requested_teams": [],
        "labels": [],
        "milestone": null,
        "commits_url": "https://api.github.com/repos/SamirBoulil/slub/pulls/10/commits",
        "review_comments_url": "https://api.github.com/repos/SamirBoulil/slub/pulls/10/comments",
        "review_comment_url": "https://api.github.com/repos/SamirBoulil/slub/pulls/comments{/number}",
        "comments_url": "https://api.github.com/repos/SamirBoulil/slub/issues/10/comments",
        "statuses_url": "https://api.github.com/repos/SamirBoulil/slub/statuses/caed81fdd51f573d7235fb63959ef6a6d0aaf809",
        "head": {
            "label": "SamirBoulil:plug-github",
            "ref": "plug-github",
            "sha": "caed81fdd51f573d7235fb63959ef6a6d0aaf809",
            "user": {
                "login": "SamirBoulil",
                "id": 1826473,
                "node_id": "MDQ6VXNlcjE4MjY0NzM=",
                "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
                "gravatar_id": "",
                "url": "https://api.github.com/users/SamirBoulil",
                "html_url": "https://github.com/SamirBoulil",
                "followers_url": "https://api.github.com/users/SamirBoulil/followers",
                "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
                "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
                "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
                "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
                "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
                "repos_url": "https://api.github.com/users/SamirBoulil/repos",
                "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
                "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
                "type": "User",
                "site_admin": false
            },
            "repo": {
                "id": 166291213,
                "node_id": "MDEwOlJlcG9zaXRvcnkxNjYyOTEyMTM=",
                "name": "slub",
                "full_name": "SamirBoulil/slub",
                "private": false,
                "owner": {
                    "login": "SamirBoulil",
                    "id": 1826473,
                    "node_id": "MDQ6VXNlcjE4MjY0NzM=",
                    "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
                    "gravatar_id": "",
                    "url": "https://api.github.com/users/SamirBoulil",
                    "html_url": "https://github.com/SamirBoulil",
                    "followers_url": "https://api.github.com/users/SamirBoulil/followers",
                    "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
                    "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
                    "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
                    "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
                    "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
                    "repos_url": "https://api.github.com/users/SamirBoulil/repos",
                    "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
                    "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
                    "type": "User",
                    "site_admin": false
                },
                "html_url": "https://github.com/SamirBoulil/slub",
                "description": "Improve the feedback loop between Github pull requests statuses and teams using Slack.",
                "fork": false,
                "url": "https://api.github.com/repos/SamirBoulil/slub",
                "forks_url": "https://api.github.com/repos/SamirBoulil/slub/forks",
                "keys_url": "https://api.github.com/repos/SamirBoulil/slub/keys{/key_id}",
                "collaborators_url": "https://api.github.com/repos/SamirBoulil/slub/collaborators{/collaborator}",
                "teams_url": "https://api.github.com/repos/SamirBoulil/slub/teams",
                "hooks_url": "https://api.github.com/repos/SamirBoulil/slub/hooks",
                "issue_events_url": "https://api.github.com/repos/SamirBoulil/slub/issues/events{/number}",
                "events_url": "https://api.github.com/repos/SamirBoulil/slub/events",
                "assignees_url": "https://api.github.com/repos/SamirBoulil/slub/assignees{/user}",
                "branches_url": "https://api.github.com/repos/SamirBoulil/slub/branches{/branch}",
                "tags_url": "https://api.github.com/repos/SamirBoulil/slub/tags",
                "blobs_url": "https://api.github.com/repos/SamirBoulil/slub/git/blobs{/sha}",
                "git_tags_url": "https://api.github.com/repos/SamirBoulil/slub/git/tags{/sha}",
                "git_refs_url": "https://api.github.com/repos/SamirBoulil/slub/git/refs{/sha}",
                "trees_url": "https://api.github.com/repos/SamirBoulil/slub/git/trees{/sha}",
                "statuses_url": "https://api.github.com/repos/SamirBoulil/slub/statuses/{sha}",
                "languages_url": "https://api.github.com/repos/SamirBoulil/slub/languages",
                "stargazers_url": "https://api.github.com/repos/SamirBoulil/slub/stargazers",
                "contributors_url": "https://api.github.com/repos/SamirBoulil/slub/contributors",
                "subscribers_url": "https://api.github.com/repos/SamirBoulil/slub/subscribers",
                "subscription_url": "https://api.github.com/repos/SamirBoulil/slub/subscription",
                "commits_url": "https://api.github.com/repos/SamirBoulil/slub/commits{/sha}",
                "git_commits_url": "https://api.github.com/repos/SamirBoulil/slub/git/commits{/sha}",
                "comments_url": "https://api.github.com/repos/SamirBoulil/slub/comments{/number}",
                "issue_comment_url": "https://api.github.com/repos/SamirBoulil/slub/issues/comments{/number}",
                "contents_url": "https://api.github.com/repos/SamirBoulil/slub/contents/{+path}",
                "compare_url": "https://api.github.com/repos/SamirBoulil/slub/compare/{base}...{head}",
                "merges_url": "https://api.github.com/repos/SamirBoulil/slub/merges",
                "archive_url": "https://api.github.com/repos/SamirBoulil/slub/{archive_format}{/ref}",
                "downloads_url": "https://api.github.com/repos/SamirBoulil/slub/downloads",
                "issues_url": "https://api.github.com/repos/SamirBoulil/slub/issues{/number}",
                "pulls_url": "https://api.github.com/repos/SamirBoulil/slub/pulls{/number}",
                "milestones_url": "https://api.github.com/repos/SamirBoulil/slub/milestones{/number}",
                "notifications_url": "https://api.github.com/repos/SamirBoulil/slub/notifications{?since,all,participating}",
                "labels_url": "https://api.github.com/repos/SamirBoulil/slub/labels{/name}",
                "releases_url": "https://api.github.com/repos/SamirBoulil/slub/releases{/id}",
                "deployments_url": "https://api.github.com/repos/SamirBoulil/slub/deployments",
                "created_at": "2019-01-17T20:21:39Z",
                "updated_at": "2019-02-24T11:35:21Z",
                "pushed_at": "2019-02-24T12:33:25Z",
                "git_url": "git://github.com/SamirBoulil/slub.git",
                "ssh_url": "git@github.com:SamirBoulil/slub.git",
                "clone_url": "https://github.com/SamirBoulil/slub.git",
                "svn_url": "https://github.com/SamirBoulil/slub",
                "homepage": "",
                "size": 211,
                "stargazers_count": 0,
                "watchers_count": 0,
                "language": "PHP",
                "has_issues": true,
                "has_projects": true,
                "has_downloads": true,
                "has_wiki": true,
                "has_pages": false,
                "forks_count": 0,
                "mirror_url": null,
                "archived": false,
                "open_issues_count": 1,
                "license": {
                    "key": "mit",
                    "name": "MIT License",
                    "spdx_id": "MIT",
                    "url": "https://api.github.com/licenses/mit",
                    "node_id": "MDc6TGljZW5zZTEz"
                },
                "forks": 0,
                "open_issues": 1,
                "watchers": 0,
                "default_branch": "master"
            }
        },
        "base": {
            "label": "SamirBoulil:master",
            "ref": "master",
            "sha": "7a5df1cd6070a872fbc22522ceefa83c763c3f6e",
            "user": {
                "login": "SamirBoulil",
                "id": 1826473,
                "node_id": "MDQ6VXNlcjE4MjY0NzM=",
                "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
                "gravatar_id": "",
                "url": "https://api.github.com/users/SamirBoulil",
                "html_url": "https://github.com/SamirBoulil",
                "followers_url": "https://api.github.com/users/SamirBoulil/followers",
                "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
                "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
                "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
                "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
                "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
                "repos_url": "https://api.github.com/users/SamirBoulil/repos",
                "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
                "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
                "type": "User",
                "site_admin": false
            },
            "repo": {
                "id": 166291213,
                "node_id": "MDEwOlJlcG9zaXRvcnkxNjYyOTEyMTM=",
                "name": "slub",
                "full_name": "SamirBoulil/slub",
                "private": false,
                "owner": {
                    "login": "SamirBoulil",
                    "id": 1826473,
                    "node_id": "MDQ6VXNlcjE4MjY0NzM=",
                    "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
                    "gravatar_id": "",
                    "url": "https://api.github.com/users/SamirBoulil",
                    "html_url": "https://github.com/SamirBoulil",
                    "followers_url": "https://api.github.com/users/SamirBoulil/followers",
                    "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
                    "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
                    "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
                    "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
                    "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
                    "repos_url": "https://api.github.com/users/SamirBoulil/repos",
                    "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
                    "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
                    "type": "User",
                    "site_admin": false
                },
                "html_url": "https://github.com/SamirBoulil/slub",
                "description": "Improve the feedback loop between Github pull requests statuses and teams using Slack.",
                "fork": false,
                "url": "https://api.github.com/repos/SamirBoulil/slub",
                "forks_url": "https://api.github.com/repos/SamirBoulil/slub/forks",
                "keys_url": "https://api.github.com/repos/SamirBoulil/slub/keys{/key_id}",
                "collaborators_url": "https://api.github.com/repos/SamirBoulil/slub/collaborators{/collaborator}",
                "teams_url": "https://api.github.com/repos/SamirBoulil/slub/teams",
                "hooks_url": "https://api.github.com/repos/SamirBoulil/slub/hooks",
                "issue_events_url": "https://api.github.com/repos/SamirBoulil/slub/issues/events{/number}",
                "events_url": "https://api.github.com/repos/SamirBoulil/slub/events",
                "assignees_url": "https://api.github.com/repos/SamirBoulil/slub/assignees{/user}",
                "branches_url": "https://api.github.com/repos/SamirBoulil/slub/branches{/branch}",
                "tags_url": "https://api.github.com/repos/SamirBoulil/slub/tags",
                "blobs_url": "https://api.github.com/repos/SamirBoulil/slub/git/blobs{/sha}",
                "git_tags_url": "https://api.github.com/repos/SamirBoulil/slub/git/tags{/sha}",
                "git_refs_url": "https://api.github.com/repos/SamirBoulil/slub/git/refs{/sha}",
                "trees_url": "https://api.github.com/repos/SamirBoulil/slub/git/trees{/sha}",
                "statuses_url": "https://api.github.com/repos/SamirBoulil/slub/statuses/{sha}",
                "languages_url": "https://api.github.com/repos/SamirBoulil/slub/languages",
                "stargazers_url": "https://api.github.com/repos/SamirBoulil/slub/stargazers",
                "contributors_url": "https://api.github.com/repos/SamirBoulil/slub/contributors",
                "subscribers_url": "https://api.github.com/repos/SamirBoulil/slub/subscribers",
                "subscription_url": "https://api.github.com/repos/SamirBoulil/slub/subscription",
                "commits_url": "https://api.github.com/repos/SamirBoulil/slub/commits{/sha}",
                "git_commits_url": "https://api.github.com/repos/SamirBoulil/slub/git/commits{/sha}",
                "comments_url": "https://api.github.com/repos/SamirBoulil/slub/comments{/number}",
                "issue_comment_url": "https://api.github.com/repos/SamirBoulil/slub/issues/comments{/number}",
                "contents_url": "https://api.github.com/repos/SamirBoulil/slub/contents/{+path}",
                "compare_url": "https://api.github.com/repos/SamirBoulil/slub/compare/{base}...{head}",
                "merges_url": "https://api.github.com/repos/SamirBoulil/slub/merges",
                "archive_url": "https://api.github.com/repos/SamirBoulil/slub/{archive_format}{/ref}",
                "downloads_url": "https://api.github.com/repos/SamirBoulil/slub/downloads",
                "issues_url": "https://api.github.com/repos/SamirBoulil/slub/issues{/number}",
                "pulls_url": "https://api.github.com/repos/SamirBoulil/slub/pulls{/number}",
                "milestones_url": "https://api.github.com/repos/SamirBoulil/slub/milestones{/number}",
                "notifications_url": "https://api.github.com/repos/SamirBoulil/slub/notifications{?since,all,participating}",
                "labels_url": "https://api.github.com/repos/SamirBoulil/slub/labels{/name}",
                "releases_url": "https://api.github.com/repos/SamirBoulil/slub/releases{/id}",
                "deployments_url": "https://api.github.com/repos/SamirBoulil/slub/deployments",
                "created_at": "2019-01-17T20:21:39Z",
                "updated_at": "2019-02-24T11:35:21Z",
                "pushed_at": "2019-02-24T12:33:25Z",
                "git_url": "git://github.com/SamirBoulil/slub.git",
                "ssh_url": "git@github.com:SamirBoulil/slub.git",
                "clone_url": "https://github.com/SamirBoulil/slub.git",
                "svn_url": "https://github.com/SamirBoulil/slub",
                "homepage": "",
                "size": 211,
                "stargazers_count": 0,
                "watchers_count": 0,
                "language": "PHP",
                "has_issues": true,
                "has_projects": true,
                "has_downloads": true,
                "has_wiki": true,
                "has_pages": false,
                "forks_count": 0,
                "mirror_url": null,
                "archived": false,
                "open_issues_count": 1,
                "license": {
                    "key": "mit",
                    "name": "MIT License",
                    "spdx_id": "MIT",
                    "url": "https://api.github.com/licenses/mit",
                    "node_id": "MDc6TGljZW5zZTEz"
                },
                "forks": 0,
                "open_issues": 1,
                "watchers": 0,
                "default_branch": "master"
            }
        },
        "_links": {
            "self": {
                "href": "https://api.github.com/repos/SamirBoulil/slub/pulls/10"
            },
            "html": {
                "href": "https://github.com/SamirBoulil/slub/pull/10"
            },
            "issue": {
                "href": "https://api.github.com/repos/SamirBoulil/slub/issues/10"
            },
            "comments": {
                "href": "https://api.github.com/repos/SamirBoulil/slub/issues/10/comments"
            },
            "review_comments": {
                "href": "https://api.github.com/repos/SamirBoulil/slub/pulls/10/comments"
            },
            "review_comment": {
                "href": "https://api.github.com/repos/SamirBoulil/slub/pulls/comments{/number}"
            },
            "commits": {
                "href": "https://api.github.com/repos/SamirBoulil/slub/pulls/10/commits"
            },
            "statuses": {
                "href": "https://api.github.com/repos/SamirBoulil/slub/statuses/caed81fdd51f573d7235fb63959ef6a6d0aaf809"
            }
        },
        "author_association": "OWNER"
    },
    "repository": {
        "id": 166291213,
        "node_id": "MDEwOlJlcG9zaXRvcnkxNjYyOTEyMTM=",
        "name": "slub",
        "full_name": "SamirBoulil/slub",
        "private": false,
        "owner": {
            "login": "SamirBoulil",
            "id": 1826473,
            "node_id": "MDQ6VXNlcjE4MjY0NzM=",
            "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
            "gravatar_id": "",
            "url": "https://api.github.com/users/SamirBoulil",
            "html_url": "https://github.com/SamirBoulil",
            "followers_url": "https://api.github.com/users/SamirBoulil/followers",
            "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
            "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
            "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
            "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
            "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
            "repos_url": "https://api.github.com/users/SamirBoulil/repos",
            "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
            "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
            "type": "User",
            "site_admin": false
        },
        "html_url": "https://github.com/SamirBoulil/slub",
        "description": "Improve the feedback loop between Github pull requests statuses and teams using Slack.",
        "fork": false,
        "url": "https://api.github.com/repos/SamirBoulil/slub",
        "forks_url": "https://api.github.com/repos/SamirBoulil/slub/forks",
        "keys_url": "https://api.github.com/repos/SamirBoulil/slub/keys{/key_id}",
        "collaborators_url": "https://api.github.com/repos/SamirBoulil/slub/collaborators{/collaborator}",
        "teams_url": "https://api.github.com/repos/SamirBoulil/slub/teams",
        "hooks_url": "https://api.github.com/repos/SamirBoulil/slub/hooks",
        "issue_events_url": "https://api.github.com/repos/SamirBoulil/slub/issues/events{/number}",
        "events_url": "https://api.github.com/repos/SamirBoulil/slub/events",
        "assignees_url": "https://api.github.com/repos/SamirBoulil/slub/assignees{/user}",
        "branches_url": "https://api.github.com/repos/SamirBoulil/slub/branches{/branch}",
        "tags_url": "https://api.github.com/repos/SamirBoulil/slub/tags",
        "blobs_url": "https://api.github.com/repos/SamirBoulil/slub/git/blobs{/sha}",
        "git_tags_url": "https://api.github.com/repos/SamirBoulil/slub/git/tags{/sha}",
        "git_refs_url": "https://api.github.com/repos/SamirBoulil/slub/git/refs{/sha}",
        "trees_url": "https://api.github.com/repos/SamirBoulil/slub/git/trees{/sha}",
        "statuses_url": "https://api.github.com/repos/SamirBoulil/slub/statuses/{sha}",
        "languages_url": "https://api.github.com/repos/SamirBoulil/slub/languages",
        "stargazers_url": "https://api.github.com/repos/SamirBoulil/slub/stargazers",
        "contributors_url": "https://api.github.com/repos/SamirBoulil/slub/contributors",
        "subscribers_url": "https://api.github.com/repos/SamirBoulil/slub/subscribers",
        "subscription_url": "https://api.github.com/repos/SamirBoulil/slub/subscription",
        "commits_url": "https://api.github.com/repos/SamirBoulil/slub/commits{/sha}",
        "git_commits_url": "https://api.github.com/repos/SamirBoulil/slub/git/commits{/sha}",
        "comments_url": "https://api.github.com/repos/SamirBoulil/slub/comments{/number}",
        "issue_comment_url": "https://api.github.com/repos/SamirBoulil/slub/issues/comments{/number}",
        "contents_url": "https://api.github.com/repos/SamirBoulil/slub/contents/{+path}",
        "compare_url": "https://api.github.com/repos/SamirBoulil/slub/compare/{base}...{head}",
        "merges_url": "https://api.github.com/repos/SamirBoulil/slub/merges",
        "archive_url": "https://api.github.com/repos/SamirBoulil/slub/{archive_format}{/ref}",
        "downloads_url": "https://api.github.com/repos/SamirBoulil/slub/downloads",
        "issues_url": "https://api.github.com/repos/SamirBoulil/slub/issues{/number}",
        "pulls_url": "https://api.github.com/repos/SamirBoulil/slub/pulls{/number}",
        "milestones_url": "https://api.github.com/repos/SamirBoulil/slub/milestones{/number}",
        "notifications_url": "https://api.github.com/repos/SamirBoulil/slub/notifications{?since,all,participating}",
        "labels_url": "https://api.github.com/repos/SamirBoulil/slub/labels{/name}",
        "releases_url": "https://api.github.com/repos/SamirBoulil/slub/releases{/id}",
        "deployments_url": "https://api.github.com/repos/SamirBoulil/slub/deployments",
        "created_at": "2019-01-17T20:21:39Z",
        "updated_at": "2019-02-24T11:35:21Z",
        "pushed_at": "2019-02-24T12:33:25Z",
        "git_url": "git://github.com/SamirBoulil/slub.git",
        "ssh_url": "git@github.com:SamirBoulil/slub.git",
        "clone_url": "https://github.com/SamirBoulil/slub.git",
        "svn_url": "https://github.com/SamirBoulil/slub",
        "homepage": "",
        "size": 211,
        "stargazers_count": 0,
        "watchers_count": 0,
        "language": "PHP",
        "has_issues": true,
        "has_projects": true,
        "has_downloads": true,
        "has_wiki": true,
        "has_pages": false,
        "forks_count": 0,
        "mirror_url": null,
        "archived": false,
        "open_issues_count": 1,
        "license": {
            "key": "mit",
            "name": "MIT License",
            "spdx_id": "MIT",
            "url": "https://api.github.com/licenses/mit",
            "node_id": "MDc6TGljZW5zZTEz"
        },
        "forks": 0,
        "open_issues": 1,
        "watchers": 0,
        "default_branch": "master"
    },
    "sender": {
        "login": "SamirBoulil",
        "id": 1826473,
        "node_id": "MDQ6VXNlcjE4MjY0NzM=",
        "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
        "gravatar_id": "",
        "url": "https://api.github.com/users/SamirBoulil",
        "html_url": "https://github.com/SamirBoulil",
        "followers_url": "https://api.github.com/users/SamirBoulil/followers",
        "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
        "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
        "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
        "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
        "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
        "repos_url": "https://api.github.com/users/SamirBoulil/repos",
        "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
        "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
        "type": "User",
        "site_admin": false
    }
}
JSON;

        return $json;
    }

    private function PRCommented(): string
    {
        $json = <<<JSON
{
  "action": "submitted",
  "review": {
    "id": 213374685,
    "node_id": "MDE3OlB1bGxSZXF1ZXN0UmV2aWV3MjEzMzc0Njg1",
    "user": {
      "login": "SamirBoulil",
      "id": 111111,
      "node_id": "MDQ6VXNlcjE4MjY0NzM=",
      "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
      "gravatar_id": "",
      "url": "https://api.github.com/users/SamirBoulil",
      "html_url": "https://github.com/SamirBoulil",
      "followers_url": "https://api.github.com/users/SamirBoulil/followers",
      "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
      "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
      "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
      "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
      "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
      "repos_url": "https://api.github.com/users/SamirBoulil/repos",
      "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
      "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
      "type": "User",
      "site_admin": false
    },
    "body": "qsd",
    "commit_id": "5a0abb69cdc1765c07e01df89a1192537eedf723",
    "submitted_at": "2019-03-12T13:06:14Z",
    "state": "commented",
    "html_url": "https://github.com/SamirBoulil/slub/pull/10#pullrequestreview-213374685",
    "pull_request_url": "https://api.github.com/repos/SamirBoulil/slub/pull/10",
    "author_association": "OWNER",
    "_links": {
      "html": {
        "href": "https://github.com/SamirBoulil/slub/pull/10#pullrequestreview-213374685"
      },
      "pull_request": {
        "href": "https://api.github.com/repos/SamirBoulil/slub/pull/10"
      }
    }
  },
  "pull_request": {
    "url": "https://api.github.com/repos/SamirBoulil/slub/pull/10",
    "id": 260346636,
    "node_id": "MDExOlB1bGxSZXF1ZXN0MjYwMzQ2NjM2",
    "html_url": "https://github.com/SamirBoulil/slub/pull/10",
    "diff_url": "https://github.com/SamirBoulil/slub/pull/10.diff",
    "patch_url": "https://github.com/SamirBoulil/slub/pull/10.patch",
    "issue_url": "https://api.github.com/repos/SamirBoulil/slub/issues/10",
    "number": 10,
    "state": "open",
    "locked": false,
    "title": "Stupid test",
    "user": {
      "login": "SamirBoulil",
      "id": 1826473,
      "node_id": "MDQ6VXNlcjE4MjY0NzM=",
      "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
      "gravatar_id": "",
      "url": "https://api.github.com/users/SamirBoulil",
      "html_url": "https://github.com/SamirBoulil",
      "followers_url": "https://api.github.com/users/SamirBoulil/followers",
      "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
      "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
      "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
      "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
      "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
      "repos_url": "https://api.github.com/users/SamirBoulil/repos",
      "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
      "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
      "type": "User",
      "site_admin": false
    },
    "body": "",
    "created_at": "2019-03-12T12:44:12Z",
    "updated_at": "2019-03-12T13:06:14Z",
    "closed_at": null,
    "merged_at": null,
    "merge_commit_sha": "36668c6a652d94514aeb28585e06e78be86f7228",
    "assignee": null,
    "assignees": [

    ],
    "requested_reviewers": [

    ],
    "requested_teams": [

    ],
    "labels": [

    ],
    "milestone": null,
    "commits_url": "https://api.github.com/repos/SamirBoulil/slub/pull/10/commits",
    "review_comments_url": "https://api.github.com/repos/SamirBoulil/slub/pull/10/comments",
    "review_comment_url": "https://api.github.com/repos/SamirBoulil/slub/pulls/comments{/number}",
    "comments_url": "https://api.github.com/repos/SamirBoulil/slub/issues/10/comments",
    "statuses_url": "https://api.github.com/repos/SamirBoulil/slub/statuses/5a0abb69cdc1765c07e01df89a1192537eedf723",
    "head": {
      "label": "SamirBoulil:test-3",
      "ref": "test-3",
      "sha": "5a0abb69cdc1765c07e01df89a1192537eedf723",
      "user": {
        "login": "SamirBoulil",
        "id": 1826473,
        "node_id": "MDQ6VXNlcjE4MjY0NzM=",
        "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
        "gravatar_id": "",
        "url": "https://api.github.com/users/SamirBoulil",
        "html_url": "https://github.com/SamirBoulil",
        "followers_url": "https://api.github.com/users/SamirBoulil/followers",
        "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
        "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
        "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
        "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
        "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
        "repos_url": "https://api.github.com/users/SamirBoulil/repos",
        "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
        "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
        "type": "User",
        "site_admin": false
      },
      "repo": {
        "id": 166291213,
        "node_id": "MDEwOlJlcG9zaXRvcnkxNjYyOTEyMTM=",
        "name": "slub",
        "full_name": "SamirBoulil/slub",
        "private": false,
        "owner": {
          "login": "SamirBoulil",
          "id": 1826473,
          "node_id": "MDQ6VXNlcjE4MjY0NzM=",
          "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
          "gravatar_id": "",
          "url": "https://api.github.com/users/SamirBoulil",
          "html_url": "https://github.com/SamirBoulil",
          "followers_url": "https://api.github.com/users/SamirBoulil/followers",
          "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
          "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
          "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
          "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
          "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
          "repos_url": "https://api.github.com/users/SamirBoulil/repos",
          "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
          "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
          "type": "User",
          "site_admin": false
        },
        "html_url": "https://github.com/SamirBoulil/slub",
        "description": "Improve the feedback loop between Github pull requests statuses and teams using Slack.",
        "fork": false,
        "url": "https://api.github.com/repos/SamirBoulil/slub",
        "forks_url": "https://api.github.com/repos/SamirBoulil/slub/forks",
        "keys_url": "https://api.github.com/repos/SamirBoulil/slub/keys{/key_id}",
        "collaborators_url": "https://api.github.com/repos/SamirBoulil/slub/collaborators{/collaborator}",
        "teams_url": "https://api.github.com/repos/SamirBoulil/slub/teams",
        "hooks_url": "https://api.github.com/repos/SamirBoulil/slub/hooks",
        "issue_events_url": "https://api.github.com/repos/SamirBoulil/slub/issues/events{/number}",
        "events_url": "https://api.github.com/repos/SamirBoulil/slub/events",
        "assignees_url": "https://api.github.com/repos/SamirBoulil/slub/assignees{/user}",
        "branches_url": "https://api.github.com/repos/SamirBoulil/slub/branches{/branch}",
        "tags_url": "https://api.github.com/repos/SamirBoulil/slub/tags",
        "blobs_url": "https://api.github.com/repos/SamirBoulil/slub/git/blobs{/sha}",
        "git_tags_url": "https://api.github.com/repos/SamirBoulil/slub/git/tags{/sha}",
        "git_refs_url": "https://api.github.com/repos/SamirBoulil/slub/git/refs{/sha}",
        "trees_url": "https://api.github.com/repos/SamirBoulil/slub/git/trees{/sha}",
        "statuses_url": "https://api.github.com/repos/SamirBoulil/slub/statuses/{sha}",
        "languages_url": "https://api.github.com/repos/SamirBoulil/slub/languages",
        "stargazers_url": "https://api.github.com/repos/SamirBoulil/slub/stargazers",
        "contributors_url": "https://api.github.com/repos/SamirBoulil/slub/contributors",
        "subscribers_url": "https://api.github.com/repos/SamirBoulil/slub/subscribers",
        "subscription_url": "https://api.github.com/repos/SamirBoulil/slub/subscription",
        "commits_url": "https://api.github.com/repos/SamirBoulil/slub/commits{/sha}",
        "git_commits_url": "https://api.github.com/repos/SamirBoulil/slub/git/commits{/sha}",
        "comments_url": "https://api.github.com/repos/SamirBoulil/slub/comments{/number}",
        "issue_comment_url": "https://api.github.com/repos/SamirBoulil/slub/issues/comments{/number}",
        "contents_url": "https://api.github.com/repos/SamirBoulil/slub/contents/{+path}",
        "compare_url": "https://api.github.com/repos/SamirBoulil/slub/compare/{base}...{head}",
        "merges_url": "https://api.github.com/repos/SamirBoulil/slub/merges",
        "archive_url": "https://api.github.com/repos/SamirBoulil/slub/{archive_format}{/ref}",
        "downloads_url": "https://api.github.com/repos/SamirBoulil/slub/downloads",
        "issues_url": "https://api.github.com/repos/SamirBoulil/slub/issues{/number}",
        "pulls_url": "https://api.github.com/repos/SamirBoulil/slub/pulls{/number}",
        "milestones_url": "https://api.github.com/repos/SamirBoulil/slub/milestones{/number}",
        "notifications_url": "https://api.github.com/repos/SamirBoulil/slub/notifications{?since,all,participating}",
        "labels_url": "https://api.github.com/repos/SamirBoulil/slub/labels{/name}",
        "releases_url": "https://api.github.com/repos/SamirBoulil/slub/releases{/id}",
        "deployments_url": "https://api.github.com/repos/SamirBoulil/slub/deployments",
        "created_at": "2019-01-17T20:21:39Z",
        "updated_at": "2019-03-12T12:35:40Z",
        "pushed_at": "2019-03-12T12:44:12Z",
        "git_url": "git://github.com/SamirBoulil/slub.git",
        "ssh_url": "git@github.com:SamirBoulil/slub.git",
        "clone_url": "https://github.com/SamirBoulil/slub.git",
        "svn_url": "https://github.com/SamirBoulil/slub",
        "homepage": "",
        "size": 358,
        "stargazers_count": 0,
        "watchers_count": 0,
        "language": "PHP",
        "has_issues": true,
        "has_projects": true,
        "has_downloads": true,
        "has_wiki": true,
        "has_pages": false,
        "forks_count": 0,
        "mirror_url": null,
        "archived": false,
        "open_issues_count": 2,
        "license": {
          "key": "mit",
          "name": "MIT License",
          "spdx_id": "MIT",
          "url": "https://api.github.com/licenses/mit",
          "node_id": "MDc6TGljZW5zZTEz"
        },
        "forks": 0,
        "open_issues": 2,
        "watchers": 0,
        "default_branch": "master"
      }
    },
    "base": {
      "label": "SamirBoulil:master",
      "ref": "master",
      "sha": "84ac8f0ba3bb748caed6c6eecaeeb5e55e3db25a",
      "user": {
        "login": "SamirBoulil",
        "id": 1826473,
        "node_id": "MDQ6VXNlcjE4MjY0NzM=",
        "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
        "gravatar_id": "",
        "url": "https://api.github.com/users/SamirBoulil",
        "html_url": "https://github.com/SamirBoulil",
        "followers_url": "https://api.github.com/users/SamirBoulil/followers",
        "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
        "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
        "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
        "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
        "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
        "repos_url": "https://api.github.com/users/SamirBoulil/repos",
        "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
        "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
        "type": "User",
        "site_admin": false
      },
      "repo": {
        "id": 166291213,
        "node_id": "MDEwOlJlcG9zaXRvcnkxNjYyOTEyMTM=",
        "name": "slub",
        "full_name": "SamirBoulil/slub",
        "private": false,
        "owner": {
          "login": "SamirBoulil",
          "id": 1826473,
          "node_id": "MDQ6VXNlcjE4MjY0NzM=",
          "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
          "gravatar_id": "",
          "url": "https://api.github.com/users/SamirBoulil",
          "html_url": "https://github.com/SamirBoulil",
          "followers_url": "https://api.github.com/users/SamirBoulil/followers",
          "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
          "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
          "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
          "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
          "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
          "repos_url": "https://api.github.com/users/SamirBoulil/repos",
          "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
          "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
          "type": "User",
          "site_admin": false
        },
        "html_url": "https://github.com/SamirBoulil/slub",
        "description": "Improve the feedback loop between Github pull requests statuses and teams using Slack.",
        "fork": false,
        "url": "https://api.github.com/repos/SamirBoulil/slub",
        "forks_url": "https://api.github.com/repos/SamirBoulil/slub/forks",
        "keys_url": "https://api.github.com/repos/SamirBoulil/slub/keys{/key_id}",
        "collaborators_url": "https://api.github.com/repos/SamirBoulil/slub/collaborators{/collaborator}",
        "teams_url": "https://api.github.com/repos/SamirBoulil/slub/teams",
        "hooks_url": "https://api.github.com/repos/SamirBoulil/slub/hooks",
        "issue_events_url": "https://api.github.com/repos/SamirBoulil/slub/issues/events{/number}",
        "events_url": "https://api.github.com/repos/SamirBoulil/slub/events",
        "assignees_url": "https://api.github.com/repos/SamirBoulil/slub/assignees{/user}",
        "branches_url": "https://api.github.com/repos/SamirBoulil/slub/branches{/branch}",
        "tags_url": "https://api.github.com/repos/SamirBoulil/slub/tags",
        "blobs_url": "https://api.github.com/repos/SamirBoulil/slub/git/blobs{/sha}",
        "git_tags_url": "https://api.github.com/repos/SamirBoulil/slub/git/tags{/sha}",
        "git_refs_url": "https://api.github.com/repos/SamirBoulil/slub/git/refs{/sha}",
        "trees_url": "https://api.github.com/repos/SamirBoulil/slub/git/trees{/sha}",
        "statuses_url": "https://api.github.com/repos/SamirBoulil/slub/statuses/{sha}",
        "languages_url": "https://api.github.com/repos/SamirBoulil/slub/languages",
        "stargazers_url": "https://api.github.com/repos/SamirBoulil/slub/stargazers",
        "contributors_url": "https://api.github.com/repos/SamirBoulil/slub/contributors",
        "subscribers_url": "https://api.github.com/repos/SamirBoulil/slub/subscribers",
        "subscription_url": "https://api.github.com/repos/SamirBoulil/slub/subscription",
        "commits_url": "https://api.github.com/repos/SamirBoulil/slub/commits{/sha}",
        "git_commits_url": "https://api.github.com/repos/SamirBoulil/slub/git/commits{/sha}",
        "comments_url": "https://api.github.com/repos/SamirBoulil/slub/comments{/number}",
        "issue_comment_url": "https://api.github.com/repos/SamirBoulil/slub/issues/comments{/number}",
        "contents_url": "https://api.github.com/repos/SamirBoulil/slub/contents/{+path}",
        "compare_url": "https://api.github.com/repos/SamirBoulil/slub/compare/{base}...{head}",
        "merges_url": "https://api.github.com/repos/SamirBoulil/slub/merges",
        "archive_url": "https://api.github.com/repos/SamirBoulil/slub/{archive_format}{/ref}",
        "downloads_url": "https://api.github.com/repos/SamirBoulil/slub/downloads",
        "issues_url": "https://api.github.com/repos/SamirBoulil/slub/issues{/number}",
        "pulls_url": "https://api.github.com/repos/SamirBoulil/slub/pulls{/number}",
        "milestones_url": "https://api.github.com/repos/SamirBoulil/slub/milestones{/number}",
        "notifications_url": "https://api.github.com/repos/SamirBoulil/slub/notifications{?since,all,participating}",
        "labels_url": "https://api.github.com/repos/SamirBoulil/slub/labels{/name}",
        "releases_url": "https://api.github.com/repos/SamirBoulil/slub/releases{/id}",
        "deployments_url": "https://api.github.com/repos/SamirBoulil/slub/deployments",
        "created_at": "2019-01-17T20:21:39Z",
        "updated_at": "2019-03-12T12:35:40Z",
        "pushed_at": "2019-03-12T12:44:12Z",
        "git_url": "git://github.com/SamirBoulil/slub.git",
        "ssh_url": "git@github.com:SamirBoulil/slub.git",
        "clone_url": "https://github.com/SamirBoulil/slub.git",
        "svn_url": "https://github.com/SamirBoulil/slub",
        "homepage": "",
        "size": 358,
        "stargazers_count": 0,
        "watchers_count": 0,
        "language": "PHP",
        "has_issues": true,
        "has_projects": true,
        "has_downloads": true,
        "has_wiki": true,
        "has_pages": false,
        "forks_count": 0,
        "mirror_url": null,
        "archived": false,
        "open_issues_count": 2,
        "license": {
          "key": "mit",
          "name": "MIT License",
          "spdx_id": "MIT",
          "url": "https://api.github.com/licenses/mit",
          "node_id": "MDc6TGljZW5zZTEz"
        },
        "forks": 0,
        "open_issues": 2,
        "watchers": 0,
        "default_branch": "master"
      }
    },
    "_links": {
      "self": {
        "href": "https://api.github.com/repos/SamirBoulil/slub/pull/10"
      },
      "html": {
        "href": "https://github.com/SamirBoulil/slub/pull/10"
      },
      "issue": {
        "href": "https://api.github.com/repos/SamirBoulil/slub/issues/10"
      },
      "comments": {
        "href": "https://api.github.com/repos/SamirBoulil/slub/issues/10/comments"
      },
      "review_comments": {
        "href": "https://api.github.com/repos/SamirBoulil/slub/pull/10/comments"
      },
      "review_comment": {
        "href": "https://api.github.com/repos/SamirBoulil/slub/pulls/comments{/number}"
      },
      "commits": {
        "href": "https://api.github.com/repos/SamirBoulil/slub/pull/10/commits"
      },
      "statuses": {
        "href": "https://api.github.com/repos/SamirBoulil/slub/statuses/5a0abb69cdc1765c07e01df89a1192537eedf723"
      }
    },
    "author_association": "OWNER"
  },
  "repository": {
    "id": 166291213,
    "node_id": "MDEwOlJlcG9zaXRvcnkxNjYyOTEyMTM=",
    "name": "slub",
    "full_name": "SamirBoulil/slub",
    "private": false,
    "owner": {
      "login": "SamirBoulil",
      "id": 1826473,
      "node_id": "MDQ6VXNlcjE4MjY0NzM=",
      "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
      "gravatar_id": "",
      "url": "https://api.github.com/users/SamirBoulil",
      "html_url": "https://github.com/SamirBoulil",
      "followers_url": "https://api.github.com/users/SamirBoulil/followers",
      "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
      "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
      "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
      "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
      "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
      "repos_url": "https://api.github.com/users/SamirBoulil/repos",
      "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
      "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
      "type": "User",
      "site_admin": false
    },
    "html_url": "https://github.com/SamirBoulil/slub",
    "description": "Improve the feedback loop between Github pull requests statuses and teams using Slack.",
    "fork": false,
    "url": "https://api.github.com/repos/SamirBoulil/slub",
    "forks_url": "https://api.github.com/repos/SamirBoulil/slub/forks",
    "keys_url": "https://api.github.com/repos/SamirBoulil/slub/keys{/key_id}",
    "collaborators_url": "https://api.github.com/repos/SamirBoulil/slub/collaborators{/collaborator}",
    "teams_url": "https://api.github.com/repos/SamirBoulil/slub/teams",
    "hooks_url": "https://api.github.com/repos/SamirBoulil/slub/hooks",
    "issue_events_url": "https://api.github.com/repos/SamirBoulil/slub/issues/events{/number}",
    "events_url": "https://api.github.com/repos/SamirBoulil/slub/events",
    "assignees_url": "https://api.github.com/repos/SamirBoulil/slub/assignees{/user}",
    "branches_url": "https://api.github.com/repos/SamirBoulil/slub/branches{/branch}",
    "tags_url": "https://api.github.com/repos/SamirBoulil/slub/tags",
    "blobs_url": "https://api.github.com/repos/SamirBoulil/slub/git/blobs{/sha}",
    "git_tags_url": "https://api.github.com/repos/SamirBoulil/slub/git/tags{/sha}",
    "git_refs_url": "https://api.github.com/repos/SamirBoulil/slub/git/refs{/sha}",
    "trees_url": "https://api.github.com/repos/SamirBoulil/slub/git/trees{/sha}",
    "statuses_url": "https://api.github.com/repos/SamirBoulil/slub/statuses/{sha}",
    "languages_url": "https://api.github.com/repos/SamirBoulil/slub/languages",
    "stargazers_url": "https://api.github.com/repos/SamirBoulil/slub/stargazers",
    "contributors_url": "https://api.github.com/repos/SamirBoulil/slub/contributors",
    "subscribers_url": "https://api.github.com/repos/SamirBoulil/slub/subscribers",
    "subscription_url": "https://api.github.com/repos/SamirBoulil/slub/subscription",
    "commits_url": "https://api.github.com/repos/SamirBoulil/slub/commits{/sha}",
    "git_commits_url": "https://api.github.com/repos/SamirBoulil/slub/git/commits{/sha}",
    "comments_url": "https://api.github.com/repos/SamirBoulil/slub/comments{/number}",
    "issue_comment_url": "https://api.github.com/repos/SamirBoulil/slub/issues/comments{/number}",
    "contents_url": "https://api.github.com/repos/SamirBoulil/slub/contents/{+path}",
    "compare_url": "https://api.github.com/repos/SamirBoulil/slub/compare/{base}...{head}",
    "merges_url": "https://api.github.com/repos/SamirBoulil/slub/merges",
    "archive_url": "https://api.github.com/repos/SamirBoulil/slub/{archive_format}{/ref}",
    "downloads_url": "https://api.github.com/repos/SamirBoulil/slub/downloads",
    "issues_url": "https://api.github.com/repos/SamirBoulil/slub/issues{/number}",
    "pulls_url": "https://api.github.com/repos/SamirBoulil/slub/pulls{/number}",
    "milestones_url": "https://api.github.com/repos/SamirBoulil/slub/milestones{/number}",
    "notifications_url": "https://api.github.com/repos/SamirBoulil/slub/notifications{?since,all,participating}",
    "labels_url": "https://api.github.com/repos/SamirBoulil/slub/labels{/name}",
    "releases_url": "https://api.github.com/repos/SamirBoulil/slub/releases{/id}",
    "deployments_url": "https://api.github.com/repos/SamirBoulil/slub/deployments",
    "created_at": "2019-01-17T20:21:39Z",
    "updated_at": "2019-03-12T12:35:40Z",
    "pushed_at": "2019-03-12T12:44:12Z",
    "git_url": "git://github.com/SamirBoulil/slub.git",
    "ssh_url": "git@github.com:SamirBoulil/slub.git",
    "clone_url": "https://github.com/SamirBoulil/slub.git",
    "svn_url": "https://github.com/SamirBoulil/slub",
    "homepage": "",
    "size": 358,
    "stargazers_count": 0,
    "watchers_count": 0,
    "language": "PHP",
    "has_issues": true,
    "has_projects": true,
    "has_downloads": true,
    "has_wiki": true,
    "has_pages": false,
    "forks_count": 0,
    "mirror_url": null,
    "archived": false,
    "open_issues_count": 2,
    "license": {
      "key": "mit",
      "name": "MIT License",
      "spdx_id": "MIT",
      "url": "https://api.github.com/licenses/mit",
      "node_id": "MDc6TGljZW5zZTEz"
    },
    "forks": 0,
    "open_issues": 2,
    "watchers": 0,
    "default_branch": "master"
  },
  "sender": {
    "login": "SamirBoulil",
    "id": 1826473,
    "node_id": "MDQ6VXNlcjE4MjY0NzM=",
    "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
    "gravatar_id": "",
    "url": "https://api.github.com/users/SamirBoulil",
    "html_url": "https://github.com/SamirBoulil",
    "followers_url": "https://api.github.com/users/SamirBoulil/followers",
    "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
    "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
    "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
    "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
    "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
    "repos_url": "https://api.github.com/users/SamirBoulil/repos",
    "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
    "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
    "type": "User",
    "site_admin": false
  }
}
JSON;

        return $json;
    }

    private function PRCommentedByOwnAuthor(): string
    {
        $json = <<<JSON
{
  "action": "submitted",
  "review": {
    "id": 213374685,
    "node_id": "MDE3OlB1bGxSZXF1ZXN0UmV2aWV3MjEzMzc0Njg1",
    "user": {
      "login": "SamirBoulil",
      "id": 1826473,
      "node_id": "MDQ6VXNlcjE4MjY0NzM=",
      "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
      "gravatar_id": "",
      "url": "https://api.github.com/users/SamirBoulil",
      "html_url": "https://github.com/SamirBoulil",
      "followers_url": "https://api.github.com/users/SamirBoulil/followers",
      "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
      "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
      "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
      "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
      "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
      "repos_url": "https://api.github.com/users/SamirBoulil/repos",
      "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
      "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
      "type": "User",
      "site_admin": false
    },
    "body": "qsd",
    "commit_id": "5a0abb69cdc1765c07e01df89a1192537eedf723",
    "submitted_at": "2019-03-12T13:06:14Z",
    "state": "commented",
    "html_url": "https://github.com/SamirBoulil/slub/pull/10#pullrequestreview-213374685",
    "pull_request_url": "https://api.github.com/repos/SamirBoulil/slub/pull/10",
    "author_association": "OWNER",
    "_links": {
      "html": {
        "href": "https://github.com/SamirBoulil/slub/pull/10#pullrequestreview-213374685"
      },
      "pull_request": {
        "href": "https://api.github.com/repos/SamirBoulil/slub/pull/10"
      }
    }
  },
  "pull_request": {
    "url": "https://api.github.com/repos/SamirBoulil/slub/pull/10",
    "id": 260346636,
    "node_id": "MDExOlB1bGxSZXF1ZXN0MjYwMzQ2NjM2",
    "html_url": "https://github.com/SamirBoulil/slub/pull/10",
    "diff_url": "https://github.com/SamirBoulil/slub/pull/10.diff",
    "patch_url": "https://github.com/SamirBoulil/slub/pull/10.patch",
    "issue_url": "https://api.github.com/repos/SamirBoulil/slub/issues/10",
    "number": 10,
    "state": "open",
    "locked": false,
    "title": "Stupid test",
    "user": {
      "login": "SamirBoulil",
      "id": 1826473,
      "node_id": "MDQ6VXNlcjE4MjY0NzM=",
      "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
      "gravatar_id": "",
      "url": "https://api.github.com/users/SamirBoulil",
      "html_url": "https://github.com/SamirBoulil",
      "followers_url": "https://api.github.com/users/SamirBoulil/followers",
      "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
      "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
      "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
      "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
      "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
      "repos_url": "https://api.github.com/users/SamirBoulil/repos",
      "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
      "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
      "type": "User",
      "site_admin": false
    },
    "body": "",
    "created_at": "2019-03-12T12:44:12Z",
    "updated_at": "2019-03-12T13:06:14Z",
    "closed_at": null,
    "merged_at": null,
    "merge_commit_sha": "36668c6a652d94514aeb28585e06e78be86f7228",
    "assignee": null,
    "assignees": [

    ],
    "requested_reviewers": [

    ],
    "requested_teams": [

    ],
    "labels": [

    ],
    "milestone": null,
    "commits_url": "https://api.github.com/repos/SamirBoulil/slub/pull/10/commits",
    "review_comments_url": "https://api.github.com/repos/SamirBoulil/slub/pull/10/comments",
    "review_comment_url": "https://api.github.com/repos/SamirBoulil/slub/pulls/comments{/number}",
    "comments_url": "https://api.github.com/repos/SamirBoulil/slub/issues/10/comments",
    "statuses_url": "https://api.github.com/repos/SamirBoulil/slub/statuses/5a0abb69cdc1765c07e01df89a1192537eedf723",
    "head": {
      "label": "SamirBoulil:test-3",
      "ref": "test-3",
      "sha": "5a0abb69cdc1765c07e01df89a1192537eedf723",
      "user": {
        "login": "SamirBoulil",
        "id": 1826473,
        "node_id": "MDQ6VXNlcjE4MjY0NzM=",
        "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
        "gravatar_id": "",
        "url": "https://api.github.com/users/SamirBoulil",
        "html_url": "https://github.com/SamirBoulil",
        "followers_url": "https://api.github.com/users/SamirBoulil/followers",
        "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
        "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
        "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
        "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
        "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
        "repos_url": "https://api.github.com/users/SamirBoulil/repos",
        "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
        "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
        "type": "User",
        "site_admin": false
      },
      "repo": {
        "id": 166291213,
        "node_id": "MDEwOlJlcG9zaXRvcnkxNjYyOTEyMTM=",
        "name": "slub",
        "full_name": "SamirBoulil/slub",
        "private": false,
        "owner": {
          "login": "SamirBoulil",
          "id": 1826473,
          "node_id": "MDQ6VXNlcjE4MjY0NzM=",
          "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
          "gravatar_id": "",
          "url": "https://api.github.com/users/SamirBoulil",
          "html_url": "https://github.com/SamirBoulil",
          "followers_url": "https://api.github.com/users/SamirBoulil/followers",
          "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
          "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
          "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
          "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
          "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
          "repos_url": "https://api.github.com/users/SamirBoulil/repos",
          "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
          "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
          "type": "User",
          "site_admin": false
        },
        "html_url": "https://github.com/SamirBoulil/slub",
        "description": "Improve the feedback loop between Github pull requests statuses and teams using Slack.",
        "fork": false,
        "url": "https://api.github.com/repos/SamirBoulil/slub",
        "forks_url": "https://api.github.com/repos/SamirBoulil/slub/forks",
        "keys_url": "https://api.github.com/repos/SamirBoulil/slub/keys{/key_id}",
        "collaborators_url": "https://api.github.com/repos/SamirBoulil/slub/collaborators{/collaborator}",
        "teams_url": "https://api.github.com/repos/SamirBoulil/slub/teams",
        "hooks_url": "https://api.github.com/repos/SamirBoulil/slub/hooks",
        "issue_events_url": "https://api.github.com/repos/SamirBoulil/slub/issues/events{/number}",
        "events_url": "https://api.github.com/repos/SamirBoulil/slub/events",
        "assignees_url": "https://api.github.com/repos/SamirBoulil/slub/assignees{/user}",
        "branches_url": "https://api.github.com/repos/SamirBoulil/slub/branches{/branch}",
        "tags_url": "https://api.github.com/repos/SamirBoulil/slub/tags",
        "blobs_url": "https://api.github.com/repos/SamirBoulil/slub/git/blobs{/sha}",
        "git_tags_url": "https://api.github.com/repos/SamirBoulil/slub/git/tags{/sha}",
        "git_refs_url": "https://api.github.com/repos/SamirBoulil/slub/git/refs{/sha}",
        "trees_url": "https://api.github.com/repos/SamirBoulil/slub/git/trees{/sha}",
        "statuses_url": "https://api.github.com/repos/SamirBoulil/slub/statuses/{sha}",
        "languages_url": "https://api.github.com/repos/SamirBoulil/slub/languages",
        "stargazers_url": "https://api.github.com/repos/SamirBoulil/slub/stargazers",
        "contributors_url": "https://api.github.com/repos/SamirBoulil/slub/contributors",
        "subscribers_url": "https://api.github.com/repos/SamirBoulil/slub/subscribers",
        "subscription_url": "https://api.github.com/repos/SamirBoulil/slub/subscription",
        "commits_url": "https://api.github.com/repos/SamirBoulil/slub/commits{/sha}",
        "git_commits_url": "https://api.github.com/repos/SamirBoulil/slub/git/commits{/sha}",
        "comments_url": "https://api.github.com/repos/SamirBoulil/slub/comments{/number}",
        "issue_comment_url": "https://api.github.com/repos/SamirBoulil/slub/issues/comments{/number}",
        "contents_url": "https://api.github.com/repos/SamirBoulil/slub/contents/{+path}",
        "compare_url": "https://api.github.com/repos/SamirBoulil/slub/compare/{base}...{head}",
        "merges_url": "https://api.github.com/repos/SamirBoulil/slub/merges",
        "archive_url": "https://api.github.com/repos/SamirBoulil/slub/{archive_format}{/ref}",
        "downloads_url": "https://api.github.com/repos/SamirBoulil/slub/downloads",
        "issues_url": "https://api.github.com/repos/SamirBoulil/slub/issues{/number}",
        "pulls_url": "https://api.github.com/repos/SamirBoulil/slub/pulls{/number}",
        "milestones_url": "https://api.github.com/repos/SamirBoulil/slub/milestones{/number}",
        "notifications_url": "https://api.github.com/repos/SamirBoulil/slub/notifications{?since,all,participating}",
        "labels_url": "https://api.github.com/repos/SamirBoulil/slub/labels{/name}",
        "releases_url": "https://api.github.com/repos/SamirBoulil/slub/releases{/id}",
        "deployments_url": "https://api.github.com/repos/SamirBoulil/slub/deployments",
        "created_at": "2019-01-17T20:21:39Z",
        "updated_at": "2019-03-12T12:35:40Z",
        "pushed_at": "2019-03-12T12:44:12Z",
        "git_url": "git://github.com/SamirBoulil/slub.git",
        "ssh_url": "git@github.com:SamirBoulil/slub.git",
        "clone_url": "https://github.com/SamirBoulil/slub.git",
        "svn_url": "https://github.com/SamirBoulil/slub",
        "homepage": "",
        "size": 358,
        "stargazers_count": 0,
        "watchers_count": 0,
        "language": "PHP",
        "has_issues": true,
        "has_projects": true,
        "has_downloads": true,
        "has_wiki": true,
        "has_pages": false,
        "forks_count": 0,
        "mirror_url": null,
        "archived": false,
        "open_issues_count": 2,
        "license": {
          "key": "mit",
          "name": "MIT License",
          "spdx_id": "MIT",
          "url": "https://api.github.com/licenses/mit",
          "node_id": "MDc6TGljZW5zZTEz"
        },
        "forks": 0,
        "open_issues": 2,
        "watchers": 0,
        "default_branch": "master"
      }
    },
    "base": {
      "label": "SamirBoulil:master",
      "ref": "master",
      "sha": "84ac8f0ba3bb748caed6c6eecaeeb5e55e3db25a",
      "user": {
        "login": "SamirBoulil",
        "id": 1826473,
        "node_id": "MDQ6VXNlcjE4MjY0NzM=",
        "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
        "gravatar_id": "",
        "url": "https://api.github.com/users/SamirBoulil",
        "html_url": "https://github.com/SamirBoulil",
        "followers_url": "https://api.github.com/users/SamirBoulil/followers",
        "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
        "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
        "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
        "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
        "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
        "repos_url": "https://api.github.com/users/SamirBoulil/repos",
        "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
        "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
        "type": "User",
        "site_admin": false
      },
      "repo": {
        "id": 166291213,
        "node_id": "MDEwOlJlcG9zaXRvcnkxNjYyOTEyMTM=",
        "name": "slub",
        "full_name": "SamirBoulil/slub",
        "private": false,
        "owner": {
          "login": "SamirBoulil",
          "id": 1826473,
          "node_id": "MDQ6VXNlcjE4MjY0NzM=",
          "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
          "gravatar_id": "",
          "url": "https://api.github.com/users/SamirBoulil",
          "html_url": "https://github.com/SamirBoulil",
          "followers_url": "https://api.github.com/users/SamirBoulil/followers",
          "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
          "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
          "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
          "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
          "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
          "repos_url": "https://api.github.com/users/SamirBoulil/repos",
          "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
          "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
          "type": "User",
          "site_admin": false
        },
        "html_url": "https://github.com/SamirBoulil/slub",
        "description": "Improve the feedback loop between Github pull requests statuses and teams using Slack.",
        "fork": false,
        "url": "https://api.github.com/repos/SamirBoulil/slub",
        "forks_url": "https://api.github.com/repos/SamirBoulil/slub/forks",
        "keys_url": "https://api.github.com/repos/SamirBoulil/slub/keys{/key_id}",
        "collaborators_url": "https://api.github.com/repos/SamirBoulil/slub/collaborators{/collaborator}",
        "teams_url": "https://api.github.com/repos/SamirBoulil/slub/teams",
        "hooks_url": "https://api.github.com/repos/SamirBoulil/slub/hooks",
        "issue_events_url": "https://api.github.com/repos/SamirBoulil/slub/issues/events{/number}",
        "events_url": "https://api.github.com/repos/SamirBoulil/slub/events",
        "assignees_url": "https://api.github.com/repos/SamirBoulil/slub/assignees{/user}",
        "branches_url": "https://api.github.com/repos/SamirBoulil/slub/branches{/branch}",
        "tags_url": "https://api.github.com/repos/SamirBoulil/slub/tags",
        "blobs_url": "https://api.github.com/repos/SamirBoulil/slub/git/blobs{/sha}",
        "git_tags_url": "https://api.github.com/repos/SamirBoulil/slub/git/tags{/sha}",
        "git_refs_url": "https://api.github.com/repos/SamirBoulil/slub/git/refs{/sha}",
        "trees_url": "https://api.github.com/repos/SamirBoulil/slub/git/trees{/sha}",
        "statuses_url": "https://api.github.com/repos/SamirBoulil/slub/statuses/{sha}",
        "languages_url": "https://api.github.com/repos/SamirBoulil/slub/languages",
        "stargazers_url": "https://api.github.com/repos/SamirBoulil/slub/stargazers",
        "contributors_url": "https://api.github.com/repos/SamirBoulil/slub/contributors",
        "subscribers_url": "https://api.github.com/repos/SamirBoulil/slub/subscribers",
        "subscription_url": "https://api.github.com/repos/SamirBoulil/slub/subscription",
        "commits_url": "https://api.github.com/repos/SamirBoulil/slub/commits{/sha}",
        "git_commits_url": "https://api.github.com/repos/SamirBoulil/slub/git/commits{/sha}",
        "comments_url": "https://api.github.com/repos/SamirBoulil/slub/comments{/number}",
        "issue_comment_url": "https://api.github.com/repos/SamirBoulil/slub/issues/comments{/number}",
        "contents_url": "https://api.github.com/repos/SamirBoulil/slub/contents/{+path}",
        "compare_url": "https://api.github.com/repos/SamirBoulil/slub/compare/{base}...{head}",
        "merges_url": "https://api.github.com/repos/SamirBoulil/slub/merges",
        "archive_url": "https://api.github.com/repos/SamirBoulil/slub/{archive_format}{/ref}",
        "downloads_url": "https://api.github.com/repos/SamirBoulil/slub/downloads",
        "issues_url": "https://api.github.com/repos/SamirBoulil/slub/issues{/number}",
        "pulls_url": "https://api.github.com/repos/SamirBoulil/slub/pulls{/number}",
        "milestones_url": "https://api.github.com/repos/SamirBoulil/slub/milestones{/number}",
        "notifications_url": "https://api.github.com/repos/SamirBoulil/slub/notifications{?since,all,participating}",
        "labels_url": "https://api.github.com/repos/SamirBoulil/slub/labels{/name}",
        "releases_url": "https://api.github.com/repos/SamirBoulil/slub/releases{/id}",
        "deployments_url": "https://api.github.com/repos/SamirBoulil/slub/deployments",
        "created_at": "2019-01-17T20:21:39Z",
        "updated_at": "2019-03-12T12:35:40Z",
        "pushed_at": "2019-03-12T12:44:12Z",
        "git_url": "git://github.com/SamirBoulil/slub.git",
        "ssh_url": "git@github.com:SamirBoulil/slub.git",
        "clone_url": "https://github.com/SamirBoulil/slub.git",
        "svn_url": "https://github.com/SamirBoulil/slub",
        "homepage": "",
        "size": 358,
        "stargazers_count": 0,
        "watchers_count": 0,
        "language": "PHP",
        "has_issues": true,
        "has_projects": true,
        "has_downloads": true,
        "has_wiki": true,
        "has_pages": false,
        "forks_count": 0,
        "mirror_url": null,
        "archived": false,
        "open_issues_count": 2,
        "license": {
          "key": "mit",
          "name": "MIT License",
          "spdx_id": "MIT",
          "url": "https://api.github.com/licenses/mit",
          "node_id": "MDc6TGljZW5zZTEz"
        },
        "forks": 0,
        "open_issues": 2,
        "watchers": 0,
        "default_branch": "master"
      }
    },
    "_links": {
      "self": {
        "href": "https://api.github.com/repos/SamirBoulil/slub/pull/10"
      },
      "html": {
        "href": "https://github.com/SamirBoulil/slub/pull/10"
      },
      "issue": {
        "href": "https://api.github.com/repos/SamirBoulil/slub/issues/10"
      },
      "comments": {
        "href": "https://api.github.com/repos/SamirBoulil/slub/issues/10/comments"
      },
      "review_comments": {
        "href": "https://api.github.com/repos/SamirBoulil/slub/pull/10/comments"
      },
      "review_comment": {
        "href": "https://api.github.com/repos/SamirBoulil/slub/pulls/comments{/number}"
      },
      "commits": {
        "href": "https://api.github.com/repos/SamirBoulil/slub/pull/10/commits"
      },
      "statuses": {
        "href": "https://api.github.com/repos/SamirBoulil/slub/statuses/5a0abb69cdc1765c07e01df89a1192537eedf723"
      }
    },
    "author_association": "OWNER"
  },
  "repository": {
    "id": 166291213,
    "node_id": "MDEwOlJlcG9zaXRvcnkxNjYyOTEyMTM=",
    "name": "slub",
    "full_name": "SamirBoulil/slub",
    "private": false,
    "owner": {
      "login": "SamirBoulil",
      "id": 1826473,
      "node_id": "MDQ6VXNlcjE4MjY0NzM=",
      "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
      "gravatar_id": "",
      "url": "https://api.github.com/users/SamirBoulil",
      "html_url": "https://github.com/SamirBoulil",
      "followers_url": "https://api.github.com/users/SamirBoulil/followers",
      "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
      "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
      "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
      "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
      "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
      "repos_url": "https://api.github.com/users/SamirBoulil/repos",
      "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
      "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
      "type": "User",
      "site_admin": false
    },
    "html_url": "https://github.com/SamirBoulil/slub",
    "description": "Improve the feedback loop between Github pull requests statuses and teams using Slack.",
    "fork": false,
    "url": "https://api.github.com/repos/SamirBoulil/slub",
    "forks_url": "https://api.github.com/repos/SamirBoulil/slub/forks",
    "keys_url": "https://api.github.com/repos/SamirBoulil/slub/keys{/key_id}",
    "collaborators_url": "https://api.github.com/repos/SamirBoulil/slub/collaborators{/collaborator}",
    "teams_url": "https://api.github.com/repos/SamirBoulil/slub/teams",
    "hooks_url": "https://api.github.com/repos/SamirBoulil/slub/hooks",
    "issue_events_url": "https://api.github.com/repos/SamirBoulil/slub/issues/events{/number}",
    "events_url": "https://api.github.com/repos/SamirBoulil/slub/events",
    "assignees_url": "https://api.github.com/repos/SamirBoulil/slub/assignees{/user}",
    "branches_url": "https://api.github.com/repos/SamirBoulil/slub/branches{/branch}",
    "tags_url": "https://api.github.com/repos/SamirBoulil/slub/tags",
    "blobs_url": "https://api.github.com/repos/SamirBoulil/slub/git/blobs{/sha}",
    "git_tags_url": "https://api.github.com/repos/SamirBoulil/slub/git/tags{/sha}",
    "git_refs_url": "https://api.github.com/repos/SamirBoulil/slub/git/refs{/sha}",
    "trees_url": "https://api.github.com/repos/SamirBoulil/slub/git/trees{/sha}",
    "statuses_url": "https://api.github.com/repos/SamirBoulil/slub/statuses/{sha}",
    "languages_url": "https://api.github.com/repos/SamirBoulil/slub/languages",
    "stargazers_url": "https://api.github.com/repos/SamirBoulil/slub/stargazers",
    "contributors_url": "https://api.github.com/repos/SamirBoulil/slub/contributors",
    "subscribers_url": "https://api.github.com/repos/SamirBoulil/slub/subscribers",
    "subscription_url": "https://api.github.com/repos/SamirBoulil/slub/subscription",
    "commits_url": "https://api.github.com/repos/SamirBoulil/slub/commits{/sha}",
    "git_commits_url": "https://api.github.com/repos/SamirBoulil/slub/git/commits{/sha}",
    "comments_url": "https://api.github.com/repos/SamirBoulil/slub/comments{/number}",
    "issue_comment_url": "https://api.github.com/repos/SamirBoulil/slub/issues/comments{/number}",
    "contents_url": "https://api.github.com/repos/SamirBoulil/slub/contents/{+path}",
    "compare_url": "https://api.github.com/repos/SamirBoulil/slub/compare/{base}...{head}",
    "merges_url": "https://api.github.com/repos/SamirBoulil/slub/merges",
    "archive_url": "https://api.github.com/repos/SamirBoulil/slub/{archive_format}{/ref}",
    "downloads_url": "https://api.github.com/repos/SamirBoulil/slub/downloads",
    "issues_url": "https://api.github.com/repos/SamirBoulil/slub/issues{/number}",
    "pulls_url": "https://api.github.com/repos/SamirBoulil/slub/pulls{/number}",
    "milestones_url": "https://api.github.com/repos/SamirBoulil/slub/milestones{/number}",
    "notifications_url": "https://api.github.com/repos/SamirBoulil/slub/notifications{?since,all,participating}",
    "labels_url": "https://api.github.com/repos/SamirBoulil/slub/labels{/name}",
    "releases_url": "https://api.github.com/repos/SamirBoulil/slub/releases{/id}",
    "deployments_url": "https://api.github.com/repos/SamirBoulil/slub/deployments",
    "created_at": "2019-01-17T20:21:39Z",
    "updated_at": "2019-03-12T12:35:40Z",
    "pushed_at": "2019-03-12T12:44:12Z",
    "git_url": "git://github.com/SamirBoulil/slub.git",
    "ssh_url": "git@github.com:SamirBoulil/slub.git",
    "clone_url": "https://github.com/SamirBoulil/slub.git",
    "svn_url": "https://github.com/SamirBoulil/slub",
    "homepage": "",
    "size": 358,
    "stargazers_count": 0,
    "watchers_count": 0,
    "language": "PHP",
    "has_issues": true,
    "has_projects": true,
    "has_downloads": true,
    "has_wiki": true,
    "has_pages": false,
    "forks_count": 0,
    "mirror_url": null,
    "archived": false,
    "open_issues_count": 2,
    "license": {
      "key": "mit",
      "name": "MIT License",
      "spdx_id": "MIT",
      "url": "https://api.github.com/licenses/mit",
      "node_id": "MDc6TGljZW5zZTEz"
    },
    "forks": 0,
    "open_issues": 2,
    "watchers": 0,
    "default_branch": "master"
  },
  "sender": {
    "login": "SamirBoulil",
    "id": 1826473,
    "node_id": "MDQ6VXNlcjE4MjY0NzM=",
    "avatar_url": "https://avatars1.githubusercontent.com/u/1826473?v=4",
    "gravatar_id": "",
    "url": "https://api.github.com/users/SamirBoulil",
    "html_url": "https://github.com/SamirBoulil",
    "followers_url": "https://api.github.com/users/SamirBoulil/followers",
    "following_url": "https://api.github.com/users/SamirBoulil/following{/other_user}",
    "gists_url": "https://api.github.com/users/SamirBoulil/gists{/gist_id}",
    "starred_url": "https://api.github.com/users/SamirBoulil/starred{/owner}{/repo}",
    "subscriptions_url": "https://api.github.com/users/SamirBoulil/subscriptions",
    "organizations_url": "https://api.github.com/users/SamirBoulil/orgs",
    "repos_url": "https://api.github.com/users/SamirBoulil/repos",
    "events_url": "https://api.github.com/users/SamirBoulil/events{/privacy}",
    "received_events_url": "https://api.github.com/users/SamirBoulil/received_events",
    "type": "User",
    "site_admin": false
  }
}
JSON;

        return $json;
    }
}
