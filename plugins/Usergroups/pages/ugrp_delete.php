<?PHP
require_once( '../../../core.php' );
$reqVar = '_' . $_SERVER['REQUEST_METHOD'];
$form_vars = $$reqVar;
$delete_id = $form_vars['delete_id'] ;
$grp_table	= plugin_table('groups','Usergroups');
$ugrp_table	= plugin_table('usergroup','Usergroups');
require_once( '../../../core.php' );
// first check if this entry is still in use
$query= "select * from $ugrp_table where group_id= $delete_id";
$result = db_query_bound($query);
$res2=db_num_rows($result);
if ($res2 >0){
	trigger_error( ERROR_USERGROUP_ACTIVE,ERROR );
} else {
	# Deleting category
	$query = "DELETE FROM $grp_table WHERE group_id = $delete_id";        
	db_query_bound($query);
}
print_header_redirect( 'plugin.php?page=Usergroups/config' );
