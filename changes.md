# Change log for the Embedded questions progress report

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
