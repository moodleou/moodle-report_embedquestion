@report @report_embedquestion
Feature: Teachers can see their students progress on embedded questions.
  So that I can mentor them
  As a teacher
  I should be able to my students' progress with embedded questions

  Background:
    Given the following "users" exist:
      | username | firstname | lastname| idnumber | phone1      |
      | student1 | Student   |    A    |    s1    | 12345678911 |
      | student2 | Student   |    B    |    s2    | 12345678811 |
      | student3 | Student   |    C    |    s3    | 12345678711 |
      | student4 | Student   |    D    |    s4    | 12345678611 |
      | student5 | Student   |    E    |    s5    | 12345678611 |
      | teacher  | Teacher   |    F    |    t1    | 12345678511 |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
      | student5 | C1     | student        |
      | teacher  | C1     | editingteacher |
    And the following "activities" exist:
      | activity | name        | idnumber | course |
      | page     | Test page 1 | page1    | C1     |
    And the following "question categories" exist:
      | contextlevel | reference | name          | idnumber |
      | Course       | C1        | Test questions| embed    |
    And the following "questions" exist:
      | questioncategory | qtype     | name           | idnumber |
      | Test questions   | truefalse | First question | test1    |
    And the following "filter_embedquestion > Pages with embedded question" exist:
      | name        | idnumber | course | question    |
      | Test page 2 | page2    | C1     | embed/test1 |
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
    And "student4" has started embedded question "embed/test1" in "activity" context "page2"
    And "student5" has attempted embedded questions in "activity" context "page1":
      | pagename | question    | response |
      | C1:page1 | embed/test1 | True     |
    And "student5" has attempted embedded questions in "course" context "Course 1":
      | pagename        | question    | response |
      | Course:Course 1 | embed/test1 | True     |
    And "student5" has started embedded question "embed/test1" in "activity" context "page2"
    And the following "permission overrides" exist:
      | capability                                   | permission | role    | contextlevel | reference |
      | moodle/site:viewuseridentity                 | Allow      | student | System       |           |
    And the following config values are set as admin:
      | showuseridentity | username |

  Scenario: A teacher can see their students progress in a course
    Given I am on the "C1" "report_embedquestion > Progress report for Course" page logged in as "teacher"
    Then I should see "Embedded question progress for Course 1"
    And ".groupselector" "css_element" should not exist
    And I should see "Download table data as"
    And I should see "student1"
    And I should see "Correct" in the "student1" "table_row"
    And "Correct" "icon" should exist in the "student1" "table_row"
    And I should see "student2"
    And I should see "Incorrect" in the "student2" "table_row"
    And "Incorrect" "icon" should exist in the "student2" "table_row"
    And I should see "student3"
    And I should see "student4"
    And I should see "Not yet answered" in the "student4" "table_row"
    And I should see "Correct" in the "student3" "table_row"
    And "Correct" "icon" should exist in the "student3" "table_row"
    And I click on "Preview" "link" in the "student1" "table_row"
    And I should see "First question"
    # Question text - reasonable evidence we actually got to the preview page.
    And I should see "The answer is true."

  Scenario: A teacher can see their students progress in an activity
    Given I am on the "page1" "report_embedquestion > Progress report for Activity" page logged in as "teacher"
    Then I should see "Embedded question progress for Test page"
    And ".groupselector" "css_element" should not exist
    And I should see "Download table data as"
    And I should see "student1"
    And I should see "Correct" in the "student1" "table_row"
    And "Correct" "icon" should exist in the "student1" "table_row"
    And I should see "student2"
    And I should see "Incorrect" in the "student2" "table_row"
    And "Incorrect" "icon" should exist in the "student2" "table_row"
    And I should not see "student3"
    And I should not see "student4"
    And I click on "Preview" "link" in the "student1" "table_row"
    And I should see "First question"
    # Question text - reasonable evidence we actually got to the preview page.
    And I should see "The answer is true."

  Scenario: A student can only see his/her own progress in an activity
    Given I am on the "page1" "report_embedquestion > Progress report for Activity" page logged in as "student2"
    Then I should see "Embedded question progress for Test page"
    And I should see "Download table data as"
    And I should see "student2"
    And I should not see "student1"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test page 1"
    And I navigate to "Embedded questions progress" in current page administration
    And I should see "student1"
    And I should not see "student2"

  Scenario: A student can only see his/her own progress in a course
    Given I am on the "C1" "report_embedquestion > Progress report for Course" page logged in as "student2"
    Then I should see "Embedded question progress for Course 1"
    And I should see "student2"
    And I should not see "student1"

  Scenario: The report will show the IDs columns depend on the administration setting.
    Given I am on the "page1" "report_embedquestion > Progress report for Activity" page logged in as "admin"
    And I should see "Embedded question progress for Test page"
    And the following config values are set as admin:
      | showuseridentity | |
    And I reload the page
    And I should not see "student1" in the "Student A" "table_row"
    And I should not see "s1" in the "Student A" "table_row"
    And I should not see "student1@gmail.com" in the "Student A" "table_row"
    And I should not see "12345678911" in the "Student A" "table_row"
    And the following config values are set as admin:
    | showuseridentity | username,idnumber,email,phone1 |
    And I reload the page
    And I should see "student1" in the "Student A" "table_row"
    And I should see "s1" in the "Student A" "table_row"
    And I should see "student1@example.com" in the "Student A" "table_row"
    And I should see "12345678911" in the "Student A" "table_row"

  @javascript
  Scenario: The teacher can filter the report by activity.
    Given I am on the "C1" "report_embedquestion > Progress report for Course" page logged in as "teacher"
    Then I should see "Embedded question progress for Course 1"
    And I expand all fieldsets
    And I should see "Attempts from"
    And I should see "All activities" in the ".form-autocomplete-selection" "css_element"
    And ".initialbar.firstinitial" "css_element" should exist
    And ".initialbar.lastinitial " "css_element" should exist
    And I open the autocomplete suggestions list
    And I click on "Test page 1" item in the autocomplete list
    And "Test page 1" "autocomplete_selection" should exist
    And I click on "Show report" "button"
    And I should see "student1"
    And I should see "student2"
    And I should not see "student4"
    And I click on "A" "link" in the ".initialbar.lastinitial" "css_element"
    And I should see "Student A"
    And I should not see "Student B"
    And I should not see "Student D"
    And I click on "All" "link" in the ".initialbar.lastinitial" "css_element"
    And I open the autocomplete suggestions list
    And I click on "Test page 2" item in the autocomplete list
    And "Test page 2" "autocomplete_selection" should exist
    And I click on "Show report" "button"
    And I should see "student1"
    And I should see "student2"
    And I should see "student4"
    And I click on "Test page 1" "autocomplete_selection"
    And "Test page 1" "autocomplete_selection" should not exist
    And I click on "Show report" "button"
    And I should not see "student1"
    And I should not see "student2"
    And I should see "student4"

  Scenario: The teacher can view all the attempts of a specific student using 'Show all' link
    When I am on the "page1" "report_embedquestion > Progress report for Activity" page logged in as "teacher"
    Then I should see "Show only" in the "student1" "table_row"
    And I should see "Show only" in the "student2" "table_row"
    And ".initialbar.firstinitial" "css_element" should exist
    And ".initialbar.lastinitial " "css_element" should exist
    And I click on "Show only" "link" in the "student1" "table_row"
    And I should see "Student A (student1)" in the ".breadcrumb" "css_element"
    And I should see "Showing only Student A (student1)"
    And "Show everybody" "link" should exist
    And ".initialbar.firstinitial" "css_element" should not exist
    And ".initialbar.lastinitial " "css_element" should not exist
    And I should not see "student2"
    And I should not see "Show only" in the "student1" "table_row"
    And I click on "Show everybody" "link"
    And I should see "student2"
    And I log out
    And I am on the "page1" "report_embedquestion > Progress report for Activity" page logged in as "student1"
    And I should not see "Show only" in the "student1" "table_row"
    And "Show everybody" "link" should not exist

  @javascript
  Scenario: A teacher can filter the report and the filter should not be changed when teacher change the table preferences
    Given I am on the "C1" "report_embedquestion > Progress report for Course" page logged in as "teacher"
    Then I should see "Embedded question progress for Course 1"
    And I click on "Show only" "link" in the "student5" "table_row"
    And I should see "Show everybody"

    # Filter the result, the Show everybody link shouldn't disappear.
    And I open the autocomplete suggestions list
    And I click on "Test page 1" item in the autocomplete list
    And I click on "Test page 2" item in the autocomplete list
    And I set the field "Page size" to "1"
    And I press "Show report"
    And I should see "Show everybody"
    And I should see "Student E" in the "student5" "table_row"

    # Change the paging, the filter shouldn't be changed.
    And I click on "Next" "link"
    And I should see "Student E" in the "student5" "table_row"
    And "Test page 1" "autocomplete_selection" should exist
    And "Test page 2" "autocomplete_selection" should exist
    And the field "Page size" matches value "1"
