<?php

require('../../config.php');
require_once("$CFG->dirroot/mod/agvideo/locallib.php");
require_once($CFG->libdir . '/completionlib.php');

$id       = optional_param('id', 0, PARAM_INT);        // Course module ID
$redirect = optional_param('redirect', 0, PARAM_BOOL);

$cm = get_coursemodule_from_id('agvideo', $id, 0, false, MUST_EXIST);
$agvideo = $DB->get_record('agvideo', array('id'=>$cm->instance), '*', MUST_EXIST);

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
require_capability('mod/agvideo:view', $context);

add_to_log($course->id, 'agvideo', 'view', 'view.php?id='.$cm->id, $agvideo->id, $cm->id);

// Update 'viewed' state if required by completion system
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/agvideo/view.php', array('id' => $cm->id));

if ($redirect) {
    // coming from course page or url index page,
    // the redirection is needed for completion tracking and logging
    $fullurl = agvideo_get_full_url($agvideo, $cm, $course);
    redirect(str_replace('&amp;', '&', $fullurl));
}

agvideo_print_workaround($agvideo, $cm, $course);
