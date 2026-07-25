Feature: Publish a reminder of the pull requests to review for every channels
  In order to prevent a squad from forgetting to review a PR
  As an author
  I want to automatically publish a reminder of all the PR in review in the channel

  @nominal
  Scenario: Publish a PR in review missing GTMS
    Given a PR in review not GTMed
    And a PR in review having 2 GTMs
    And a PR merged
    And a PR closed
    When the system publishes a reminder
    Then the reminder should only contain the PR not GTMed

  @nominal
  Scenario: Publish a reminder of a PR in review for each slack channels
    Given some PRs in review and some PRs merged in multiple channels
    When the system publishes a reminder
    And the reminders should only contain a reference to the PRs in review
    # And they are ordered by descending time in review

  # TODO: To investigate, this scenario should be failing but it's not for some reason.
  @secondary
  Scenario: Does not publish a reminder of a PR published in unsupported channel
    Given a PR not GTMed published in a supported channel
    And a PR not GTMed published in a unsupported channel
    When the system publishes a reminder
    Then the reminder should only contain the PR not GTMed in the supported channel

  @secondary
  Scenario: Does not publish a reminder on the week-end
    Given a PR in review having 1 GTMs
    And we are on a week-end
    When the system publishes a reminder
    Then the reminder should be empty

  @nominal
  Scenario: Publish a reminder for a document in review
    Given a document in review having 2 check mark reactions
    When the system publishes a reminder
    Then the reminder should only contain the document in review

  @nominal
  Scenario: Publish a single reminder containing both the PRs and the documents in review
    Given a PR in review not GTMed
    And a document in review having 2 check mark reactions
    When the system publishes a reminder
    Then the reminder should contain both the PR and the document in review

  @secondary
  Scenario: Publishes the reminder even when Slack fails for one document
    Given a document in review having 2 check mark reactions
    And a document in review for which Slack fails
    When the system publishes a reminder
    Then the reminder should contain the document in review and a degraded line for the failing document

  @secondary
  Scenario: Documents are ordered by the number of days in review
    Given a document put in review 2 days ago
    And a document in review having 2 check mark reactions
    When the system publishes a reminder
    Then the reminder should contain the documents ordered by the number of days in review

  @secondary
  Scenario: Does not publish a document reminder on the week-end
    Given a document in review having 2 check mark reactions
    And we are on a week-end
    When the system publishes a reminder
    Then the reminder should be empty
