Feature: Repository API lifecycle
  Background:
    Given the externally configured API credentials

  Scenario: Phase 1 - Verify the repository is empty
    Given the configured repository is "test"
    When I delete all "behave-*" BagIts from the test repository
    Then no "behave-*" BagIts remain in the test repository
    When I delete the test repository log file
    Then the test repository log file does not exist
    When I request the repository status
    Then the API response status is 200
    And the repository has no bags or files
    When I list all bags
    Then the API response status is 200
    And no bags are listed

  Scenario: Phase 2 - Create a bag and upload a file
    When I create the test bag
    Then the API response status is 201
    And the API response state is "ok"
    When I create the second test bag
    Then the API response status is 201
    And the API response state is "ok"
    When I upload the first part of the test file
    Then the API response status is 201
    And the API response state is "ok"
    When I upload the second part of the test file
    Then the API response status is 201
    And the API response state is "ok"
    When I finalize the test file
    Then the API response status is 201
    And the API response state is "ok"
    And the append checksum matches the test file

  Scenario: Phase 3 - List bags and verify the checksum
    When I list all bags
    Then the API response status is 200
    And the test bag is listed
    When I list the first bag page
    Then the API response status is 200
    And the first bag page contains only the first test bag
    When I list the second bag page
    Then the API response status is 200
    And the second bag page contains only the second test bag
    When I list a page after the last bag
    Then the API response status is 200
    And the bag page is empty
    When I list the test bag
    Then the API response status is 200
    And the test file is listed
    When I request the test file checksum
    Then the API response status is 200
    And the returned checksum matches the test file

  Scenario: Phase 4 - Download the file and a file part
    When I download the test file
    Then the API response status is 200
    And the downloaded content matches the test file
    When I download the first half of the test file
    Then the API response status is 200
    When I download the second half of the test file
    Then the API response status is 200
    And the downloaded parts reconstruct the test file

  Scenario: Phase 5 - Inspect the repository log
    When I download the repository log
    Then the API response status is 200
    And the log contains the test file append entry