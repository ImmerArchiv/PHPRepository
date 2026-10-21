Feature: Repository API authorization
  Background:
    Given the externally configured API credentials

  Scenario: Reject a request without a bearer token
    When I request the repository status without a bearer token
    Then the API response status is 401
    And the API error message is "use Authorization Header"

  Scenario: Reject an invalid bearer token
    When I request the repository status with an invalid bearer token
    Then the API response status is 401
    And the API error message is "Unauthorized Access"

  Scenario: Do not allow the test token to access the default repository
    When I request the default repository status with the test token
    Then the API response status is 401
    And the API error message is "Unauthorized Access"