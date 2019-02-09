Feature: Improve the feedback delay between the squad and the CI status
  In order to accelerate the feedback loop between the squad and the CI status of PR
  As a squad
  We want to be notified of the pull request CI status updates

  @nominal
  Scenario: Notify the squad when the continuous integration (CI) is green for a pull request
    Given a pull request in review
    When the CI is green for the pull request
    Then the PR should be green
    And the squad should be notified that the ci is green for the pull request
