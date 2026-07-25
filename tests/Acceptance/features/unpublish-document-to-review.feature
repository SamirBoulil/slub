Feature: Unpublish a specific document in review
  In order to stop following the review of a document
  As an author
  I want to unpublish a specific document from the reviewing process

  @nominal
  Scenario: Unpublish a document from the reviewing process
    Given a document has been put to review by mistake
    When an author unpublishes the document
    Then the document is unpublished

  @nominal
  Scenario: If the document is not in review, it does nothing
    Given a document not in review
    When an author unpublishes the document
    Then the document is unpublished
