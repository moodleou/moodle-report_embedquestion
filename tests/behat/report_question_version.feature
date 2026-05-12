@ou @ou_vle @report @report_embedquestion
Feature: Test multiple versions of embedded questions in the activity
  As a teacher
  I want to be able to embed questions in the activity and have students see a warning if the question changes while they are working on it

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher  | Terry1    | Teacher1 | teacher1@example.com |
      | student  | Sam1      | Student1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
      | student | C1     | student        |
    And the following "activities" exist:
      | activity | name    | intro           | course | idnumber |
      | qbank    | Qbank 1 | Question bank 1 | C1     | qbank1   |
    And the following "question categories" exist:
      | contextlevel    | reference | name           | idnumber |
      | Activity module | qbank1    | Test questions | embed    |
    And the "embedquestion" filter is "on"

  @javascript
  Scenario: Student clicks Check and then sees new version seamlessly after a compatible question edit
    Given the following "questions" exist:
      | questioncategory | qtype     | name           | idnumber |
      | Test questions   | truefalse | First question | test1    |
    And the following "filter_embedquestion > Pages with embedded question" exist:
      | name      | idnumber | course | question    |
      | Test page | page1    | C1     | embed/test1 |
    And "student" has started embedded question "embed/test1" in "activity" context "page1"
    # Edit the question text to produce a compatible new version.
    And I am on the "First question" "core_question > edit" page logged in as teacher
    And I set the field "Question text" to "Edited question text."
    And I press "id_submitbutton"
    When I am on the "Test page" "page activity" page logged in as student
    And I switch to "filter_embedquestion-iframe" iframe
    And I click on "True" "radio" in the "Edited question text." "question"
    And I press "Check"
    Then I should see "Edited question text."
    And I should not see "This question has been changed since you started working on it."

  @javascript
  Scenario: Teacher with in-progress attempt is auto-restarted after an incompatible question edit
    Given the following "questions" exist:
      | questioncategory | qtype       | template    | name           | idnumber |
      | Test questions   | multichoice | one_of_four | First question | test1    |
    And the following "filter_embedquestion > Pages with embedded question" exist:
      | name      | idnumber | course | question    |
      | Test page | page1    | C1     | embed/test1 |
    And "teacher" has started embedded question "embed/test1" in "activity" context "page1"
    # Edit the question to remove the fourth choice, making the new version incompatible.
    And I am on the "First question" "core_question > edit" page logged in as teacher
    And I set the following fields to these values:
      | Choice 4      |  |
      | id_feedback_3 |  |
    And I press "id_submitbutton"
    When I am on the "Test page" "page activity" page
    And I switch to "filter_embedquestion-iframe" iframe
    # Teacher is auto-restarted with the new version, no warning message shown.
    Then I should not see "This question has been changed since you started working on it."
    And I should not see "Restart with the latest version"

  @javascript
  Scenario: Student with in-progress attempt sees warning after an incompatible question edit
    Given the following "questions" exist:
      | questioncategory | qtype       | template    | name           | idnumber |
      | Test questions   | multichoice | one_of_four | First question | test1    |
    And the following "filter_embedquestion > Pages with embedded question" exist:
      | name      | idnumber | course | question    |
      | Test page | page1    | C1     | embed/test1 |
    And "student" has started embedded question "embed/test1" in "activity" context "page1"
    # Edit the question to remove the fourth choice, making the new version incompatible.
    And I am on the "First question" "core_question > edit" page logged in as teacher
    And I set the following fields to these values:
      | Choice 4      |  |
      | id_feedback_3 |  |
    And I press "id_submitbutton"
    When I am on the "Test page" "page activity" page logged in as student
    And I switch to "filter_embedquestion-iframe" iframe
    # Student sees old version with warning and restart button.
    Then I should see "This question has been changed since you started working on it."
    And I should see "Restart with the latest version"
    And I should see "Which is the oddest number?"
    # Student clicks the restart button and sees the new version with 3 choices.
    When I press "Restart with the latest version"
    Then I should not see "This question has been changed since you started working on it."
    And I should not see "Restart with the latest version"
    And I should see "Which is the oddest number?"
    And I should not see "Four"
