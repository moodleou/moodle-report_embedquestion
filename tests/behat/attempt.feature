@report @report_embedquestion
Feature: Testing attempt detail view and delete feature
  As a teacher/student
  I should be able to view the attempt detail or delete it in Embed Question report

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "custom profile fields" exist:
      | datatype | shortname  | name      |
      | text     | food       | Fave food |
    And the following "users" exist:
      | username | firstname | lastname | email                | profile_field_food |
      | teacher1 | Teacher   | 1        | teacher1@example.com | bouillabaisse      |
      | tutor1   | Tutor     | 1        | tutor1@example.com   | tiramisu           |
      | student1 | Student   | 1        | student1@example.com | chocolate frog     |
      | student2 | Student   | 2        | student2@example.com | crisps             |
      | student3 | Student   | 3        | student3@example.com | lasagne            |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | teacher1 | C1     | editingteacher |
      | tutor1   | C1     | teacher        |
    And the following "activities" exist:
      | activity | name        | idnumber | course |
      | page     | Test page   | page1    | C1     |
      | page     | Test page 2 | page2    | C1     |
      | page     | Test page 3 | page3    | C1     |
    And the following "question categories" exist:
      | contextlevel | reference | name           | idnumber |
      | Course       | C1        | Test questions | embed    |
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
    And "student2" has attempted embedded questions in "activity" context "page1":
      | pagename | question    | response |
      | C1:page1 | embed/test1 | True     |
    And "student3" has attempted embedded questions in "activity" context "page1":
      | pagename | question    | response |
      | C1:page1 | embed/test1 | False    |
    And "tutor1" has attempted embedded questions in "activity" context "page1":
      | pagename | question    | response |
      | C1:page1 | embed/test1 | True     |
    And the following "permission overrides" exist:
      | capability                   | permission | role    | contextlevel | reference |
      | moodle/site:viewuseridentity | Allow      | student | System       |           |
    And the following config values are set as admin:
      | showuseridentity | username,profile_field_food |

  @javascript
  Scenario: A teacher can see their students attempt summary in an activity
    When I am on the "C1" "report_embedquestion > Progress report for Course" page logged in as "teacher1"
    Then I should see "student1"
    And I click on "Attempt summary" "link" in the "student1" "table_row"
    And I should see "Attempt summary for:"
    And I should see "Next" in the ".pagination" "css_element"
    And I start watching to see if a new page loads
    And I click on "Next" "link" in the ".pagination" "css_element"
    And a new page should have loaded since I started watching
    And I should see "Attempt summary for:"

  @javascript
  Scenario: Students can see their attempt detail in a activity
    When I am on the "page1" "report_embedquestion > Progress report for Activity" page logged in as "student2"
    And I click on "Attempt summary" "link" in the "student2" "table_row"
    And I click on "Attempt detail view" "link" in the "Correct" "table_row"
    And I switch to the browser tab opened by the app
    Then I should see "First question"
    And "quizreviewsummary" "table" should exist
    And the field "True" matches value "1"
    And I should see "The correct answer is 'True'"
    And I should see "Response history"

  @javascript
  Scenario: Teachers can see their student's attempts detail in a course
    When I am on the "C1" "report_embedquestion > Progress report for Course" page logged in as "teacher1"
    And I click on "Attempt summary" "link" in the "student3" "table_row"
    And I click on "Attempt detail view" "link" in the "Incorrect" "table_row"
    And I switch to the browser tab opened by the app
    Then I should see "First question"
    And "quizreviewsummary" "table" should exist
    And the field "False" matches value "1"
    And I should see "The correct answer is 'True'"
    And I should see "Response history"
    And I close the browser tab opened by the app
    And I press the "back" button in the browser
    And I click on "Attempt summary" "link" in the "student2" "table_row"
    And I click on "Attempt detail view" "link" in the "Correct" "table_row"
    And I switch to the browser tab opened by the app
    And I should see "First question"
    And "quizreviewsummary" "table" should exist
    And the field "True" matches value "1"
    And I should see "The correct answer is 'True'"
    And I should see "Response history"

  @javascript
  Scenario: A student can delete his/her own progress in an activity
    When I am on the "page1" "report_embedquestion > Progress report for Activity" page logged in as "student2"
    Then "Select attempt" "checkbox" should exist in the "student2" "table_row"
    And "Delete selected attempts" "button" should exist
    And the "Delete selected attempts" "button" should be disabled
    And I click on "Select attempt" "checkbox" in the "student2" "table_row"
    And the "Delete selected attempts" "button" should be enabled
    And I click on "Delete selected attempts" "button"
    And I should see "Are you absolutely sure you want to completely delete these attempts?" in the "Confirmation" "dialogue"
    And I click on "Yes" "button"
    And I should see "Nothing to display"

  @javascript
  Scenario: A student cannot delete his/her own progress in an activity if he/she does not have the permission
    Given the following "permission overrides" exist:
      | capability                           | permission | role    | contextlevel | reference |
      | report/embedquestion:deletemyattempt | Prevent    | student | Course       | C1        |
    When I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test page"
    And I navigate to "Embedded questions progress" in current page administration
    Then "Select attempt" "checkbox" should not exist in the "student2" "table_row"
    And "Delete selected attempts" "button" should exist

  @javascript
  Scenario: A tutor can see their students progress but only can delete his/her own progress in an activity
    When I am on the "page1" "report_embedquestion > Progress report for Activity" page logged in as "tutor1"
    Then "Select attempt" "checkbox" should exist in the "tutor" "table_row"
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
    When I am on the "page1" "report_embedquestion > Progress report for Activity" page logged in as "teacher1"
    Then "Select attempt" "checkbox" should exist in the "tutor" "table_row"
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

  @javascript
  Scenario: A teacher can download their students progress in an activity for question type essay
    Given the following "questions" exist:
      | questioncategory | qtype | name            | idnumber | template         |
      | Test questions   | essay | Second question | test2    | editorfilepicker |
    And "student1" has attempted embedded questions in "activity" context "page2":
      | pagename | question    | response                                                         |
      | C1:page2 | embed/test2 | <p>The <b>cat</b> sat on the mat. Then it ate a <b>frog</b>.</p> |
    When I am on the "page1" "report_embedquestion > Progress report for Activity" page logged in as "teacher1"
    Then "Download all response files" "button" should not exist
    And "Download selected response files" "button" should not exist
    And I log out
    And I am on the "page2" "report_embedquestion > Progress report for Activity" page logged in as "teacher1"
    And "Download all response files" "button" should exist
    And "Download selected response files" "button" should exist
    And the "Download selected response files" "button" should be disabled
    And I click on "Select attempt" "checkbox" in the "student1" "table_row"
    And the "Download selected response files" "button" should be enabled
    And I click on "Download selected response files" "button"
    And I should see "Download zip or export the response files"
    And I should see "Download to device"
    And "Download to device" "link" should exist
    And following "Download to device" should download between "1" and "300" bytes

  @javascript
  Scenario: A teacher can download their students progress in an activity for question type recordrtc
    Given I check the "recordrtc" question type already installed for embed question
    And the following "questions" exist:
      | questioncategory | qtype     | name           | idnumber | template |
      | Test questions   | recordrtc | Third question | test3    | audio    |
    And "student1" has attempted embedded questions in "activity" context "page3":
      | pagename | question    | response |
      | C1:page3 | embed/test3 |          |
    When I am on the "page3" "report_embedquestion > Progress report for Activity" page logged in as "teacher1"
    Then "Download all response files" "button" should exist
    And "Download selected response files" "button" should exist
    And the "Download selected response files" "button" should be disabled
    And I click on "Select attempt" "checkbox" in the "student1" "table_row"
    And the "Download selected response files" "button" should be enabled
    And I click on "Download selected response files" "button"
    And I should see "Download zip or export the response files"
    And I should see "Download to device"
    And "Download to device" "link" should exist
    And following "Download to device" should download between "1" and "40000" bytes

  @javascript
  Scenario: A teacher can download their students previous finished attempt progress in an activity for question type recordrtc
    Given I check the "recordrtc" question type already installed for embed question
    And the following "questions" exist:
      | questioncategory | qtype     | name            | idnumber | template |
      | Test questions   | recordrtc | Fourth question | test4    | audio    |
    And the following "filter_embedquestion > Pages with embedded question" exist:
      | name        | idnumber | course | question    | slot |
      | Test page 4 | page4    | C1     | embed/test4 | 1    |
    # Student1 has started the attempt and submitted response.
    And "student1" has attempted embedded questions in "activity" context "page4":
      | pagename | question    | response |
      | C1:page4 | embed/test4 |          |
    # The student1 has only started the attempt, but not submitted anything
    And "student1" has started embedded question "embed/test4" in "activity" context "page4" with slot "2"
    And "student2" has started embedded question "embed/test4" in "activity" context "page4" with slot "1"
    When I am on the "page4" "report_embedquestion > Progress report for Activity" page logged in as "teacher1"
    Then I should see "Not yet answered" in the "student2" "table_row"
    And I click on "Select attempt" "checkbox" in the "student2" "table_row"
    And the "Download selected response files" "button" should be disabled
    Then I should see "Not yet answered" in the "student1" "table_row"
    And I click on "Select attempt" "checkbox" in the "student1" "table_row"
    And the "Download selected response files" "button" should be enabled
    And I click on "Download selected response files" "button"
    And I should see "Download zip or export the response files"
    And I should see "Download to device"
    And "Download to device" "link" should exist
    And following "Download to device" should download between "1" and "40000" bytes

  @javascript
  Scenario: Teacher can see custom user fields columns as additional user identity
    When I am on the "C1" "report_embedquestion > Progress report for Course" page logged in as "admin"
    Then I should see "chocolate frog" in the "student1" "table_row"
    And I should see "crisps" in the "student2" "table_row"
