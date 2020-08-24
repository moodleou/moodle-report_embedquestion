@report @report_embedquestion
Feature: User can see their report attempt in embedded question.
  In order to view an finished attempt
  As a user
  I should be able to access my report attempt in embedded question detail page

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | teacher1 | C1     | editingteacher |
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
  Scenario: Students can access to their report attempt in a activity
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test page"
    When I navigate to "Embedded questions progress" in current page administration
    Then I should see "Embedded question progress for Test page"
    And I should see "student1"
    And I click on "Attempt summary" "link" in the "student1" "table_row"
    And I click on "Attempt detail view" "link" in the "Correct" "table_row"
    And I switch to the browser tab opened by the app
    And I should see "First question"
    And "quizreviewsummary" "table" should exist
    And the field "True" matches value "1"
    And I should see "The correct answer is 'True'"
    And I should see "Response history"

  @javascript
  Scenario: Teacher can access to their own report attempt in a course
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    When I navigate to "Reports > Embedded questions progress" in current page administration
    Then I should see "Embedded question progress for Course 1"
    And I should see "student1"
    And I should see "student2"
    And I click on "Attempt summary" "link" in the "student2" "table_row"
    And I click on "Attempt detail view" "link" in the "Incorrect" "table_row"
    And I switch to the browser tab opened by the app
    And I should see "First question"
    And "quizreviewsummary" "table" should exist
    And the field "False" matches value "1"
    And I should see "The correct answer is 'True'"
    And I should see "Response history"
    And I close the browser tab opened by the app
    And I press the "back" button in the browser
    And I click on "Attempt summary" "link" in the "student1" "table_row"
    And I click on "Attempt detail view" "link" in the "Correct" "table_row"
    And I switch to the browser tab opened by the app
    And I should see "First question"
    And "quizreviewsummary" "table" should exist
    And the field "True" matches value "1"
    And I should see "The correct answer is 'True'"
    And I should see "Response history"
