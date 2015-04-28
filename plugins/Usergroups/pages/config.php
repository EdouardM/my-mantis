<?php	
########################################################
# Mantis Bugtracker Plugin Usergroups
#
# By Cas Nuy  www.nuy.info 2010
# To be used with Mantis 1.20 and above
#
########################################################
# what is the table for tasks
$grp_table	= plugin_table('groups');
html_page_top1(  );
html_page_top2();
print_manage_menu();
?>
<tr>
<td class="center" colspan="6">
<br>
<?php 
$colspan=4;
?>
<table align="center" class="width75" cellspacing="1">
<tr>
<td colspan="<?php echo $colspan ?>" class="row-category"><div align="left"><a name="grouprecord"></a>
<?php 
echo lang_get( 'allgroups' ); 
?>
<form name="config" method="post" action="plugins/Usergroups/pages/ugrp_add.php">
</div>
</td>
</tr>
<tr class="row-category">
<td><div align="center"><?php echo lang_get( 'groupname' ); ?></div></td>
<td><div align="center"><?php echo lang_get( 'groupdesc' ); ?></div></td>
<td><div align="center"><?php echo lang_get( 'groupmail' ); ?></div></td>
<td><div align="center"><?php echo lang_get( 'groupactions' ); ?></div></td>
</tr>
<tr>
<td><div align="center">
<input name="group_name" type="text" maxlength=10 >
</td>
<td><div align="center">
<input name="group_desc" type="text" maxlength=50 >
</td>
<td><div align="center">
<input name="group_mail" type="text" maxlength=50 >
</td>
<td><div align="center">
<input name="<?php echo lang_get( 'groupsubmit' ) ?>" type="submit" value="<?php echo lang_get( 'groupsubmit' ) ?>">
</td>
</td>
</tr>
</form>
<?php
# Pull all group-Record entries 
$query = "SELECT * FROM $grp_table order by group_name";
$result = db_query_bound($query);
while ($row = db_fetch_array($result)) {
	?>
	<tr <?php echo helper_alternate_class() ?>>
	<td><div align="center">
	<a href="plugins/Usergroups/pages/ugrp_show.php?edit_id=<?php echo $row["group_id"]; ?>"><?php echo $row['group_name'] ?></a><br>
	</td>
	<td><div align="center"><?php  echo $row["group_desc"]; ?></td>
	<td><div align="center"><?php  echo $row["group_mail"]; ?></td>
	<td><div align="center">
	<a href="plugins/Usergroups/pages/ugrp_delete.php?delete_id=<?php echo $row["group_id"]; ?>"><?php echo lang_get( 'groupdelete' ) ?></a>
	<===> 
	<a href="javascript: void(0)" onclick="window.open('plugins/Usergroups/pages/ugrp_edit.php?edit_id=<?php echo $row["group_id"]; ?>', 'GroupEdit', 'width=900, height=200'); return false;"><?php echo lang_get( 'groupedit' ) ?></a><br>
	</td>
	</tr>
	<?php
}	 
?>
</table>
<form action="<?php echo plugin_page( 'config_edit' ) ?>" method="post">

	<table align="center" class="width75" cellspacing="1">

		<tr <?php echo helper_alternate_class() ?>>
			<td class="category">
				<?php echo lang_get( 'mailtogroup' ) ?>
			</td>
			<td class='center'>
				<label><input type="radio" name="mail_group" value="1" <?php echo ( ON == plugin_config_get( 'mail_group' ) ) ? 'checked="checked" ' : ''?>/>
				<?php echo lang_get( 'usergroups_enabled' ) ?></label>
				<label><input type="radio" name="mail_group" value="0" <?php echo ( OFF == plugin_config_get( 'mail_group' ) ) ? 'checked="checked" ' : ''?>/>
				<?php echo lang_get( 'usergroups_disabled' ) ?></label>
			</td>
		</tr>
		
		<tr>
			<td class="center" colspan="3">
				<input type="submit" class="button" value="<?php echo lang_get( 'change_configuration' ) ?>" />
			</td>
		</tr>

	</table>
<form> 
<?php
html_page_bottom1( __FILE__ );