# Change log for the Embedded questions progress report

## Features in version 1.7

* This version works with Moodle 4.0.
* The way the report loads data from the database has been changed to perform better.
* Fixed a bug with changing the report options.
* Fixed a bug with downloading response files when there are no files.
* Fixed a bug downloading the response files from the
  [Record audio/video question type](https://moodle.org/plugins/qtype_recordrtc).


## Features in version 1.6

* Support for custom profile fields in 'Show user identity' for Moodle 3.11+
* Therefore, this version only works with Moodle 3.11 or later. The previous
  version of this plugin works with older Moodles.


## Features in version 1.5

* Support for the new language feature in the filter.
* The report now respects the 'Show user identity' admin setting.
* Improved report options, e.g. filter by location, an number of rows per page.
* Improvements in navigtion between different pages in the report.
* If the Record audio/video qustions type is installed, students can now easily
  download all their recordings. 
* Ensure that all character that might appear in the idnubmers of embedded questions work
  (Even though I would recommend keeping the idnumbers simple.)
* Various other bug fixes.


## Features in version 1.4

* Re-release to fix a mistake with the version number.


## Features in version 1.3

* Add missing require_once that was causing intermittent errors in backup/restore.


## Features in version 1.2

* The report now supports Moodle group mode.
* An extra screen to drill-down into the detail of a student's attempts.
* A feature so that teachers (with an appropriate capability) can delete attempts.
* ... and, for students to delete their own attempts (if they have 'Delete my attempts' capability).
* Improvements to the visual design.
* Link to the report only appears in the navigation in places where questions are embedded.
* Breadcrumb trail improved for report pages.
* Now, if the embedding option for max-mark is changed, in-progress attempts pick up the new value.
* In-progress attempts are now shown consistently.
* Fix occasional errors in cron from this plugin.
* Fix a bug with report paging, and a few other fixes not worth mentioning.


## Features in version 1.1

* Ability to filter the report by date. Either a from - to date range,
  or last so many days or weeks.
* Option to download the report in a range of formats.
* Improvements to the report UI.
* Fix a bug where you could get stuck with an error you could not escape
  if a question that you attempted was deleted.


## Features in version 1.0

* Responses are stored permanently whenever user attempts an embedded question.
* There are reports to let a Student review their own attempts ...
* ... and also to let teachers review the attempts of all students.
