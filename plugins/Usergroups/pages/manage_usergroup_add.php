<?PHP
require_once( '../../../core.php' );
$ugrp_table	= plugin_table('usergroup','Usergroups');
$userid		= $_REQUEST['user_id'];
$f_group_id	= gpc_get_int_array( 'group_id', array() ); 
foreach ( $f_group_id as $groupid ) {
	$query = "INSERT INTO $ugrp_table ( group_id,user_id ) VALUES ('$groupid', '$userid')";
	$res=db_query_bound($query);
}
$link='manage_user_edit_page.php?user_id='.$userid;
print_header_redirect( $link );