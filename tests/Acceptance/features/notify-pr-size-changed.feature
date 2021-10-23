Feature: Improve PR reviews by warning author if their PR are too large
  In order for the author to be able to split it
  As an author
  We want to be notified when a PR is too large

  @nominal
  Scenario Outline: Notify the author when the PR becomes too large
    Given a PR in review that has an acceptable size
    When the author updates the PR with <additions> additions and <deletions> deletions
    Then the author should be notified that the PR has become too large

    Examples:
      | additions | deletions |
      | 1000      | 0         |
      | 0         | 1000      |

  @secondary
  Scenario: Do not notify the author when the PR stays small
    Given a PR in review that has an acceptable size
    When the author updates the PR with 0 additions and 0 deletions
    Then the author should not be notified that the PR size has changed

  @secondary
  Scenario: Do not notify the author when the PR becomes small
    Given a large PR in review
    When the author updates the PR with 0 additions and 0 deletions
    Then the author should not be notified that the PR size has changed
