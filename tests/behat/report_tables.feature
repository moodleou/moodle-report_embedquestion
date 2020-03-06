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
    Given the following "questions" exist:
      | questioncategory | qtype     | name           | idnumber |
      | Test questions   | truefalse | First question | test1    |
    And the "embedquestion" filter is "on"
    Given "student1" has attempted embedded questions in "activity" context "page1":
      | pagename | question    | response |
      | C1:page1 | embed/test1 | True     |
    And "student2" has attempted embedded questions in "activity" context "page1":
      | pagename | question    | response |
      | C1:page1 | embed/test1 | False    |
    And "student3" has attempted embedded questions in "course" context "Course 1":
      | pagename        | question    | response |
      | Course:Course 1 | embed/test1 | True     |

  Scenario: A teacher can see their students progress in a course
    When I log in as "teacher"
    And I am on "Course 1" course homepage
    Then I navigate to "Reports > Embedded questions progress" in current page administration
    And I should see "Embedded question progress for Course 1"
    Then I should see "Date filter"
    And I should see "Download table data as"
    And I should see "student1"
    And I should see "student2"
    And I should see "student3"

  Scenario: A teacher can see their students progress in an activity
    When I log in as "teacher"
    When I am on "Course 1" course homepage
    And I follow "Test page"
    And I navigate to "Embedded questions progress" in current page administration
    Then I should see "Embedded question progress for Test page"

  Scenario: A student can see his/her own progress in an activity
    When I log in as "student2"
    When I am on "Course 1" course homepage
    And I follow "Test page"
    And I navigate to "Embedded questions progress" in current page administration
    Then I should see "Embedded question progress for Test page"
    And I should see "Date filter"
    And I should see "Download table data as"
