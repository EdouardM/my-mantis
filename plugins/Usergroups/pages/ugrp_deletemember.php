<?PHP
require_once( '../../../core.php' );
$reqVar = '_' . $_SERVER['REQUEST_METHOD'];
$form_vars = $$reqVar;
$delete_id = $form_vars['delete_id'] ;
$group_id = $form_vars['group_id'] ;
$ugrp_table	= plugin_table('usergroup','Usergroups');
require_once( '../../../core.php' );
$query = "DELETE FROM $ugrp_table WHERE user_id = $delete_id and group_id=$group_id";        
db_query_bound($query);
print_header_redirect( 'plugin.php?page=Usergroups/config' );