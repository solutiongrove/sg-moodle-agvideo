<?php

require('../../config.php');
require_once("$CFG->dirroot/mod/agvideo/locallib.php");

$id       = optional_param('id', 0, PARAM_INT);        // Course ID
$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);

require_course_login($course, true);
$context = get_context_instance(CONTEXT_COURSE, $course->id);

if (has_capability('moodle/grade:edit', $context)) {
  $PAGE->set_pagelayout('incourse');

  $PAGE->set_url('/mod/agvideo/gradeupdate.php', array('id' => $course->id));
  $PAGE->set_title($course->shortname.': '.get_string('updategrades','agvideo'));
  $PAGE->set_heading($course->fullname);
  echo $OUTPUT->header();
  agvideo_cron(TRUE);
  # currently a hack to also upgrade exercises
  if ($plugins = $DB->get_records('modules', array('name'=>'agexercise'))) {
    require_once("$CFG->dirroot/mod/agexercise/locallib.php");
    agexercise_cron(TRUE);
  }
  echo "<br/><a href=\"/grade/report/index.php?id={$course->id}\">Return to Grades</a>";
  echo $OUTPUT->footer();
} else {
  redirect('/course/view.php?id='.$course->id);
}
