Feature: Improve PR reviews by warning author if their PR are too large
  In order for the author to be able to split it
  As an author
  We want to be notified when a PR is too large

  @nominal
  Scenario: Notify the author when the PR is too large
    Given a PR in review
    When the author opened the PR
    And the PR is too large
    Then the author should be notified that the PR is too large

  @nominal
  Scenario: Notify the author when the PR is too large
    Given a PR in review
    When the author synchronize the PR
    And the PR is too large
    Then the author should be notified that the PR is too large

  @secondary
  Scenario: It does not notify when a PR is not too large
    When the a PR is no too large
    Then the author should not be not notified
