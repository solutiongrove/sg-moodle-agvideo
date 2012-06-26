<?php

defined('MOODLE_INTERNAL') || die;

/**
 * List of features supported in AGVIDEO module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function agvideo_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

/**
 * Returns all other caps used in module
 * @return array
 */
function agvideo_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * Removes all grades from gradebook
 *
 * @param int $courseid
 * @param string optional type
 */
function agvideo_reset_gradebook($courseid, $type='') {
    global $CFG, $DB;

    $agvideos = $DB->get_records_sql("
            SELECT q.*, cm.idnumber as cmidnumber, q.course as courseid
            FROM {modules} m
            JOIN {course_modules} cm ON m.id = cm.module
            JOIN {agvideo} q ON cm.instance = q.id
            WHERE m.name = 'agvideo' AND cm.course = ?", array($courseid));

    foreach ($agvideos as $agvideo) {
        agvideo_grade_item_update($agvideo, 'reset');
    }
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function agvideo_reset_userdata($data) {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/mod/agvideo/locallib.php');

    $componentstr = get_string('modulenameplural', 'agvideo');
    $status = array();

    // Remove all grades from gradebook
    $DB->delete_records_select('agvideo_grades',
            'agvideo IN (SELECT id FROM {agvideo} WHERE course = ?)', array($data->courseid));
    if (empty($data->reset_gradebook_grades)) {
      agvideo_reset_gradebook($data->courseid);
    }
    $status[] = array(
        'component' => $componentstr,
        'item' => get_string('gradesdeleted', 'agvideo'),
        'error' => false);
    return $status;
}

/**
 * List of view style log actions
 * @return array
 */
function agvideo_get_view_actions() {
    return array('view', 'view all', 'report');
}

/**
 * List of update style log actions
 * @return array
 */
function agvideo_get_post_actions() {
    return array('update', 'add');
}

/**
 * Add agvideo instance.
 * @param object $data
 * @param object $mform
 * @return int new agvideo instance id
 */
function agvideo_add_instance($data, $mform) {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/mod/agvideo/locallib.php');

    $data->name = get_string('videoname', 'agvideo').$data->name;
    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    $displayoptions['printheading'] = (int)!empty($data->printheading);
    $displayoptions['printintro']   = (int)!empty($data->printintro);
    $data->displayoptions = serialize($displayoptions);

    $data->relativepath = agvideo_fix_submitted_path($data->relativepath);
    $data->agid = array_pop(split('/', $data->relativepath));

    $data->timemodified = time();
    $data->id = $DB->insert_record('agvideo', $data);

    // Do the processing required after an add or an update.
    agvideo_after_add_or_update($data);

    return $data->id;
}

/**
 * Update agvideo instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function agvideo_update_instance($data, $mform) {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/mod/agvideo/locallib.php');

    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    $displayoptions['printheading'] = (int)!empty($data->printheading);
    $displayoptions['printintro']   = (int)!empty($data->printintro);
    $data->displayoptions = serialize($displayoptions);

    $data->relativepath = agvideo_fix_submitted_path($data->relativepath);
    $data->agid = array_pop(split('/', $data->relativepath));

    $data->timemodified = time();
    $data->id           = $data->instance;

    $DB->update_record('agvideo', $data);

    // Do the processing required after an add or an update.
    agvideo_after_add_or_update($data);

    return true;
}

/**
 * Delete agvideo instance.
 * @param int $id
 * @return bool true
 */
function agvideo_delete_instance($id) {
    global $DB;

    if (!$agvideo = $DB->get_record('agvideo', array('id'=>$id))) {
        return false;
    }

    // note: all context files are deleted automatically

    agvideo_delete_all_grades($agvideo);

    agvideo_grade_item_delete($agvideo);

    $DB->delete_records('agvideo', array('id'=>$agvideo->id));

    return true;
}

/**
 * Return use outline
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $agvideo
 * @return object|null
 */
function agvideo_user_outline($course, $user, $mod, $agvideo) {
    global $DB, $CFG;
    require_once("$CFG->libdir/gradelib.php");
    $grades = grade_get_grades($course->id, 'mod', 'agvideo', $agvideo->id, $user->id);

    if (empty($grades->items[0]->grades)) {
        return null;
    } else {
        $grade = reset($grades->items[0]->grades);
    }

    $result = new stdClass();
    $result->info = get_string('grade') . ': ' . $grade->str_long_grade;

    //datesubmitted == time created. dategraded == time modified or time overridden
    //if grade was last modified by the user themselves use date graded. Otherwise use
    // date submitted
    // TODO: move this copied & pasted code somewhere in the grades API. See MDL-26704
    if ($grade->usermodified == $user->id || empty($grade->datesubmitted)) {
        $result->time = $grade->dategraded;
    } else {
        $result->time = $grade->datesubmitted;
    }

    return $result;
}

/**
 * Return use complete
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $agvideo
 */
function agvideo_user_complete($course, $user, $mod, $agvideo) {
    global $DB, $CFG, $OUTPUT;
    require_once("$CFG->libdir/gradelib.php");

    $grades = grade_get_grades($course->id, 'mod', 'agvideo', $agvideo->id, $user->id);
    if (!empty($grades->items[0]->grades)) {
        $grade = reset($grades->items[0]->grades);
        echo $OUTPUT->container(get_string('grade').': '.$grade->str_long_grade);
    }

    return true;
}

/**
 * Delete grade item for given agvideo
 *
 * @param object $agvideo object
 * @return object agvideo
 */
function agvideo_grade_item_delete($agvideo) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    return grade_update('mod/agvideo', $agvideo->course, 'mod', 'agvideo', $agvideo->id, 0,
            null, array('deleted' => 1));
}

/**
 * Returns the users with data in one agvideo
 *
 * @todo: deprecated - to be deleted in 2.2
 *
 * @param int $agvideoid
 * @return bool false
 */
function agvideo_get_participants($agvideoid) {
    return false;
}

/**
 * This function extends the global navigation for the site.
 * It is important to note that you should not rely on PAGE objects within this
 * body of code as there is no guarantee that during an AJAX request they are
 * available
 *
 * @param navigation_node $navigation The agvideo node within the global navigation
 * @param stdClass $course The course object returned from the DB
 * @param stdClass $module The module object returned from the DB
 * @param stdClass $cm The course module instance returned from the DB
 */
function agvideo_extend_navigation($navigation, $course, $module, $cm) {
    /**
     * This is currently just a stub so that it can be easily expanded upon.
     * When expanding just remove this comment and the line below and then add
     * you content.
     */
    $navigation->nodetype = navigation_node::NODETYPE_LEAF;
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function agvideo_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-agvideo-*'=>get_string('page-mod-agvideo-x', 'agvideo'));
    return $module_pagetype;
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as synchronizing grades, rebuilding cache, etc ...
 */
function agvideo_cron($inpage=FALSE) {
    global $DB, $CFG;

    require_once($CFG->dirroot.'/mod/agvideo/locallib.php');
    require_once($CFG->dirroot.'/local/agbase/locallib.php');
    require_once($CFG->libdir.'/gradelib.php');

    if ($inpage) {
      $eol = "<br/>";
    } else {
      $eol = "\n";
    }

    mtrace("Starting agvideo grade check",$eol);
    $moodleid = get_config('local_agbase', 'agservermoodleid');
    $last_watched_sync_value = rtrim(get_config('agvideo','lastwatchedsyncvalue'),".0").".0";
    $rest_request = new local_agbase_rest();
    mtrace("Fetching completed videos after UTC ".$last_watched_sync_value,$eol);
    $response_data = $rest_request->call("POST",
                                         "video.gradequeue",
                                         array('moodleid' => $moodleid,
                                               'last_watched_utc' => $last_watched_sync_value)
                                         );

    if ($response_data == "") {
        mtrace("error while trying to fetch data",$eol);
    } else {
      $converted_data = json_decode($response_data);
      if (is_array($converted_data)) {
        $new_last_watched_sync_value = $last_watched_sync_value;
        foreach ($converted_data as $video_item) {
          if ($video_item->lastwatched > $new_last_watched_sync_value) {
            $new_last_watched_sync_value = $video_item->lastwatched;
          }
          $seconds_watched = (float)$video_item->seconds_watched;
          $duration = (float)$video_item->duration;
          if ($seconds_watched > $duration) $seconds_watched = $duration;
          $grade = number_format(($seconds_watched / $duration * 100), 2);
          mtrace("Processing grades for user ".$video_item->userid." on video ".$video_item->kaid,$eol);
          agvideo_enqueue_grade($video_item->kaid, $video_item->userid, $grade);
        }
        set_config('lastwatchedsyncvalue', $new_last_watched_sync_value, 'agvideo');
      }
    }
    if ($queued_grades = $DB->get_records('agvideo_grades_queue')) {
        foreach ($queued_grades as $one_grade) {
            if ($agvideos = $DB->get_records('agvideo', array('agid'=>$one_grade->agid))) {
                foreach ($agvideos as $agvideo) {
                    $is_locked = FALSE;
                    mtrace("found on course ".$agvideo->course." with name ".$agvideo->name,$eol);
                    $gradebook_grades = grade_get_grades($agvideo->course, 'mod', 'agvideo', $agvideo->id);
                    if (!empty($gradebook_grades->items)) {
                        $grade_item = $gradebook_grades->items[0];
                        if ($grade_item->locked) {
                            $is_locked = TRUE;
                        }
                    }
                    if ($is_locked) {
                        mtrace("skipping due to grade locked",$eol);
                    } else {
                        agvideo_save_best_grade($agvideo, $one_grade->userid, $one_grade->grade);
                        $DB->delete_records('agvideo_grades_queue', array('id' => $one_grade->id));
                    }
                }
            }
        }
    }
    mtrace("Finished agvideo grade check",$eol);
    return true;
}

/**
 * This function is called at the end of agvideo_add_instance
 * and agvideo_update_instance, to do the common processing.
 *
 * @param object $agvideo the agvideo object.
 */
function agvideo_after_add_or_update($agvideo) {
    global $DB;

    //update related grade item
    agvideo_grade_item_update($agvideo);
}

/**
 * Create grade item for given agvideo
 *
 * @param object $agvideo object with extra cmidnumber
 * @param mixed $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function agvideo_grade_item_update($agvideo, $grades = null) {
    global $CFG, $OUTPUT;
    require_once($CFG->dirroot . '/mod/agvideo/locallib.php');
    require_once($CFG->libdir.'/gradelib.php');

    if (array_key_exists('cmidnumber', $agvideo)) { // may not be always present
        $params = array('itemname' => $agvideo->name, 'idnumber' => $agvideo->cmidnumber);
    } else {
        $params = array('itemname' => $agvideo->name);
    }

    if ($agvideo->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $agvideo->grade;
        $params['grademin']  = 0;

    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    $params['hidden'] = 0;

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    $gradebook_grades = grade_get_grades($agvideo->course, 'mod', 'agvideo', $agvideo->id);
    if (!empty($gradebook_grades->items)) {
        $grade_item = $gradebook_grades->items[0];
        if ($grade_item->locked) {
            $confirm_regrade = optional_param('confirm_regrade', 0, PARAM_INT);
            if (!$confirm_regrade) {
                $message = get_string('gradeitemislocked', 'grades');
                $regrade_link = qualified_me() . '&amp;confirm_regrade=1';
                echo $OUTPUT->box_start('generalbox', 'notice');
                echo '<p>'. $message .'</p>';
                echo $OUTPUT->container_start('buttons');
                echo $OUTPUT->single_button($regrade_link, get_string('regradeanyway', 'grades'));
                echo $OUTPUT->container_end();
                echo $OUTPUT->box_end();

                return GRADE_UPDATE_ITEM_LOCKED;
            }
        }
    }

    return grade_update('mod/agvideo', $agvideo->course, 'mod', 'agvideo', $agvideo->id, 0, $grades, $params);
}

/**
 * Return grade for given user or all users.
 *
 * @param int $agvideo video data
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none. These are raw grades. They should
 * be processed with agvideo_format_grade for display.
 */
function agvideo_get_user_grades($agvideo, $userid = 0) {
    global $CFG, $DB;

    $params = array($agvideo->id);
    $usertest = '';
    if ($userid) {
        $params[] = $userid;
        $usertest = 'AND u.id = ?';
    }
    return $DB->get_records_sql("
            SELECT
                u.id,
                u.id AS userid,
                qg.grade AS rawgrade,
                qg.timemodified AS dategraded

            FROM {user} u
            JOIN {agvideo_grades} qg ON u.id = qg.userid

            WHERE qg.agvideo = ?
            $usertest", $params);
}

/**
 * Update grades in central gradebook
 *
 * @param object $agvideo the agvideo settings.
 * @param int $userid specific user only, 0 means all users.
 */
function agvideo_update_grades($agvideo, $userid = 0, $nullifnone = true) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    if ($agvideo->grade == 0) {
        agvideo_grade_item_update($agvideo);

    } else if ($grades = agvideo_get_user_grades($agvideo, $userid)) {
        agvideo_grade_item_update($agvideo, $grades);

    } else if ($userid && $nullifnone) {
        $grade = new stdClass();
        $grade->userid = $userid;
        $grade->rawgrade = null;
        agvideo_grade_item_update($agvideo, $grade);

    } else {
        agvideo_grade_item_update($agvideo);
    }
}

/**
 * Update all grades in gradebook.
 */
function agvideo_upgrade_grades() {
    global $DB;

    $sql = "SELECT COUNT('x')
              FROM {agvideo} a, {course_modules} cm, {modules} m
             WHERE m.name='agvideo' AND m.id=cm.module AND cm.instance=a.id";
    $count = $DB->count_records_sql($sql);

    $sql = "SELECT a.*, cm.idnumber AS cmidnumber, a.course AS courseid
              FROM {agvideo} a, {course_modules} cm, {modules} m
             WHERE m.name='agvideo' AND m.id=cm.module AND cm.instance=a.id";
    $rs = $DB->get_recordset_sql($sql);
    if ($rs->valid()) {
        $pbar = new progress_bar('agvideoupgradegrades', 500, true);
        $i=0;
        foreach ($rs as $agvideo) {
            $i++;
            upgrade_set_timeout(60*5); // set up timeout, may also abort execution
            agvideo_update_grades($agvideo, 0, false);
            $pbar->update($i, $count, "Updating AGVIDEO grades ($i/$count).");
        }
    }
    $rs->close();
}

/**
 * Delete all the grades belonging to a agvideo.
 *
 * @param object $agvideo The agvideo object.
 */
function agvideo_delete_all_grades($agvideo) {
    global $CFG, $DB;
    $DB->delete_records('agvideo_grades', array('agvideo' => $agvideo->id));
}

