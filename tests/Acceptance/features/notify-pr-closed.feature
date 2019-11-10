Feature: Improve the signal VS noise of the list of pull requests that needs a review
  In order for the squad to make the difference between the PRs that are still open from those closed
  As a squad
  We want to be notified when a PR is closed

  @nominal
  Scenario: Notify the squad when the PR is closed without being merged
    Given a PR in review having multiple comments and a CI result
    When the author closes the PR without merging
    Then the PR is only closed
    And the squad should be notified that the PR has been closed without merging

  @nominal
  Scenario: Notify the squad when the PR is closed and merged
    Given a PR in review having multiple comments and a CI result
    When the author closes the PR by merging it
    Then the PR is closed and merged
    And the squad should be notified that the PR has been closed and merged

  @secondary
  Scenario: It does not notify when a PR of an unsupported repository is closed
    When the a PR belonging to an unsupported repository is closed
    Then the squad should not be not notified
