<?php
/**
 * Copyright © 2013 Andrej Pavlovic. All rights reserved.
 *
 * This code may not be used, copied, modified, sold, or extended without written
 * permission from Andrej Pavlovic (andrej.pavlovic@pokret.org).
 */

auth_reauthenticate();
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

html_page_top1( plugin_lang_get( 'title' ) );
html_page_top2();

print_manage_menu();

?>

<br/>
<form action="<?php echo plugin_page( 'config_edit' ) ?>" method="post">
<?php echo form_security_field( 'plugin_PokretBilling_config_edit' ) ?>
<table class="width60" align="center" cellspacing="1">

<tr>
<td class="form-title" colspan="2"><?php echo plugin_lang_get( 'title' ), ': ', plugin_lang_get( 'configuration_report' ) ?></td>
</tr>


<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'report_enabled' ) ?></td>
<td>
	<input name="report_enabled" type="checkbox" <?php echo (ON == plugin_config_get( 'report_enabled' ) ? 'checked="checked"' : '') ?>>
	<select name="report_threshold"><?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'report_threshold' ) ) ?></select>
</td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'report_tab_title' ) ?></td>
<td>
	<input name="report_tab_title" type="text" value="<?php echo string_attribute(plugin_config_get( 'report_tab_title' ))?>" />
</td>
</tr>

<tr>
<td class="form-title" colspan="2"><?php echo plugin_lang_get( 'title' ), ': ', plugin_lang_get( 'configuration_field' ) ?></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'time_tracking_enabled' ) ?></td>
<td>
	<input name="time_tracking_enabled" type="checkbox" <?php echo (ON == plugin_config_get( 'time_tracking_enabled' ) ? 'checked="checked"' : '') ?>>
	<select name="time_tracking_threshold"><?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'time_tracking_threshold' ) ) ?></select>
</td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'multiplier_enabled' ) ?></td>
<td>
	<input name="multiplier_enabled" type="checkbox" <?php echo (ON == plugin_config_get( 'multiplier_enabled' ) ? 'checked="checked"' : '') ?>>
	<select name="multiplier_threshold"><?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'multiplier_threshold' ) ) ?></select>
</td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'adjustment_enabled' ) ?></td>
<td>
	<input name="adjustment_enabled" type="checkbox" <?php echo (ON == plugin_config_get( 'adjustment_enabled' ) ? 'checked="checked"' : '') ?>>
	<select name="adjustment_threshold"><?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'adjustment_threshold' ) ) ?></select>
</td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'billable_enabled' ) ?></td>
<td>
	<input name="billable_enabled" type="checkbox" <?php echo (ON == plugin_config_get( 'billable_enabled' ) ? 'checked="checked"' : '') ?>>
	<select name="billable_threshold"><?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'billable_threshold' ) ) ?></select>
</td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'billed_enabled' ) ?></td>
<td>
	<input name="billed_enabled" type="checkbox" <?php echo (ON == plugin_config_get( 'billed_enabled' ) ? 'checked="checked"' : '') ?>>
	<select name="billed_threshold"><?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'billed_threshold' ) ) ?></select>
</td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'reviewed_enabled' ) ?></td>
<td>
	<input name="reviewed_enabled" type="checkbox" <?php echo (ON == plugin_config_get( 'reviewed_enabled' ) ? 'checked="checked"' : '') ?>>
	<select name="reviewed_threshold"><?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'reviewed_threshold' ) ) ?></select>
</td>
</tr>


<tr>
<td class="center" colspan="2"><input type="submit" value="<?php echo plugin_lang_get( 'update_configuration' ) ?>"/></td>
</tr>

</table>
</form>

<?php
html_page_bottom1( __FILE__ );

