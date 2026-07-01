@ou @ou_vle @report @report_embedquestion @javascript
Feature: Embedded question version changes during in-progress attempts
  In order to keep in-progress embedded question attempts consistent after question edits
  As a student
  I need the attempt to update, restart, or show a restart message based on compatibility and permissions

  Background:
    Given the following "users" exist:
      | username          | firstname | lastname  | email                |
      | teacher           | Terry1    | Teacher1  | teacher1@example.com |
      | student           | Sam1      | Student1  | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user              | course | role              |
      | teacher           | C1     | editingteacher    |
      | student           | C1     | student           |
    And the following "activities" exist:
      | activity | name    | intro           | course | idnumber |
      | qbank    | Qbank 1 | Question bank 1 | C1     | qbank1   |
    And the following "question categories" exist:
      | contextlevel    | reference | name           | idnumber |
      | Activity module | qbank1    | Test questions | embed    |
    And the following "questions" exist:
      | questioncategory | qtype       | template    | name                | idnumber |
      | Test questions   | truefalse   |             | True/false question | test1    |
      | Test questions   | multichoice | one_of_four | Choice question     | test2    |
    And the following "filter_embedquestion > Pages with embedded question" exist:
      | name           | idnumber | course | question    |
      | Truefalse page | page1    | C1     | embed/test1 |
      | Choice page    | page2    | C1     | embed/test2 |
    And the "embedquestion" filter is "on"

  Scenario: Student returning to an in-progress attempt sees the compatible latest version immediately after a question edit
    Given "student" has started embedded question "embed/test1" in "activity" context "page1"
    # Edit the question text only — this produces a compatible new version.
    And I am on the "True/false question" "core_question > edit" page logged in as teacher
    And I set the field "Question text" to "Edited question text on return."
    And I press "id_submitbutton"
    When I am on the "Truefalse page" "page activity" page logged in as student
    And I switch to "filter_embedquestion-iframe" iframe
    # The attempt is silently updated to the new version — no warning or restart button.
    Then I should see "Edited question text on return."
    And I should not see "This question has been changed since you started working on it."
    And I should not see "Restart with the latest version"

  Scenario: Student returning to an in-progress attempt sees the absolute latest version after multiple sequential compatible edits
    Given "student" has started embedded question "embed/test1" in "activity" context "page1"
    # First compatible edit.
    And I am on the "True/false question" "core_question > edit" page logged in as teacher
    And I set the field "Question text" to "Second version of the question."
    And I press "id_submitbutton"
    # Second compatible edit via the Edit question link inside the iframe.
    And I am on the "Truefalse page" "page activity" page logged in as teacher
    And I switch to "filter_embedquestion-iframe" iframe
    And I follow "Edit question"
    And I switch to the main frame
    And I set the field "Question text" to "Third version of the question."
    And I press "id_submitbutton"
    When I am on the "Truefalse page" "page activity" page logged in as student
    And I switch to "filter_embedquestion-iframe" iframe
    # The attempt must show the absolute latest version, not the intermediate one.
    Then I should see "Third version of the question."
    And I should not see "Second version of the question."
    And I should not see "This question has been changed since you started working on it."
    And I should not see "Restart with the latest version"

  Scenario: Student sees incompatible version warning again after reloading the page without restarting
    Given "student" has started embedded question "embed/test2" in "activity" context "page2"
    # Remove a choice to produce an incompatible new version.
    And I am on the "Choice question" "core_question > edit" page logged in as teacher
    And I set the following fields to these values:
      | Choice 4      |  |
      | id_feedback_3 |  |
    And I press "id_submitbutton"
    When I am on the "Choice page" "page activity" page logged in as student
    And I switch to "filter_embedquestion-iframe" iframe
    # Warning appears on first visit after the incompatible edit.
    And I should see "This question has been changed since you started working on it."
    # Reload the page without clicking restart — the warning must persist.
    And I switch to the main frame
    And I am on the "Choice page" "page activity" page logged in as student
    And I switch to "filter_embedquestion-iframe" iframe
    Then I should see "This question has been changed since you started working on it."
    And I should see "Restart with the latest version"

  Scenario: A student who has a completed attempt still sees a version change warning after an incompatible question edit
    # Student has already completed an attempt on the multichoice question.
    Given "student" has attempted embedded questions in "activity" context "page2":
      | pagename | question    | response |
      | C1:page2 | embed/test2 | One      |
    # Remove a choice to produce an incompatible new version.
    And I am on the "Choice question" "core_question > edit" page logged in as teacher
    And I set the following fields to these values:
      | Choice 4      |  |
      | id_feedback_3 |  |
    And I press "id_submitbutton"
    When I am on the "Choice page" "page activity" page logged in as student
    And I switch to "filter_embedquestion-iframe" iframe
    # Even a completed attempt must show the version-change warning and restart button.
    Then I should see "This question has been changed since you started working on it."
    And I should see "Restart with the latest version"
