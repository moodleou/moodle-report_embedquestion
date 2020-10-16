@report @report_embedquestion
Feature: Teachers or tutor can see their students progress in their group on embedded questions.
  So that I can mentor them
  As a teacher
  I should be able to my students' progress with embedded questions

  Background:
    Given the following "users" exist:
      | username |
      | student1 |
      | student2 |
      | tutor    |
      | teacher  |
    And the following "courses" exist:
      | fullname | shortname | groupmode |
      | Course 1 | C1        | 1         |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | tutor    | C1     | teacher        |
      | teacher  | C1     | editingteacher |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
      | Group 2 | C1     | G2       |
    And the following "group members" exist:
      | user     | group |
      | student1 | G1    |
      | teacher  | G1    |
      | tutor    | G1    |
      | student2 | G2    |
      | teacher  | G2    |
    And the following "activities" exist:
      | activity | name      | idnumber | course |
      | page     | Test page | page1    | C1     |
    And the following "question categories" exist:
      | contextlevel | reference | name           | idnumber |
      | Course       | C1        | Test questions | embed    |
    And the following "questions" exist:
      | questioncategory | qtype     | name           | idnumber |
      | Test questions   | truefalse | First question | test1    |
    And the "embedquestion" filter is "on"
    And "student1" has attempted embedded questions in "activity" context "page1":
      | pagename | question    | response |
      | C1:page1 | embed/test1 | True     |
    And "student2" has attempted embedded questions in "activity" context "page1":
      | pagename | question    | response |
      | C1:page1 | embed/test1 | False    |

  @javascript
  Scenario: A teacher can see all students progress in a course
    Given I log in as "teacher"
    And I am on "Course 1" course homepage
    And I follow "Test page"
    When I navigate to "Embedded questions progress" in current page administration
    Then I should see "Embedded question progress for Test page"
    And I set the field "Separate groups" to "All participants"
    And I should see "student1"
    And I should see "student2"
    And I set the field "Separate groups" to "Group 1"
    And I should see "student1"
    And I should not see "student2"
    And I set the field "Separate groups" to "Group 2"
    And I should see "student2"
    And I should not see "student1"

  @javascript
  Scenario: A tutor can see their students progress in an activity
    Given I log in as "tutor"
    And I am on "Course 1" course homepage
    And I follow "Test page"
    When I navigate to "Embedded questions progress" in current page administration
    Then I should see "Embedded question progress for Test page"
    And I should see "Group 1"
    And I should see "student1"
    And I should not see "student2"
