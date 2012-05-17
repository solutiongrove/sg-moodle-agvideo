<?php

defined('MOODLE_INTERNAL') || die();

$module->version      = 2012041900;       // The current module version (Date: YYYYMMDDXX)
$module->requires     = 2011112900;       // Requires this Moodle version
$module->component    = 'mod_agvideo';    // Full name of the plugin (used for diagnostics)
$module->cron         = 3600;
$module->dependencies = array('local_agbase' => 2012041900);