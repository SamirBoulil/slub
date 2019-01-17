Feature: Improve the communication between squad internal chat and Github regarding PRs
  In order to accelerate the feedback loop between an author and it's reviewers on a pull request
  As a squad (group of developpers)
  We want to be notified when pull request status changes

  @nominal
  Scenario: Notify the squad when the pull request is GTMed
    Given a pull request put to review
    When the pull request is GTMed
    Then the squad should be notified that the pull request has one more GTM

  Scenario: Notify the squad when the pull request is not GTMed
    Given a pull request put to review
    When the pull request NOT GTMED
    Then the squad should be notified that the pull request has one more NOT GTM

  Scenario: Notify the squad when the continuous integration (CI) passes
    Given a pull request put to review
    When the CI passes
    Then the squad should be notified that the pull request does not break any tests

  Scenario: Notify the squad when the continuous integration (CI) fails
    Given a pull request put to review
    When the CI fails
    Then the squad should be notified the pull request broke some tests

  Scenario: Notify the squad when the pull request is merged
    Given a pull request put to review
    When the pull request is merged
    Then the squad should be notified the pull request has been merged

  @unsupported-repositories
  Scenario: It does not notify the new reviews on unsupported repositories
    Given a pull request belonging to an unspported repository
    When the pull request is reviewed
    Then it does not notify the squad

  Scenario: It does not notify CI status changes for unsupported repositories
    Given a pull request belonging to an unsupported repository
    When the CI status changes of the pull request changes
    Then it does not notify the squad

  Scenario: It does not notify pull requests merge for unsupported repositories
    Given a pull request belonging to an unsupported repository
    When the pull request is merged
    Then it does not notify the squad

  @unsupported-slack-channel
  Scenario: It does not notify the new reviews on unsupported chat channels
    Given a pull request is put to review in an unsupported chat channel
    When the pull request is reviewed
    Then it does not notify the squad

  Scenario: It does not notify CI status changes for unsupported chat channels
    Given a pull request is put to review in an unsupported chat channel
    When the CI status changes of the pull request changes
    Then it does not notify the squad

  Scenario: It does not notify pull requests merge for unsupported chat channels
    Given a pull request is put to review in an unsupported chat channel
    When the pull request is merged
    Then it does not notify the squad
