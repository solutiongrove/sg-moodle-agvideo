<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    $displayoptions = resourcelib_get_displayoptions(array(RESOURCELIB_DISPLAY_AUTO,
                                                           RESOURCELIB_DISPLAY_FRAME,
                                                           RESOURCELIB_DISPLAY_OPEN,
                                                           RESOURCELIB_DISPLAY_NEW,
                                                           RESOURCELIB_DISPLAY_POPUP,
                                                          ));
    $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_AUTO,
                                   RESOURCELIB_DISPLAY_OPEN,
                                   RESOURCELIB_DISPLAY_NEW,
                                  );

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_configtext('agvideo/framesize',
        get_string('framesize', 'agvideo'), get_string('configframesize', 'agvideo'), 130, PARAM_INT));
    $settings->add(new admin_setting_configcheckbox('agvideo/requiremodintro',
        get_string('requiremodintro', 'admin'), get_string('configrequiremodintro', 'admin'), 1));
    $settings->add(new admin_setting_configmultiselect('agvideo/displayoptions',
        get_string('displayoptions', 'agvideo'), get_string('configdisplayoptions', 'agvideo'),
        $defaultdisplayoptions, $displayoptions));

    //--- modedit defaults -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('agvideomodeditdefaults', get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configcheckbox_with_advanced('agvideo/printheading',
        get_string('printheading', 'agvideo'), get_string('printheadingexplain', 'agvideo'),
        array('value'=>1, 'adv'=>false)));
    $settings->add(new admin_setting_configcheckbox_with_advanced('agvideo/printintro',
        get_string('printintro', 'agvideo'), get_string('printintroexplain', 'agvideo'),
        array('value'=>1, 'adv'=>false)));
    $settings->add(new admin_setting_configselect_with_advanced('agvideo/display',
        get_string('displayselect', 'agvideo'), get_string('displayselectexplain', 'agvideo'),
        array('value'=>RESOURCELIB_DISPLAY_AUTO, 'adv'=>false), $displayoptions));
    $settings->add(new admin_setting_configtext_with_advanced('agvideo/popupwidth',
        get_string('popupwidth', 'agvideo'), get_string('popupwidthexplain', 'agvideo'),
        array('value'=>620, 'adv'=>true), PARAM_INT, 7));
    $settings->add(new admin_setting_configtext_with_advanced('agvideo/popupheight',
        get_string('popupheight', 'agvideo'), get_string('popupheightexplain', 'agvideo'),
        array('value'=>450, 'adv'=>true), PARAM_INT, 7));

    //--- cron settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('agvideocronsettings', get_string('cronsettings', 'agvideo'), get_string('cronsettings_intro', 'agvideo')));
    $settings->add(new admin_setting_configtext('agvideo/lastwatchedsyncvalue',
        get_string('lastwatchedsyncvalue', 'agvideo'), get_string('lastwatchedsyncvalue_help', 'agvideo'), 0, PARAM_INT));

}
