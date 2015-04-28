<?php
# authenticate
auth_reauthenticate();
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

# Read results
$f_mail_group			= gpc_get_int('mail_group', ON);

# update results
plugin_config_set( 'mail_group', $f_mail_group );

# redirect
print_successful_redirect( plugin_page( 'config', true ) );
