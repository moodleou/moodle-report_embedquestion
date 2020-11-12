@report @report_embedquestion
Feature: Teachers can see their students attempt summary on embedded questions.
  So that I can mentor them
  As a teacher
  I should be able to my students' attempt summary with embedded questions

  Background:
    Given the following "users" exist:
      | username |
      | student1 |
      | teacher  |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
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
      | pagename | question    | response | slot |
      | C1:page1 | embed/test1 | True     | 1    |
      | C1:page1 | embed/test1 | False    | 2    |
      | C1:page1 | embed/test1 | True     | 3    |
      | C1:page1 | embed/test1 | False    | 4    |
      | C1:page1 | embed/test1 | True     | 5    |
      | C1:page1 | embed/test1 | False    | 6    |

  @javascript
  Scenario: A teacher can see their students attempt summary in an activity
    When I log in as "teacher"
    And I am on "Course 1" course homepage
    And I follow "Test page"
    When I navigate to "Embedded questions progress" in current page administration
    Then I should see "student1"
    And I click on "Attempt summary" "link" in the "student1" "table_row"
    And I should see "Attempt summary for:"
    And I should see "Next" in the ".pagination" "css_element"
    And I start watching to see if a new page loads
    And I click on "Next" "link" in the ".pagination" "css_element"
    And a new page should have loaded since I started watching
    And I should see "Attempt summary for:"
