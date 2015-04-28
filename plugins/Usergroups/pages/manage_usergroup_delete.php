<?PHP
require_once( '../../../core.php' );
$ugrp_table	= plugin_table('usergroup','Usergroups');
$groupid	= $_REQUEST['group_id'];
$userid		= $_REQUEST['user_id'];
$query = "DELETE FROM $ugrp_table WHERE group_id = $groupid and user_id=$userid";        
db_query_bound($query);
$link='manage_user_edit_page.php?user_id='.$userid;
print_header_redirect( $link );