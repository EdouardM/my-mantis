<?PHP
$f_username = gpc_get_string( 'username', '' );

	if ( is_blank( $f_username ) ) {
		$t_user_id = gpc_get_int( 'user_id' );
	} else {
		$t_user_id = user_get_id_by_name( $f_username );
		if ( $t_user_id === false ) {
			# If we can't find the user by name, attempt to find by email.
			$t_user_id = user_get_id_by_email( $f_username );
			if ( $t_user_id === false ) {
				error_parameters( $f_username );
				trigger_error( ERROR_USER_BY_NAME_NOT_FOUND, ERROR );
			}
		}
	}
if ( access_has_global_level( config_get( 'manage_user_threshold' ) )  ) {
	require_once( config_get( 'plugin_path' ) . 'Usergroups' . DIRECTORY_SEPARATOR . 'Usergroups_api.php' );  
	?>
	<br />
	<div align="center">
	<table class="width75" cellspacing="1">
	<!-- Title -->
	<tr>
	<td class="form-title" colspan="2">
	<?php echo lang_get( 'add_usergroup_title' ) ?>
	</td>
	</tr>

	<!-- Assigned Groups -->
	<tr <?php echo helper_alternate_class( 1 ) ?> valign="top">
	<td class="category" width="30%">
	<?php echo lang_get( 'assigned_groups' ) ?>
	</td>
	<td width="70%">
	<?php 
	print_group_user_list( $t_user_id) ;
	?>
	</td>
	</tr>

	<form method="post" action="plugins/Usergroups/pages/manage_usergroup_add.php">
	<?php echo form_security_field( 'manage_user_group_add' ) ?>
	<input type="hidden" name="user_id" value="<?php echo $t_user_id ?>" />
	<!-- Unassigned Group Selection -->
	<tr <?php echo helper_alternate_class() ?> valign="top">
	<td class="category">
	<?php echo lang_get( 'unassigned_groups' ) ?>
	</td>
	<td>
	<select name="group_id[]" multiple="multiple" size="5">
	<?php 
	print_group_user_list_option_list2( $t_user_id ) 
	?>
	</select>
	</td>
	</tr>

	<!-- Submit Buttom -->
	<tr>
	<td class="center" colspan="2">
	<input type="submit" class="button" value="<?php echo lang_get( 'add_group_button' ) ?>" />
	</td>
	</tr>
	</form>
	</table>
	</div>
	<?php
}