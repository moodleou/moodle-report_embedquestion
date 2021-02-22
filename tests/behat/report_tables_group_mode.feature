@report @report_embedquestion
Feature: Testing Embedded Question report with group mode
  So that I can mentor them
  As a teacher
  I should be able to my students' progress with embedded questions

  Background:
    Given the following "users" exist:
      | username |
      | student1 |
      | student2 |
      | student3 |
      | tutor    |
      | teacher  |
    And the following "courses" exist:
      | fullname | shortname | groupmode |
      | Course 1 | C1        | 1         |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
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
      | student3 | G2    |
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
    And "student3" has attempted embedded questions in "activity" context "page1":
      | pagename | question    | response |
      | C1:page1 | embed/test1 | True     |

  @javascript
  Scenario: A teacher can see all students progress in a course, separate groups and filter should not be changed when teacher changes the table preferences
    Given I am on the "C1" "report_embedquestion > Progress report for Course" page logged in as "teacher"
    Then I should see "Embedded question progress for Course 1"
    And I set the field "Separate groups" to "All participants"
    And I should see "student1"
    And I should see "student2"
    And I set the field "Separate groups" to "Group 1"
    And I should see "student1"
    And I should not see "student2"
    And I should not see "student3"
    And I set the field "Attempts made in the" to "Last 2 days"
    And I press "Show report"
    And the field "Separate groups" matches value "Group 1"
    And I set the field "Separate groups" to "Group 2"
    And the field "Attempts made in the" matches value "Last 2 days"
    And I should not see "student1"
    And I should see "student2"
    And I should see "student3"
    And I set the field "Page size" to "1"
    And I press "Show report"
    And I click on "Next" "link"
    And the field "Separate groups" matches value "Group 2"
    And the field "Attempts made in the" matches value "Last 2 days"

  @javascript
  Scenario: A tutor can see their students progress in an activity
    Given I am on the "page1" "report_embedquestion > Progress report for Activity" page logged in as "tutor"
    Then I should see "Embedded question progress for Test page"
    And I should see "Group 1"
    And I should see "student1"
    And I should not see "student2"
