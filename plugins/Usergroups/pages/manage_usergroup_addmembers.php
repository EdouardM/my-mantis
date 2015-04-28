<?PHP
require_once( '../../../core.php' );
$groupid		= $_REQUEST['group_id'];
if (!isset($_POST['Return'])) {
	$ugrp_table	= plugin_table('usergroup','Usergroups');
	$f_user_id	= gpc_get_int_array( 'user_id', array() ); 
	foreach ( $f_user_id as $userid ) {
		$query = "INSERT INTO $ugrp_table ( group_id,user_id ) VALUES ('$groupid', '$userid')";
		$res=db_query_bound($query);
	}
}
$link= "plugins/Usergroups/pages/ugrp_show.php?edit_id=".$groupid;
print_header_redirect( $link );