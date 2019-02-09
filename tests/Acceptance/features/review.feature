Feature: Improve the communication between the author of the PR and it's reviewer by showing the squad when a PR is GTMed
  In order to accelerate the feedback loop between an author and it's reviewers on a pull request
  As a squad (group of developpers)
  We want to be notified when a pull request status changes

  @nominal
  Scenario: Notify the squad when the pull request is GTMed
    Given a pull request in review
    When the pull request is GTMed
    Then the pull request should be GTMed
    And the squad should be notified that the pull request has one more GTM

  @nominal
  Scenario: Notify the squad when the pull request is not GTMed
    Given a pull request in review
    When the pull request is NOT GTMED
    Then the pull request should be NOT GTMed
    And the squad should be notified that the pull request has one more NOT GTM

  @secondary
  Scenario: It does not notify the new reviews on unsupported repositories
    When a pull request is reviewed on an unsupported repository
    Then it does not notify the squad

