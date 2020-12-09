@report @report_embedquestion
Feature: Teachers can see their students progress on embedded questions.
  So that I can mentor them
  As a teacher
  I should be able to my students' progress with embedded questions

  Background:
    Given the following "users" exist:
      | username |
      | student1 |
      | student2 |
      | student3 |
      | teacher  |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | teacher  | C1     | editingteacher |
    And the following "activities" exist:
      | activity | name      | idnumber | course |
      | page     | Test page | page1    | C1     |
    And the following "question categories" exist:
      | contextlevel | reference | name          | idnumber |
      | Course       | C1        | Test questions| embed    |
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
    And "student3" has attempted embedded questions in "course" context "Course 1":
      | pagename        | question    | response |
      | Course:Course 1 | embed/test1 | True     |

  Scenario: A teacher can see their students progress in a course
    Given I am on the "C1" "report_embedquestion > Progress report for Course" page logged in as "teacher"
    Then I should see "Embedded question progress for Course 1"
    And I should see "Date filter"
    And ".groupselector" "css_element" should not exist
    And I should see "Download table data as"
    And I should see "student1"
    And I should see "Correct" in the "student1" "table_row"
    And "Correct" "icon" should exist in the "student1" "table_row"
    And I should see "student2"
    And I should see "Incorrect" in the "student2" "table_row"
    And "Incorrect" "icon" should exist in the "student2" "table_row"
    And I should see "student3"
    And I should see "Correct" in the "student3" "table_row"
    And "Correct" "icon" should exist in the "student3" "table_row"

  Scenario: A teacher can see their students progress in an activity
    Given I am on the "page1" "report_embedquestion > Progress report for Activity" page logged in as "teacher"
    Then I should see "Embedded question progress for Test page"
    And I should see "Date filter"
    And ".groupselector" "css_element" should not exist
    And I should see "Download table data as"
    And I should see "student1"
    And I should see "Correct" in the "student1" "table_row"
    And "Correct" "icon" should exist in the "student1" "table_row"
    And I should see "student2"
    And I should see "Incorrect" in the "student2" "table_row"
    And "Incorrect" "icon" should exist in the "student2" "table_row"
    And I should not see "student3"

  Scenario: A student can only see his/her own progress in an activity
    Given I am on the "page1" "report_embedquestion > Progress report for Activity" page logged in as "student2"
    Then I should see "Embedded question progress for Test page"
    And I should see "Date filter"
    And I should see "Download table data as"
    And I should see "student2"
    And I should not see "student1"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test page"
    And I navigate to "Embedded questions progress" in current page administration
    And I should see "student1"
    And I should not see "student2"
