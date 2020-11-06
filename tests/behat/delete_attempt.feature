@report @report_embedquestion
Feature: Teachers can delete their students progress on embedded questions.
  So that I can mentor them
  As a teacher
  I should be able to delete my students' progress with embedded questions

  Background:
    Given the following "users" exist:
      | username |
      | student1 |
      | student2 |
      | tutor    |
      | teacher  |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | tutor    | C1     | teacher        |
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
    And "tutor" has attempted embedded questions in "activity" context "page1":
      | pagename | question    | response |
      | C1:page1 | embed/test1 | True    |

  @javascript
  Scenario: A student can delete his/her own progress in an activity
    When I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test page"
    And I navigate to "Embedded questions progress" in current page administration
    Then I should see "Embedded question progress for Test page"
    And "Select attempt" "checkbox" should exist in the "student2" "table_row"
    And "Delete selected attempts" "button" should exist
    And the "Delete selected attempts" "button" should be disabled
    And I click on "Select attempt" "checkbox" in the "student2" "table_row"
    And the "Delete selected attempts" "button" should be enabled
    And I click on "Delete selected attempts" "button"
    And I should see "Are you absolutely sure you want to completely delete these attempts?" in the "Confirmation" "dialogue"
    And I click on "Yes" "button"
    And I should not see "student2"

  @javascript
  Scenario: A tutor can see their students progress but only can delete his/her own progress in an activity
    When I log in as "tutor"
    And I am on "Course 1" course homepage
    And I follow "Test page"
    And I navigate to "Embedded questions progress" in current page administration
    Then I should see "Embedded question progress for Test page"
    And I should see "student1"
    And I should see "student2"
    And I should see "tutor"
    And "Select attempt" "checkbox" should exist in the "tutor" "table_row"
    And "Select attempt" "checkbox" should not exist in the "student1" "table_row"
    And "Select attempt" "checkbox" should not exist in the "student2" "table_row"
    And "Delete selected attempts" "button" should exist
    And the "Delete selected attempts" "button" should be disabled
    And I click on "Select attempt" "checkbox" in the "tutor" "table_row"
    And the "Delete selected attempts" "button" should be enabled
    And I click on "Delete selected attempts" "button"
    And I should see "Are you absolutely sure you want to completely delete these attempts?" in the "Confirmation" "dialogue"
    And I click on "Yes" "button"
    And I should not see "tutor"
    And I should see "student1"
    And I should see "student2"

  @javascript
  Scenario: A teacher can see their students progress and can delete their students progress in an activity
    When I log in as "teacher"
    And I am on "Course 1" course homepage
    And I follow "Test page"
    And I navigate to "Embedded questions progress" in current page administration
    Then I should see "Embedded question progress for Test page"
    And I should see "student1"
    And I should see "student2"
    And I should see "tutor"
    And "Select attempt" "checkbox" should exist in the "tutor" "table_row"
    And "Select attempt" "checkbox" should exist in the "student1" "table_row"
    And "Select attempt" "checkbox" should exist in the "student2" "table_row"
    And "Delete selected attempts" "button" should exist
    And the "Delete selected attempts" "button" should be disabled
    And I click on "Select attempt" "checkbox" in the "student1" "table_row"
    And I click on "Select attempt" "checkbox" in the "student2" "table_row"
    And I click on "Select attempt" "checkbox" in the "tutor" "table_row"
    And the "Delete selected attempts" "button" should be enabled
    And I click on "Delete selected attempts" "button"
    And I should see "Are you absolutely sure you want to completely delete these attempts?" in the "Confirmation" "dialogue"
    And I click on "Yes" "button"
    And I should not see "tutor"
    And I should not see "student1"
    And I should not see "student2"
