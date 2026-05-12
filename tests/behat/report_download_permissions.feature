@ou @ou_vle @report @report_embedquestion @javascript
Feature: Testing response file download permissions
  As a teacher or tutor
  I should see download actions based on the separate download capabilities

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | tutor1   | Tutor     | 1        | tutor1@example.com   |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | tutor1   | C1     | teacher |
    And the following "activities" exist:
      | activity | name        | idnumber | course |
      | page     | Test page 2 | page2    | C1     |
    And the following "question categories" exist:
      | contextlevel | reference | name           | idnumber |
      | Course       | C1        | Test questions | embed    |
    And the following "questions" exist:
      | questioncategory | qtype | name            | idnumber | template         |
      | Test questions   | essay | Second question | test2    | editorfilepicker |
    And the "embedquestion" filter is "on"

  Scenario: A tutor can download selected response files without delete permission
    Given "student1" has attempted embedded questions in "activity" context "page2":
      | pagename | question    | response                                                         |
      | C1:page2 | embed/test2 | <p>The <b>cat</b> sat on the mat. Then it ate a <b>frog</b>.</p> |
    And the following "permission overrides" exist:
      | capability                            | permission | role    | contextlevel | reference |
      | report/embedquestion:deleteanyattempt | Prevent    | teacher | Course       | C1        |
      | report/embedquestion:deletemyattempt  | Prevent    | teacher | Course       | C1        |
    When I am on the "page2" "report_embedquestion > Progress report for Activity" page logged in as "tutor1"
    Then the "Delete selected attempts" "button" should be disabled
    And "Download all response files" "button" should exist
    And the "Download all response files" "button" should be enabled
    And the "Download selected response files" "button" should be disabled
    And I click on "Select attempt" "checkbox" in the "student1" "table_row"
    And the "Delete selected attempts" "button" should be disabled
    And the "Download selected response files" "button" should be enabled
    And I click on "Download selected response files" "button"
    And I should see "Download to device"
    And "Download to device" "link" should exist
    And following "Download to device" should download between "1" and "500000" bytes

  Scenario: A tutor with only own download permission can only download their own selected response files
    Given "student1" has attempted embedded questions in "activity" context "page2":
      | pagename | question    | response                                                         |
      | C1:page2 | embed/test2 | <p>The <b>cat</b> sat on the mat. Then it ate a <b>frog</b>.</p> |
    And "tutor1" has attempted embedded questions in "activity" context "page2":
      | pagename | question    | response                                                            |
      | C1:page2 | embed/test2 | <p>The <b>dog</b> sat on the rug. Then it ate a <b>biscuit</b>.</p> |
    And the following "permission overrides" exist:
      | capability                              | permission | role    | contextlevel | reference |
      | report/embedquestion:deleteanyattempt   | Prevent    | teacher | Course       | C1        |
      | report/embedquestion:deletemyattempt    | Prevent    | teacher | Course       | C1        |
      | report/embedquestion:downloadanyattempt | Prevent    | teacher | Course       | C1        |
    When I am on the "page2" "report_embedquestion > Progress report for Activity" page logged in as "tutor1"
    Then "Download all response files" "button" should not exist
    And the "Delete selected attempts" "button" should be disabled
    And "Download selected response files" "button" should exist
    And the "Download selected response files" "button" should be disabled
    And "Select attempt" "checkbox" should not exist in the "student1" "table_row"
    And "Select attempt" "checkbox" should exist in the "tutor" "table_row"
    And I click on "Select attempt" "checkbox" in the "tutor" "table_row"
    And the "Download selected response files" "button" should be enabled
    And I click on "Download selected response files" "button"
    And I should see "Download to device"
    And "Download to device" "link" should exist
    And following "Download to device" should download between "1" and "500000" bytes

  Scenario: A tutor with only own download permission cannot download an unfinished own first attempt
    Given "tutor1" has started embedded question "embed/test2" in "activity" context "page2" with slot "1"
    And the following "permission overrides" exist:
      | capability                              | permission | role    | contextlevel | reference |
      | report/embedquestion:deleteanyattempt   | Prevent    | teacher | Course       | C1        |
      | report/embedquestion:deletemyattempt    | Prevent    | teacher | Course       | C1        |
      | report/embedquestion:downloadanyattempt | Prevent    | teacher | Course       | C1        |
    When I am on the "page2" "report_embedquestion > Progress report for Activity" page logged in as "tutor1"
    Then "Download all response files" "button" should not exist
    And the "Delete selected attempts" "button" should be disabled
    And "Download selected response files" "button" should exist
    And the "Download selected response files" "button" should be disabled
    And I should see "Not yet answered" in the "tutor" "table_row"
    And "Select attempt" "checkbox" should exist in the "tutor" "table_row"
    And I click on "Select attempt" "checkbox" in the "tutor" "table_row"
    And the "Download selected response files" "button" should be disabled

  Scenario: A tutor without delete or download permissions cannot select any attempts
    Given "student1" has attempted embedded questions in "activity" context "page2":
      | pagename | question    | response                                                         |
      | C1:page2 | embed/test2 | <p>The <b>cat</b> sat on the mat. Then it ate a <b>frog</b>.</p> |
    And "tutor1" has attempted embedded questions in "activity" context "page2":
      | pagename | question    | response                                                            |
      | C1:page2 | embed/test2 | <p>The <b>dog</b> sat on the rug. Then it ate a <b>biscuit</b>.</p> |
    And the following "permission overrides" exist:
      | capability                              | permission | role    | contextlevel | reference |
      | report/embedquestion:deleteanyattempt   | Prevent    | teacher | Course       | C1        |
      | report/embedquestion:deletemyattempt    | Prevent    | teacher | Course       | C1        |
      | report/embedquestion:downloadanyattempt | Prevent    | teacher | Course       | C1        |
      | report/embedquestion:downloadmyattempt  | Prevent    | teacher | Course       | C1        |
    When I am on the "page2" "report_embedquestion > Progress report for Activity" page logged in as "tutor1"
    Then "Download all response files" "button" should not exist
    And the "Delete selected attempts" "button" should be disabled
    And "Download selected response files" "button" should exist
    And the "Download selected response files" "button" should be disabled
    And "Select attempt" "checkbox" should not exist in the "student1" "table_row"
    And "Select attempt" "checkbox" should not exist in the "tutor" "table_row"

  Scenario: A student can download their own selected response file but cannot see Download all response files
    Given "student1" has attempted embedded questions in "activity" context "page2":
      | pagename | question    | response                                                         |
      | C1:page2 | embed/test2 | <p>The <b>cat</b> sat on the mat. Then it ate a <b>frog</b>.</p> |
    When I am on the "page2" "report_embedquestion > Progress report for Activity" page logged in as "student1"
    Then "Download all response files" "button" should not exist
    And "Download selected response files" "button" should exist
    And the "Download selected response files" "button" should be disabled
    And "Select attempt" "checkbox" should exist in the "Student 1" "table_row"
    And I click on "Select attempt" "checkbox" in the "Student 1" "table_row"
    And the "Download selected response files" "button" should be enabled
    And I click on "Download selected response files" "button"
    And I should see "Download to device"
    And "Download to device" "link" should exist
    And following "Download to device" should download between "1" and "500000" bytes

  Scenario: A user with delete-any permission but no download permission sees checkboxes but cannot enable Download selected
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And "student1" has attempted embedded questions in "activity" context "page2":
      | pagename | question    | response                                                         |
      | C1:page2 | embed/test2 | <p>The <b>cat</b> sat on the mat. Then it ate a <b>frog</b>.</p> |
    And the following "permission overrides" exist:
      | capability                              | permission | role           | contextlevel | reference |
      | report/embedquestion:downloadanyattempt | Prevent    | editingteacher | Course       | C1        |
      | report/embedquestion:downloadmyattempt  | Prevent    | editingteacher | Course       | C1        |
    When I am on the "page2" "report_embedquestion > Progress report for Activity" page logged in as "teacher1"
    Then "Download all response files" "button" should not exist
    And the "Delete selected attempts" "button" should be disabled
    And "Download selected response files" "button" should exist
    And the "Download selected response files" "button" should be disabled
    And "Select attempt" "checkbox" should exist in the "student1" "table_row"
    And I click on "Select attempt" "checkbox" in the "student1" "table_row"
    And the "Delete selected attempts" "button" should be enabled
    And the "Download selected response files" "button" should be disabled

  Scenario: A tutor cannot use Download all when every visible attempt is unfinished
    Given "student1" has started embedded question "embed/test2" in "activity" context "page2" with slot "1"
    When I am on the "page2" "report_embedquestion > Progress report for Activity" page logged in as "tutor1"
    Then "Download all response files" "button" should exist
    And the "Download all response files" "button" should be disabled
    And "Download selected response files" "button" should exist
    And the "Download selected response files" "button" should be disabled
    And "Select attempt" "checkbox" should exist in the "student1" "table_row"
    And I click on "Select attempt" "checkbox" in the "student1" "table_row"
    And the "Download selected response files" "button" should be disabled

  Scenario: A tutor only enables Download selected after choosing a downloadable attempt
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student2 | Student   | 2        | student2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student2 | C1     | student |
    And "student1" has started embedded question "embed/test2" in "activity" context "page2" with slot "1"
    And "student2" has attempted embedded questions in "activity" context "page2":
      | pagename | question    | response                                                             |
      | C1:page2 | embed/test2 | <p>The <b>owl</b> sat on the branch. Then it saw a <b>mouse</b>.</p> |
    When I am on the "page2" "report_embedquestion > Progress report for Activity" page logged in as "tutor1"
    Then "Download all response files" "button" should exist
    And the "Download all response files" "button" should be enabled
    And "Download selected response files" "button" should exist
    And the "Download selected response files" "button" should be disabled
    And I click on "Select attempt" "checkbox" in the "student1" "table_row"
    And the "Download selected response files" "button" should be disabled
    And I click on "Select attempt" "checkbox" in the "student2" "table_row"
    And the "Download selected response files" "button" should be enabled
