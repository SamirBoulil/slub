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
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Tests\WebTestCase;

/**
 * @author    Pierrick Martos <pierrick.martos@gmail.com>
 */
class PRTooLargeTest extends WebTestCase
{
    private const PR_IDENTIFIER = 'SamirBoulil/slub/10';

    /** @var PRRepositoryInterface */
    private $PRRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->PRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $this->GivenAPRToReviewThatIsNotLarge();
    }

    /**
     * @test
     */
    public function when_a_large_pr_is_opened_on_github_it_is_set_to_large(): void
    {
        $client = $this->WhenALargePRIsOpened();

        $this->assertPRIsLarge();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    private function WhenALargePRIsOpened(): KernelBrowser
    {
        $client = self::getClient();
        $signature = sprintf('sha1=%s', hash_hmac('sha1', $this->largePREvent(), $this->get('GITHUB_WEBHOOK_SECRET')));
        $client->request(
            'POST',
            '/vcs/github',
            [],
            [],
            ['HTTP_X-GitHub-Event' => 'pull_request', 'HTTP_X-Hub-Signature' => $signature, 'HTTP_X-Github-Delivery' => Uuid::uuid4()->toString()],
            $this->largePREvent()
        );

        return $client;
    }

    private function assertPRIsLarge(): void
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString(self::PR_IDENTIFIER));
        $this->assertTrue($PR->normalize()['IS_TOO_LARGE']);
    }

    private function GivenAPRToReviewThatIsNotLarge(): void
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

    private function largePREvent(): string
    {
        return <<<JSON
{
  "action": "synchronize",
  "number": 142,
  "pull_request": {
    "url": "https://api.github.com/repos/SamirBoulil/slub/pulls/142",
    "id": 762968639,
    "node_id": "PR_kwDOCelnDc4tefo_",
    "html_url": "https://github.com/SamirBoulil/slub/pull/142",
    "diff_url": "https://github.com/SamirBoulil/slub/pull/142.diff",
    "patch_url": "https://github.com/SamirBoulil/slub/pull/142.patch",
    "issue_url": "https://api.github.com/repos/SamirBoulil/slub/issues/142",
    "number": 142,
    "state": "open",
    "locked": false,
    "title": "Issue 112",
    "user": {
      "login": "SamirBoulil",
      "id": 1826473,
      "node_id": "MDQ6VXNlcjE4MjY0NzM=",
      "avatar_url": "https://avatars.githubusercontent.com/u/1826473?v=4",
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
    "body": "Started in here: https://github.com/SamirBoulil/slub/pull/138",
    "created_at": "2021-10-21T07:07:26Z",
    "updated_at": "2021-10-23T16:44:55Z",
    "closed_at": null,
    "merged_at": null,
    "merge_commit_sha": "f078c342176d9217e82b54c783ce557ab047f1ec",
    "assignee": null,
    "assignees": [],
    "requested_reviewers": [],
    "requested_teams": [],
    "labels": [],
    "milestone": null,
    "draft": false,
    "commits_url": "https://api.github.com/repos/SamirBoulil/slub/pulls/142/commits",
    "review_comments_url": "https://api.github.com/repos/SamirBoulil/slub/pulls/142/comments",
    "review_comment_url": "https://api.github.com/repos/SamirBoulil/slub/pulls/comments{/number}",
    "comments_url": "https://api.github.com/repos/SamirBoulil/slub/issues/142/comments",
    "statuses_url": "https://api.github.com/repos/SamirBoulil/slub/statuses/cfee7dce09b15c09ab25dd2d428257559f65eff6",
    "head": {
      "label": "SamirBoulil:issue-112",
      "ref": "issue-112",
      "sha": "cfee7dce09b15c09ab25dd2d428257559f65eff6",
      "user": {
        "login": "SamirBoulil",
        "id": 1826473,
        "node_id": "MDQ6VXNlcjE4MjY0NzM=",
        "avatar_url": "https://avatars.githubusercontent.com/u/1826473?v=4",
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
          "avatar_url": "https://avatars.githubusercontent.com/u/1826473?v=4",
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
        "description": "Improve the feedback loop between pull requests authors and teams reviewers using Slack.",
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
        "updated_at": "2021-10-20T19:56:35Z",
        "pushed_at": "2021-10-23T16:44:55Z",
        "git_url": "git://github.com/SamirBoulil/slub.git",
        "ssh_url": "git@github.com:SamirBoulil/slub.git",
        "clone_url": "https://github.com/SamirBoulil/slub.git",
        "svn_url": "https://github.com/SamirBoulil/slub",
        "homepage": "",
        "size": 1878,
        "stargazers_count": 6,
        "watchers_count": 6,
        "language": "PHP",
        "has_issues": true,
        "has_projects": true,
        "has_downloads": true,
        "has_wiki": true,
        "has_pages": false,
        "forks_count": 3,
        "mirror_url": null,
        "archived": false,
        "disabled": false,
        "open_issues_count": 8,
        "license": {
          "key": "mit",
          "name": "MIT License",
          "spdx_id": "MIT",
          "url": "https://api.github.com/licenses/mit",
          "node_id": "MDc6TGljZW5zZTEz"
        },
        "allow_forking": true,
        "is_template": false,
        "topics": [],
        "visibility": "public",
        "forks": 3,
        "open_issues": 8,
        "watchers": 6,
        "default_branch": "master",
        "allow_squash_merge": true,
        "allow_merge_commit": true,
        "allow_rebase_merge": true,
        "allow_auto_merge": false,
        "delete_branch_on_merge": false
      }
    },
    "base": {
      "label": "SamirBoulil:master",
      "ref": "master",
      "sha": "78c623cd6f20d937253f266128a80fc85b977123",
      "user": {
        "login": "SamirBoulil",
        "id": 1826473,
        "node_id": "MDQ6VXNlcjE4MjY0NzM=",
        "avatar_url": "https://avatars.githubusercontent.com/u/1826473?v=4",
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
          "avatar_url": "https://avatars.githubusercontent.com/u/1826473?v=4",
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
        "description": "Improve the feedback loop between pull requests authors and teams reviewers using Slack.",
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
        "updated_at": "2021-10-20T19:56:35Z",
        "pushed_at": "2021-10-23T16:44:55Z",
        "git_url": "git://github.com/SamirBoulil/slub.git",
        "ssh_url": "git@github.com:SamirBoulil/slub.git",
        "clone_url": "https://github.com/SamirBoulil/slub.git",
        "svn_url": "https://github.com/SamirBoulil/slub",
        "homepage": "",
        "size": 1878,
        "stargazers_count": 6,
        "watchers_count": 6,
        "language": "PHP",
        "has_issues": true,
        "has_projects": true,
        "has_downloads": true,
        "has_wiki": true,
        "has_pages": false,
        "forks_count": 3,
        "mirror_url": null,
        "archived": false,
        "disabled": false,
        "open_issues_count": 8,
        "license": {
          "key": "mit",
          "name": "MIT License",
          "spdx_id": "MIT",
          "url": "https://api.github.com/licenses/mit",
          "node_id": "MDc6TGljZW5zZTEz"
        },
        "allow_forking": true,
        "is_template": false,
        "topics": [],
        "visibility": "public",
        "forks": 3,
        "open_issues": 8,
        "watchers": 6,
        "default_branch": "master",
        "allow_squash_merge": true,
        "allow_merge_commit": true,
        "allow_rebase_merge": true,
        "allow_auto_merge": false,
        "delete_branch_on_merge": false
      }
    },
    "_links": {
      "self": {
        "href": "https://api.github.com/repos/SamirBoulil/slub/pulls/142"
      },
      "html": {
        "href": "https://github.com/SamirBoulil/slub/pull/142"
      },
      "issue": {
        "href": "https://api.github.com/repos/SamirBoulil/slub/issues/142"
      },
      "comments": {
        "href": "https://api.github.com/repos/SamirBoulil/slub/issues/142/comments"
      },
      "review_comments": {
        "href": "https://api.github.com/repos/SamirBoulil/slub/pulls/142/comments"
      },
      "review_comment": {
        "href": "https://api.github.com/repos/SamirBoulil/slub/pulls/comments{/number}"
      },
      "commits": {
        "href": "https://api.github.com/repos/SamirBoulil/slub/pulls/142/commits"
      },
      "statuses": {
        "href": "https://api.github.com/repos/SamirBoulil/slub/statuses/cfee7dce09b15c09ab25dd2d428257559f65eff6"
      }
    },
    "author_association": "OWNER",
    "auto_merge": null,
    "active_lock_reason": null,
    "merged": false,
    "mergeable": null,
    "rebaseable": null,
    "mergeable_state": "unknown",
    "merged_by": null,
    "comments": 0,
    "review_comments": 2,
    "maintainer_can_modify": false,
    "commits": 20,
    "additions": 1013,
    "deletions": 105,
    "changed_files": 41
  },
  "before": "0aeb9e454f9b4e9f7103917368617c88f9fcb638",
  "after": "cfee7dce09b15c09ab25dd2d428257559f65eff6",
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
      "avatar_url": "https://avatars.githubusercontent.com/u/1826473?v=4",
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
    "description": "Improve the feedback loop between pull requests authors and teams reviewers using Slack.",
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
    "updated_at": "2021-10-20T19:56:35Z",
    "pushed_at": "2021-10-23T16:44:55Z",
    "git_url": "git://github.com/SamirBoulil/slub.git",
    "ssh_url": "git@github.com:SamirBoulil/slub.git",
    "clone_url": "https://github.com/SamirBoulil/slub.git",
    "svn_url": "https://github.com/SamirBoulil/slub",
    "homepage": "",
    "size": 1878,
    "stargazers_count": 6,
    "watchers_count": 6,
    "language": "PHP",
    "has_issues": true,
    "has_projects": true,
    "has_downloads": true,
    "has_wiki": true,
    "has_pages": false,
    "forks_count": 3,
    "mirror_url": null,
    "archived": false,
    "disabled": false,
    "open_issues_count": 8,
    "license": {
      "key": "mit",
      "name": "MIT License",
      "spdx_id": "MIT",
      "url": "https://api.github.com/licenses/mit",
      "node_id": "MDc6TGljZW5zZTEz"
    },
    "allow_forking": true,
    "is_template": false,
    "topics": [],
    "visibility": "public",
    "forks": 3,
    "open_issues": 8,
    "watchers": 6,
    "default_branch": "master"
  },
  "sender": {
    "login": "SamirBoulil",
    "id": 1826473,
    "node_id": "MDQ6VXNlcjE4MjY0NzM=",
    "avatar_url": "https://avatars.githubusercontent.com/u/1826473?v=4",
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
  "installation": {
    "id": 13175351,
    "node_id": "MDIzOkludGVncmF0aW9uSW5zdGFsbGF0aW9uMTMxNzUzNTE="
  }
}
JSON;
    }
}
