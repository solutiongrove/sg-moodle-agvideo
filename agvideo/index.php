<?php

require('../../config.php');

$id = required_param('id', PARAM_INT); // course id

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);

require_course_login($course, true);
$PAGE->set_pagelayout('incourse');

add_to_log($course->id, 'agvideo', 'view all', "index.php?id=$course->id", '');

$stragvideo      = get_string('modulename', 'agvideo');
$stragvideos     = get_string('modulenameplural', 'agvideo');
$strsectionname  = get_string('sectionname', 'format_'.$course->format);
$strname         = get_string('name');
$strintro        = get_string('moduleintro');
$strlastmodified = get_string('lastmodified');

$PAGE->set_url('/mod/agvideo/index.php', array('id' => $course->id));
$PAGE->set_title($course->shortname.': '.$stragvideos);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($stragvideos);
echo $OUTPUT->header();

if (!$agvideos = get_all_instances_in_course('agvideo', $course)) {
    notice(get_string('thereareno', 'moodle', $stragvideos), "$CFG->wwwroot/course/view.php?id=$course->id");
    exit;
}

$usesections = course_format_uses_sections($course->format);
if ($usesections) {
    $sections = get_all_sections($course->id);
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $table->head  = array ($strsectionname, $strname, $strintro);
    $table->align = array ('center', 'left', 'left');
} else {
    $table->head  = array ($strlastmodified, $strname, $strintro);
    $table->align = array ('left', 'left', 'left');
}

$modinfo = get_fast_modinfo($course);
$currentsection = '';
foreach ($agvideos as $agvideo) {
    $cm = $modinfo->cms[$agvideo->coursemodule];
    if ($usesections) {
        $printsection = '';
        if ($agvideo->section !== $currentsection) {
            if ($agvideo->section) {
                $printsection = get_section_name($course, $sections[$agvideo->section]);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $agvideo->section;
        }
    } else {
        $printsection = '<span class="smallinfo">'.userdate($agvideo->timemodified)."</span>";
    }

    $extra = empty($cm->extra) ? '' : $cm->extra;
    $icon = '';
    if (!empty($cm->icon)) {
        // each agvideo has an icon in 2.0
        $icon = '<img src="'.$OUTPUT->pix_url($cm->icon).'" class="activityicon" alt="'.get_string('modulename', $cm->modname).'" /> ';
    }

    $class = $agvideo->visible ? '' : 'class="dimmed"'; // hidden modules are dimmed
    $table->data[] = array (
        $printsection,
        "<a $class $extra href=\"view.php?id=$cm->id\">".$icon.format_string($agvideo->name)."</a>",
        format_module_intro('agvideo', $agvideo, $cm->id));
}

echo html_writer::table($table);

echo $OUTPUT->footer();
