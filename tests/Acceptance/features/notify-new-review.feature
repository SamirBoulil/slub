Feature: Improve the communication between the author of the pull request (PR) and it's reviewer by showing the squad when a PR is reviewed
  In order to accelerate the feedback loop between an author and it's reviewers on a PR
  As a squad (group of developpers)
  We want to be notified when a PR status changes

  @nominal
  Scenario: Notify the squad when the PR is GTMed
    Given a PR in review
    When the PR is GTMed
    Then the PR should be GTMed
    And the author should be notified that the PR has one more GTM
    And the squad should be notified that the PR has one more GTM

  @nominal
  Scenario: Notify the squad when the PR is not GTMed
    Given a PR in review
    When the PR is NOT GTMED
    Then the PR should be NOT GTMed
    And the author should be notified that the PR has one more NOT GTM

  @nominal
  Scenario: Notify the squad when the PR is commented
    Given a PR in review
    When the PR is commented
    Then the PR should have one comment
    And the author should be notified that the PR has one more comment

  @secondary
  Scenario: It does not notify the new reviews on unsupported repositories
    When a PR is reviewed on an unsupported repository
    Then it does not notify the squad

