<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/agvideo/backup/moodle2/backup_agvideo_stepslib.php'); // Because it exists (must)

/**
 * AGVIDEO backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_agvideo_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        $this->add_step(new backup_agvideo_activity_structure_step('agvideo_structure', 'agvideo.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        // Link to the list of agvideos
        $search="/(".$base."\/mod\/agvideo\/index.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@AGVIDEOINDEX*$2@$', $content);

        // Link to agvideo view by moduleid
        $search="/(".$base."\/mod\/agvideo\/view.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@AGVIDEOVIEWBYID*$2@$', $content);

        return $content;
    }
}
