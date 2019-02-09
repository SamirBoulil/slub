#Feature: Improve the communication between squad internal chat and Github regarding PRs
#  In order to accelerate the feedback loop between an author and it's reviewers on a pull request
#  As a squad (group of developpers)
#  We want to be notified when pull request status changes
#
#
#  @nominal
#  Scenario: Notify the squad when the pull request is merged
#    Given a pull request put to review
#    When the pull request is merged
#    Then the squad should be notified the pull request has been merged
#
#  @secondary
#  Scenario: It does not notify pull requests merge for unsupported repositories
#    Given a pull request belonging to an unsupported repository
#    When the pull request is merged
#    Then it does not notify the squad
#
#  @secondary
#  Scenario: It does not notify pull requests merge for unsupported chat channels
#    Given a pull request is put to review in an unsupported chat channel
#    When the pull request is merged
#    Then it does not notify the squad
