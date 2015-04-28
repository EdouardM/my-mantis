<?PHP
require_once( '../../../core.php' );
html_page_top1(  );
html_page_top2();
print_manage_menu();
require_once( config_get( 'plugin_path' ) . 'Usergroups' . DIRECTORY_SEPARATOR . 'Usergroups_api.php' );  
$reqVar = '_' . $_SERVER['REQUEST_METHOD'];
$form_vars = $$reqVar;
$edit_id = $form_vars['edit_id'] ;
$grp_table	= plugin_table('groups','Usergroups');
$basepad=config_get('path');
// get current values
$query = "SELECT * FROM $grp_table WHERE group_id = $edit_id ";
$result = db_query_bound($query);
$row = db_fetch_array( $result );
$name=$row['group_name'];
?>
<form method="post" action="manage_usergroup_addmembers.php">
<?php echo form_security_field( 'manage_user_group_addmembers' ) ?>
<input type="hidden" name="group_id" value="<?php echo $edit_id ?>" />
<table align="center" class="width50" cellspacing="1">
<tr <?php echo helper_alternate_class() ?> valign="top">
<td class="category">
<?php echo $name ?>
</td></tr>
<tr><td>
<select name="user_id[]" multiple="multiple" size="20">
<?php 
print_member_option_list($edit_id) ;
?>
</select>
</td></tr>
<tr><td><input name="Add Selected" type="submit" value="Update"><input name="Return" type="submit" value="Cancel"></td>
</tr>
</table>
<?php
html_page_bottom1( __FILE__ );