<?PHP
require_once( '../../../core.php' );
$grp_table	= plugin_table('groups','Usergroups');
$name		= $_REQUEST['group_name'];
$desc		= $_REQUEST['group_desc'];
$mail		= $_REQUEST['group_mail'];
// first check if entry already exists
$query= "select * from $grp_table where upper(trim(group_name))=upper(trim('$name'))";
$result = db_query_bound($query);
$res2=db_num_rows($result);
if ($res2 == 0){
	$query = "INSERT INTO $grp_table ( group_name,group_desc,group_mail) VALUES ('$name', '$desc','$mail')";
	if(!db_query_bound($query)){ 
		trigger_error( ERROR_USERGROUP_EXISTS, ERROR );
	}
}
print_header_redirect( 'plugin.php?page=Usergroups/config' );