<?php

/**
 * Define all the restore steps that will be used by the restore_agvideo_activity_task
 */

/**
 * Structure step to restore one agvideo activity
 */
class restore_agvideo_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $agvideo = new restore_path_element('agvideo', '/activity/agvideo');
        $paths[] = $agvideo;

        if ($userinfo) {
            $paths[] = new restore_path_element('agvideo_grade', '/activity/agvideo/grades/grade');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_agvideo($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // insert the agvideo record
        $newitemid = $DB->insert_record('agvideo', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_agvideo_grade($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->agvideo = $this->get_new_parentid('agvideo');

        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->grade = $data->gradeval;

        $DB->insert_record('agvideo_grades', $data);
    }

    protected function after_execute() {
        // Add agvideo related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_agvideo', 'intro', null);
    }
}
