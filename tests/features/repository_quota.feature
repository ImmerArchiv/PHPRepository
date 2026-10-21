Feature: Repository storage quota
  Scenario: Accept exactly one MiB and reject the next byte
    Given the externally configured API credentials
    And the configured repository is "test"
    When I delete all "behave-*" BagIts from the test repository
    Then no "behave-*" BagIts remain in the test repository
    When I delete the test repository log file
    Then the test repository log file does not exist
    When I request the repository status
    Then the API response status is 200
    And the repository quota is exactly one MiB
    And the repository has no bags or files
    When I create the quota test bag
    Then the API response status is 201
    And the API response state is "ok"
    When I upload a file of exactly one MiB
    Then the API response status is 201
    And the API response state is "ok"
    When I finalize the one MiB file
    Then the API response status is 201
    And the API response state is "ok"
    When I request the repository status
    Then the API response status is 200
    And the repository contains exactly one MiB of files
    When I upload one byte beyond the quota
    Then the API response status is 400
    And the API error message is "Quota reached"
    When I request the repository status
    Then the API response status is 200
    And the repository contains exactly one MiB of files