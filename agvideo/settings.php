<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_configcheckbox('agvideo/requiremodintro',
        get_string('requiremodintro', 'admin'), get_string('configrequiremodintro', 'admin'), 1));

    //--- modedit defaults -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('agvideomodeditdefaults', get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configcheckbox_with_advanced('agvideo/printheading',
        get_string('printheading', 'agvideo'), get_string('printheadingexplain', 'agvideo'),
        array('value'=>1, 'adv'=>false)));
    $settings->add(new admin_setting_configcheckbox_with_advanced('agvideo/printintro',
        get_string('printintro', 'agvideo'), get_string('printintroexplain', 'agvideo'),
        array('value'=>1, 'adv'=>false)));

    //--- cron settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('agvideocronsettings', get_string('cronsettings', 'agvideo'), get_string('cronsettings_intro', 'agvideo')));
    $settings->add(new admin_setting_configtext('agvideo/lastwatchedsyncvalue',
        get_string('lastwatchedsyncvalue', 'agvideo'), get_string('lastwatchedsyncvalue_help', 'agvideo'), 0, PARAM_INT));

}
