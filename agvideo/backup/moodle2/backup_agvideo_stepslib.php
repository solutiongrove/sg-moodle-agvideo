<?php

defined('MOODLE_INTERNAL') || die;

class backup_agvideo_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $agvideo = new backup_nested_element('agvideo', array('id'), array(
            'name', 'agid', 'intro', 'introformat', 'relativepath', 'displayoptions', 'timemodified'));

        $grades = new backup_nested_element('grades');

        $grade = new backup_nested_element('grade', array('id'), array(
            'userid', 'gradeval', 'timemodified'));

        // Build the tree
        $agvideo->add_child($grades);
        $grades->add_child($grade);

        // Define sources
        $agvideo->set_source_table('agvideo', array('id' => backup::VAR_ACTIVITYID));
        // All the rest of elements only happen if we are including user info
        if ($userinfo) {
            $grade->set_source_table('agvideo_grades', array('agvideo' => backup::VAR_PARENTID));
        }

        // Define source alias
        $grade->set_source_alias('grade', 'gradeval');

        // Define id annotations
        $grade->annotate_ids('user', 'userid');

        // Define file annotations
        $agvideo->annotate_files('mod_agvideo', 'intro', null); // This file area hasn't itemid

        // Return the root element (agvideo), wrapped into standard activity structure
        return $this->prepare_activity_structure($agvideo);

    }
}
