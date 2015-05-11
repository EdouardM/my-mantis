<?php
/**
 * Copyright © 2013 Andrej Pavlovic. All rights reserved.
 *
 * This code may not be used, copied, modified, sold, or extended without written
 * permission from Andrej Pavlovic (andrej.pavlovic@pokret.org).
 */

form_security_validate( 'plugin_PokretBilling_config_edit' );
auth_reauthenticate();
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

// Clean data
$f_time_tracking_enabled = gpc_get_bool( 'time_tracking_enabled', OFF );
$f_time_tracking_threshold = gpc_get_int( 'time_tracking_threshold' );

$f_report_enabled = gpc_get_bool( 'report_enabled', OFF );
$f_report_threshold = gpc_get_int( 'report_threshold' );
$f_report_tab_title = gpc_get_string( 'report_tab_title' );

$f_multiplier_enabled = gpc_get_bool( 'multiplier_enabled', OFF );
$f_multiplier_threshold = gpc_get_int( 'multiplier_threshold' );

$f_adjustment_enabled = gpc_get_bool( 'adjustment_enabled', OFF );
$f_adjustment_threshold = gpc_get_int( 'adjustment_threshold' );

$f_billable_enabled = gpc_get_bool( 'billable_enabled', OFF );
$f_billable_threshold = gpc_get_int( 'billable_threshold' );

$f_billed_enabled = gpc_get_bool( 'billed_enabled', OFF );
$f_billed_threshold = gpc_get_int( 'billed_threshold' );

$f_reviewed_enabled = gpc_get_bool( 'reviewed_enabled', OFF );
$f_reviewed_threshold = gpc_get_int( 'reviewed_threshold' );


// Store data
plugin_config_set( 'report_enabled', $f_report_enabled );
plugin_config_set( 'report_threshold', $f_report_threshold );
plugin_config_set( 'report_tab_title', $f_report_tab_title );

plugin_config_set( 'time_tracking_enabled', $f_time_tracking_enabled );
plugin_config_set( 'time_tracking_threshold', $f_time_tracking_threshold );

plugin_config_set( 'multiplier_enabled', $f_multiplier_enabled );
plugin_config_set( 'multiplier_threshold', $f_multiplier_threshold );

plugin_config_set( 'adjustment_enabled', $f_adjustment_enabled );
plugin_config_set( 'adjustment_threshold', $f_adjustment_threshold );

plugin_config_set( 'billable_enabled', $f_billable_enabled );
plugin_config_set( 'billable_threshold', $f_billable_threshold );

plugin_config_set( 'billed_enabled', $f_billed_enabled );
plugin_config_set( 'billed_threshold', $f_billed_threshold );

plugin_config_set( 'reviewed_enabled', $f_reviewed_enabled );
plugin_config_set( 'reviewed_threshold', $f_reviewed_threshold );


form_security_purge( 'plugin_PokretBilling_config_edit' );

print_successful_redirect( plugin_page( 'config', true ) );

