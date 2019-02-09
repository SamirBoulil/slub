Feature: Improve the signal VS noise of the list of pull requests that needs a review
  In order for the squad to make the difference between the PRs that are still open from those closed
  As a squad
  We want to be notified when a PR is merged

  @nominal
  Scenario: Notify the squad when the PR is merged
    Given a PR in review
    When the author merges the PR
    Then the PR is merged
    And the squad should be notified that the PR has been merged

  @secondary
  Scenario: It does not notify when a PR of an unsupported repository is merged
    When the a PR belonging to an unsupported repository is merged
    Then it does not notify the squad
