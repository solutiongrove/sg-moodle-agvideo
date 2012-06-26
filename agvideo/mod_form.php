<?php

defined('MOODLE_INTERNAL') || die;

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/agvideo/locallib.php');

class mod_agvideo_mod_form extends moodleform_mod {
    function definition() {
        global $CFG, $DB;
        $mform = $this->_form;

        $config = get_config('agvideo');

        //-------------------------------------------------------
        // General settings
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        $this->add_intro_editor($config->requiremodintro);

        //-------------------------------------------------------
        // Academy Grove settings
        $mform->addElement('header', 'content', get_string('contentheader', 'agvideo'));

        $mform->addElement('hidden', 'agid', "");
        $mform->setType('agid', PARAM_TEXT);

        $mform->addElement('url', 'relativepath', get_string('relativepath', 'agvideo'), array('size'=>'60'), array('usefilepicker'=>false));
        $mform->setType('relativepath', PARAM_TEXT);
        $mform->addRule('relativepath', null, 'required', null, 'client');
        $mform->addHelpButton('relativepath', 'relativepath', 'agvideo');

        //-------------------------------------------------------------------------------
        // Grade settings
        $this->standard_grading_coursemodule_elements();

        $mform->removeElement('grade');
        $mform->addElement('hidden', 'grade', "100");
        $mform->setType('grade', PARAM_NUMBER);

        //-------------------------------------------------------
        // Options settings
        $mform->addElement('header', 'optionssection', get_string('optionsheader', 'agvideo'));

        if ($this->current->instance) {
            $options = resourcelib_get_displayoptions(explode(',', $config->displayoptions), $this->current->display);
        } else {
            $options = resourcelib_get_displayoptions(explode(',', $config->displayoptions));
        }
        if (count($options) == 1) {
            $mform->addElement('hidden', 'display');
            $mform->setType('display', PARAM_INT);
            reset($options);
            $mform->setDefault('display', key($options));
        } else {
            $mform->addElement('select', 'display', get_string('displayselect', 'agvideo'), $options);
            $mform->setDefault('display', $config->display);
            $mform->setAdvanced('display', $config->display_adv);
            $mform->addHelpButton('display', 'displayselect', 'agvideo');
        }

        if (array_key_exists(RESOURCELIB_DISPLAY_POPUP, $options)) {
            $mform->addElement('text', 'popupwidth', get_string('popupwidth', 'agvideo'), array('size'=>3));
            if (count($options) > 1) {
                $mform->disabledIf('popupwidth', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            }
            $mform->setType('popupwidth', PARAM_INT);
            $mform->setDefault('popupwidth', $config->popupwidth);
            $mform->setAdvanced('popupwidth', $config->popupwidth_adv);

            $mform->addElement('text', 'popupheight', get_string('popupheight', 'agvideo'), array('size'=>3));
            if (count($options) > 1) {
                $mform->disabledIf('popupheight', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            }
            $mform->setType('popupheight', PARAM_INT);
            $mform->setDefault('popupheight', $config->popupheight);
            $mform->setAdvanced('popupheight', $config->popupheight_adv);
        }

        if (array_key_exists(RESOURCELIB_DISPLAY_AUTO, $options) or
          array_key_exists(RESOURCELIB_DISPLAY_FRAME, $options) or
          array_key_exists(RESOURCELIB_DISPLAY_NEW, $options) or
          array_key_exists(RESOURCELIB_DISPLAY_POPUP, $options)) {
            $mform->addElement('checkbox', 'printheading', get_string('printheading', 'agvideo'));
            $mform->disabledIf('printheading', 'display', 'eq', RESOURCELIB_DISPLAY_OPEN);
            $mform->setDefault('printheading', $config->printheading);
            $mform->setAdvanced('printheading', $config->printheading_adv);

            $mform->addElement('checkbox', 'printintro', get_string('printintro', 'agvideo'));
            $mform->disabledIf('printintro', 'display', 'eq', RESOURCELIB_DISPLAY_OPEN);
            $mform->setDefault('printintro', $config->printintro);
            $mform->setAdvanced('printintro', $config->printintro_adv);
        }

        //-------------------------------------------------------
        $this->standard_coursemodule_elements();

        //-------------------------------------------------------
        $this->add_action_buttons();
    }

    function data_preprocessing(&$default_values) {
        if (!empty($default_values['displayoptions'])) {
            $displayoptions = unserialize($default_values['displayoptions']);
            if (isset($displayoptions['printintro'])) {
                $default_values['printintro'] = $displayoptions['printintro'];
            }
            if (isset($displayoptions['printheading'])) {
                $default_values['printheading'] = $displayoptions['printheading'];
            }
            if (!empty($displayoptions['popupwidth'])) {
                $default_values['popupwidth'] = $displayoptions['popupwidth'];
            }
            if (!empty($displayoptions['popupheight'])) {
                $default_values['popupheight'] = $displayoptions['popupheight'];
            }
        }
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validating Entered url, we are looking for obvious problems only,
        // teachers are responsible for testing if it actually works.

        // This is not a security validation!! Teachers are allowed to enter "javascript:alert(666)" for example.

        // NOTE: do not try to explain the difference between URL and URI, people would be only confused...

        if (empty($data['relativepath'])) {
            $errors['relativepath'] = get_string('required');
        } else {
            $url = '';
            $url_temp = trim($data['relativepath']);
            $url_parts = parse_url($url_temp);
            if (array_key_exists('path',$url_parts) && $url_parts['path'] != '') {
                $url = '/'.ltrim($url_parts['path'],'/');
            }
            if (!empty($data['relativepath']) && !$url != '') {
                $errors['relativepath'] = get_string('invalidurlpath', 'agvideo');
            }
        }
        return $errors;
    }

}
