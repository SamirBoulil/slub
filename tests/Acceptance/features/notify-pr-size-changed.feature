Feature: Improve PR reviews by warning author if their PR are too large
  In order for the author to be able to split it
  As an author
  We want to be notified when a PR is too large

  @nominal
  Scenario: Notify the author when the PR becomes too large
    Given a PR in review
    When the author updates the PR too the point that it becomes too large
    Then the author should be notified that the PR is too large
