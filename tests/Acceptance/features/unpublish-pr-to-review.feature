Feature: Unpublish a specific pull request in review
  In order to stop following the progress of a pull request (PR)
  As an author
  I want to unpublish a specific PR from the reviewing process

  @nominal
  Scenario: Unpublish a PR from the reviewing process
    Given a PR has been put to review by mistake
    When an author unpublishes a PR
    Then the PR is is unpublished
