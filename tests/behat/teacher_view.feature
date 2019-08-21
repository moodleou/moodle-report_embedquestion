@report @report_embedquestion
Feature: Teachers can see their students progress on embedded questions.
  So that I can mentor them
  As a teacher
  I should be able to my students' progress with embedded questions

  Background:
    Given the following "users" exist:
      | username |
      | student  |
      | teacher  |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user    | course | role    |
      | student | C1     | student |
      | teacher | C1     | teacher |
    And the following "activities" exist:
      | activity | name      | idnumber | course |
      | page     | Test page | page1    | C1     |

  Scenario: A teacher can see their students progress in a course
    When I log in as "teacher"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Embedded questions progress" in current page administration
    Then I should see "Embedded question progress for Course 1"

  Scenario: A teacher can see their students progress in an activity
    When I log in as "teacher"
    And I am on "Course 1" course homepage
    And I follow "Test page"
    And I navigate to "Embedded questions progress" in current page administration
    Then I should see "Embedded question progress for Test page"
