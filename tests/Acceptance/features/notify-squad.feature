#Feature: Improve the communication between squad internal chat and Github regarding PRs
#  In order to accelerate the feedback loop between an author and it's reviewers on a PR
#  As a squad (group of developpers)
#  We want to be notified when PR status changes
#
#
#  @nominal
#  Scenario: Notify the squad when the PR is merged
#    Given a PR put to review
#    When the PR is merged
#    Then the squad should be notified the PR has been merged
#
#  @secondary
#  Scenario: It does not notify PRs merge for unsupported repositories
#    Given a PR belonging to an unsupported repository
#    When the PR is merged
#    Then it does not notify the squad
#
#  @secondary
#  Scenario: It does not notify PRs merge for unsupported chat channels
#    Given a PR is put to review in an unsupported chat channel
#    When the PR is merged
#    Then it does not notify the squad
