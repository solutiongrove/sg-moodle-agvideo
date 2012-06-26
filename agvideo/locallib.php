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
 * Display agvideo frames.
 * @param object $agvideo
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function agvideo_display_frame($agvideo, $cm, $course) {
    global $PAGE, $OUTPUT, $CFG;
    $frame = optional_param('frameset', 'main', PARAM_ALPHA);

    if ($frame === 'top') {
        $PAGE->set_pagelayout('frametop');
        agvideo_print_header($agvideo, $cm, $course);
        agvideo_print_heading($agvideo, $cm, $course);
        agvideo_print_intro($agvideo, $cm, $course);
        echo $OUTPUT->footer();
        die;
    } else {
        $config = get_config('agvideo');
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        $exteurl = agvideo_get_full_url($agvideo, $cm, $course, $config);
        $navurl = "$CFG->wwwroot/mod/agvideo/view.php?id=$cm->id&amp;frameset=top";
        $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
        $courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));
        $title = strip_tags($courseshortname.': '.format_string($agvideo->name));
        $framesize = empty($config->framesize) ? 130 : $config->framesize;
        $modulename = s(get_string('modulename','agvideo'));
        $dir = get_string('thisdirection', 'langconfig');

        $extframe = <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html dir="$dir">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>$title</title>
  </head>
  <frameset rows="$framesize,*">
    <frame src="$navurl" title="$modulename"/>
    <frame src="$exteurl" title="$modulename"/>
  </frameset>
</html>
EOF;

        @header('Content-Type: text/html; charset=utf-8');
        echo $extframe;
        die;
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

    $display = $agvideo->display;
    if ($display == RESOURCELIB_DISPLAY_POPUP) {
        $jsfullurl = addslashes_js($fullurl);
        $options = empty($agvideo->displayoptions) ? array() : unserialize($agvideo->displayoptions);
        $width  = empty($options['popupwidth'])  ? 620 : $options['popupwidth'];
        $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
        $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
        $extra = "onclick=\"window.open('$jsfullurl', '', '$wh'); return false;\"";
    } else if ($display == RESOURCELIB_DISPLAY_NEW) {
        $extra = "onclick=\"this.target='_blank';\"";
    } else {
        $extra = '';
    }

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
