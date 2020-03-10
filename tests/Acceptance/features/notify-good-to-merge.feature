Feature: Enhance the author's confidence in merging the PR
  In order to make improve the aurthor's confidence in merging the PR
  As an author
  I want to be notified when the PR is good to merge

  @nominal
  Scenario: Notify the author when is good to merge (the PR just received its last GTM)
    Given a green PR missing one GTM
    When the PR gets its last GTM
    Then the author should be notified that the PR is good to merge

  @nominal
  Scenario: Notify the author when is good to merge (the PR just received the green CI)
    Given a PR having 2 GTMS
    When the PR gets a green CI
    Then the author should be notified that the PR is good to merge
