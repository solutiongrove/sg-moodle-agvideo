<?php

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/agvideo/lib.php");

/**
 * Fix common URL problems that we want teachers to see fixed
 * the next time they edit the resource.
 *
 * This function does not include any XSS protection.
 *
 * @param string $url
 * @return string
 */
function agvideo_fix_submitted_path($url) {
    // note: empty and invalid urls are prevented in form validation
    $url_temp = trim($url);
    $url_parts = parse_url($url_temp);
    $url = '/'.ltrim($url_parts['path'],'/');

    // remove encoded entities - we want the raw URI here
    $url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');

    return $url;
}

/**
 * Return full url with all extra parameters
 *
 * This function does not include any XSS protection.
 *
 * @param string $agvideo
 * @param object $cm
 * @param object $course
 * @param object $config
 * @return string url with & encoded as &amp;
 */
function agvideo_get_full_url($agvideo, $cm, $course, $config=null) {

    $ag_server_url = get_config('local_agbase', 'agserverurl');

    // make sure there are no encoded entities, it is ok to do this twice
    $relativepath = trim($agvideo->relativepath);
    if (empty($relativepath)) {
        $relativepath = "/video/".$agvideo->agid;
    }
    $fullurl = rtrim($ag_server_url, '/') . html_entity_decode($relativepath, ENT_QUOTES, 'UTF-8');

    $allowed = "a-zA-Z0-9".preg_quote(';/?:@=&$_.+!*(),-#%', '/');
    $fullurl = preg_replace_callback("/[^$allowed]/", 'agvideo_filter_callback', $fullurl);

    // encode all & to &amp; entity
    $fullurl = str_replace('&', '&amp;', $fullurl);

    return $fullurl;
}

/**
 * Unicode encoding helper callback
 * @internal
 * @param array $matches
 * @return string
 */
function agvideo_filter_callback($matches) {
    return rawurlencode($matches[0]);
}

/**
 * Print url header.
 * @param object $url
 * @param object $cm
 * @param object $course
 * @return void
 */
function agvideo_print_header($agvideo, $cm, $course) {
    global $PAGE, $OUTPUT;

    $PAGE->set_title($course->shortname.': '.$agvideo->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($agvideo);
    echo $OUTPUT->header();
}

/**
 * Print agvideo heading.
 * @param object $agvideo
 * @param object $cm
 * @param object $course
 * @param bool $ignoresettings print even if not specified in modedit
 * @return void
 */
function agvideo_print_heading($agvideo, $cm, $course, $ignoresettings=false) {
    global $OUTPUT;

    $options = empty($agvideo->displayoptions) ? array() : unserialize($agvideo->displayoptions);

    if ($ignoresettings or !empty($options['printheading'])) {
        echo $OUTPUT->heading(format_string($agvideo->name), 2, 'main', 'agvideoheading');
    }
}

/**
 * Print agvideo introduction.
 * @param object $agvideo
 * @param object $cm
 * @param object $course
 * @param bool $ignoresettings print even if not specified in modedit
 * @return void
 */
function agvideo_print_intro($agvideo, $cm, $course, $ignoresettings=false) {
    global $OUTPUT;

    $options = empty($agvideo->displayoptions) ? array() : unserialize($agvideo->displayoptions);

    if ($ignoresettings or !empty($options['printintro'])) {
        if (trim(strip_tags($agvideo->intro))) {
            echo $OUTPUT->box_start('mod_introbox', 'agvideointro');
            echo format_module_intro('agvideo', $agvideo, $cm->id);
            echo $OUTPUT->box_end();
        }
    }
}


/**
 * Print agvideo info and link.
 * @param object $agvideo
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function agvideo_print_workaround($agvideo, $cm, $course) {
    global $OUTPUT;

    agvideo_print_header($agvideo, $cm, $course);
    agvideo_print_heading($agvideo, $cm, $course);
    agvideo_print_intro($agvideo, $cm, $course);

    $fullurl = agvideo_get_full_url($agvideo, $cm, $course);

    $extra = '';

    echo '<div class="agurlworkaround">';
    print_string('clicktoopen', 'agvideo', "<a href=\"$fullurl\" $extra>".get_string('video', 'agvideo')."</a>");
    echo '</div>';

    echo $OUTPUT->footer();
    die;
}

/**
 * Save the overall grade for a user at a agvideo in the agvideo_grades table
 *
 * @param object $agvideo The agvideo for which the best grade is to be calculated and then saved.
 * @param int $userid The userid to calculate the grade for.
 * @param int $grade The raw grade
 * @return bool Indicates success or failure.
 */
function agvideo_save_best_grade($agvideo, $userid, $grade = "100.00") {
    global $DB;
    global $OUTPUT;

    $bestgrade = $grade;

    // Save the best grade in the database
    if (is_null($bestgrade)) {
        $DB->delete_records('agvideo_grades', array('agvideo' => $agvideo->id, 'userid' => $userid));

    } else if ($grade = $DB->get_record('agvideo_grades',
            array('agvideo' => $agvideo->id, 'userid' => $userid))) {
        $grade->grade = $bestgrade;
        $grade->timemodified = time();
        $DB->update_record('agvideo_grades', $grade);
    } else {
        $grade->agvideo = $agvideo->id;
        $grade->userid = $userid;
        $grade->grade = $bestgrade;
        $grade->timemodified = time();
        $DB->insert_record('agvideo_grades', $grade);
    }

    agvideo_update_grades($agvideo, $userid);
}


/**
 * Enqueue the overall grade for a user at a agvideo
 *
 * @param object $agid The id of the video for which the best grade is to be calculated and then saved.
 * @param int $userid The userid to calculate the grade for.
 * @param int $grade The raw grade
 * @return bool Indicates success or failure.
 */
function agvideo_enqueue_grade($agid, $userid, $grade = "100.00") {
    global $DB;
    global $OUTPUT;

    $bestgrade = $grade;

    // Save the best grade in the database
    if (is_null($bestgrade)) {
        $DB->delete_records('agvideo_grades_queue', array('agid' => $agid, 'userid' => $userid));

    } else if ($grade = $DB->get_record('agvideo_grades_queue',
            array('agid' => $agid, 'userid' => $userid))) {
        $grade->grade = $bestgrade;
        $grade->timemodified = time();
        $DB->update_record('agvideo_grades_queue', $grade);
    } else {
        $grade->agid = $agid;
        $grade->userid = $userid;
        $grade->grade = $bestgrade;
        $grade->timemodified = time();
        $DB->insert_record('agvideo_grades_queue', $grade);
    }
}
