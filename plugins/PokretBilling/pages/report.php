<?php
/**
 * Copyright © 2013 Andrej Pavlovic. All rights reserved.
 *
 * This code may not be used, copied, modified, sold, or extended without written
 * permission from Andrej Pavlovic (andrej.pavlovic@pokret.org).
 */

if (OFF == plugin_config_get( 'report_enabled' ))
	access_denied();

access_ensure_global_level( plugin_config_get( 'report_threshold' ) );

$t_today = date( "d:m:Y" );
$t_date_submitted = isset( $t_bug ) ? date( "d:m:Y", $t_bug->date_submitted ) : $t_today;

$t_bugnote_stats = array();
$t_bugnote_stats_from_def = $t_date_submitted;
$t_bugnote_stats_from_def_ar = explode ( ":", $t_bugnote_stats_from_def );
$t_bugnote_stats_from_def_d = $t_bugnote_stats_from_def_ar[0];
$t_bugnote_stats_from_def_m = $t_bugnote_stats_from_def_ar[1];
$t_bugnote_stats_from_def_y = $t_bugnote_stats_from_def_ar[2];

$t_bugnote_stats_from_d = gpc_get_int('start_day', $t_bugnote_stats_from_def_d);
$t_bugnote_stats_from_m = gpc_get_int('start_month', $t_bugnote_stats_from_def_m);
$t_bugnote_stats_from_y = gpc_get_int('start_year', $t_bugnote_stats_from_def_y);

$t_bugnote_stats_to_def = $t_today;
$t_bugnote_stats_to_def_ar = explode ( ":", $t_bugnote_stats_to_def );
$t_bugnote_stats_to_def_d = $t_bugnote_stats_to_def_ar[0];
$t_bugnote_stats_to_def_m = $t_bugnote_stats_to_def_ar[1];
$t_bugnote_stats_to_def_y = $t_bugnote_stats_to_def_ar[2];

$t_bugnote_stats_to_d = gpc_get_int('end_day', $t_bugnote_stats_to_def_d);
$t_bugnote_stats_to_m = gpc_get_int('end_month', $t_bugnote_stats_to_def_m);
$t_bugnote_stats_to_y = gpc_get_int('end_year', $t_bugnote_stats_to_def_y);

$f_submit = gpc_get_string('submit', '');

$f_user = gpc_get_int('user', ALL_USERS);
$f_project_trace = gpc_get_string('project', ALL_PROJECTS);
$f_exclude_subprojects = gpc_get_bool('exclude_subprojects', false);
$f_cost = gpc_get_int( 'cost', '' );
$f_format = gpc_get_string('format', 'html');

$f_field_values = array();
foreach(array('billable', 'reviewed', 'billed') as $field_name)
{
	$f_field_values[$field_name] = gpc_get_string("billing_{$field_name}", null);
}

// bug note actions
$f_bug_note_arr	= gpc_get_int_array( 'bug_note_arr', array() );
$f_bug_note_action = gpc_get_string('bug_note_action', ALL_PROJECTS);
$updated_bug_note_number = session_get('pokret_billing_report_updated_bug_note_number', null);
session_delete('pokret_billing_report_updated_bug_note_number');

$errors = array();

for ($i = 1; $i == 1 && !is_blank( $f_submit ); $i++)
{
	// process bug note actions if set
	if ($f_bug_note_arr && $f_bug_note_action) {
		list($feature, $featureValue) = explode(':', $f_bug_note_action);
		
		$featureValue = $featureValue == YES;
		
		$modifiedBugNotes = array();
		
		// update each bug note
		foreach($f_bug_note_arr as $bug_note_id) {
			$t_bug_id = bugnote_get_field( $bug_note_id, 'bug_id' );
			
			if (plugin_config_get($feature . '_enabled')
				&& access_has_bug_level( plugin_config_get( $feature . '_threshold' ), $t_bug_id )) {
				
				PokretBillingAPI::bugnote_set_field_value($feature, $featureValue, $bug_note_id);
				
				$modifiedBugNotes[] = $bug_note_id;
			}
		}
		
		if (sizeof($modifiedBugNotes) > 0) {
			session_set('pokret_billing_report_updated_bug_note_number', sizeof($modifiedBugNotes));
		}
		
		print_header_redirect( $_SERVER['REQUEST_URI'], true, false, true );
	}
	
	foreach(array('billable', 'reviewed', 'billed') as $field_name)
	{
		switch($f_field_values[$field_name]) {
			case YES:
				$t_field_values[$field_name] = true;
				break;
			
			case NO:
				$t_field_values[$field_name] = false;
				break;
			
			default:
				$t_field_values[$field_name] = null;
				break;
		}
	}
	
	// validate start/end dates
	if (!checkdate($t_bugnote_stats_from_m, $t_bugnote_stats_from_d, $t_bugnote_stats_from_y)) {
		$errors[] = plugin_lang_get( 'report_error_invalid_start_date' );
		break;
	} else if (!checkdate($t_bugnote_stats_to_m, $t_bugnote_stats_to_d, $t_bugnote_stats_to_y)) {
		$errors[] = plugin_lang_get( 'report_error_invalid_end_date' );
		break;
	}
	
	$t_from = "$t_bugnote_stats_from_y-$t_bugnote_stats_from_m-$t_bugnote_stats_from_d";
	$t_to = "$t_bugnote_stats_to_y-$t_bugnote_stats_to_m-$t_bugnote_stats_to_d";
	
	$t_project_trace = explode(';', $f_project_trace);
	$t_project_trace_bottom = $t_project_trace[sizeof($t_project_trace) - 1];
	
	$t_bugnote_stats = PokretBillingAPI::bugnote_stats_get_project_array( $t_project_trace_bottom, $f_user, $t_from, $t_to, $f_exclude_subprojects, $t_field_values );
	
	// validate we got some records
	if (sizeof($t_bugnote_stats) < 1) {
		$errors[] = plugin_lang_get( 'report_error_no_records_found' );
		break;
	}
	
	// this is where all the table data is stored before it is outputted
	$table = array(
		'headings' => array(
			'date' => 'Date',
			'project' => 'Project',
			'sub-project' => 'Sub-project',
			'name' => lang_get( 'name' ),
			'issue' => 'Issue',
			'note' => 'Note',
			'title' => 'Title',
			'description' => 'Activity Description',
			'billable' => 'Billable',
			'reviewed' => 'Reviewed',
			'billed' => 'Billed',
			'hours' => 'Hours',
			'multiplier' => 'Multiplier',
			'adjustment' => 'Adjustment',
			'total' => 'Total',
			'cost' => lang_get( 'time_tracking_cost' ),
		),
		'rows' => array(),
	);
	
	if ( is_blank( $f_cost ) || ( (double)$f_cost == 0 ) ) {
		$t_cost_col = false;
    } else {
    	$t_cost_col = true;
    	$c_cost = (double)$f_cost;
    }
	
	$t_sum_in_hours = 0;
	$t_adjustment_sum_in_hours = 0;
	$t_multiplier_total = 0;

	foreach ( $t_bugnote_stats as $t_item )
	{
		$t_item['time_tracking_hours'] = round( $t_item['time_tracking'] / 60, 2);
		$t_sum_in_hours += $t_item['time_tracking_hours'];
		
		$t_item['adjustment'] = round( $t_item['pokret_billing_adjustment'] / 60, 2);
		$t_adjustment_sum_in_hours += $t_item['adjustment'];
		
		$t_multiplier_total += $t_item['pokret_billing_multiplier'];
		
		$t_item['total'] = round( (
			$t_item['time_tracking'] *
			((plugin_config_get('multiplier_enabled') && access_has_global_level(plugin_config_get("multiplier_threshold"))) ? $t_item['pokret_billing_multiplier'] : 1) +
			((plugin_config_get('adjustment_enabled') && access_has_global_level(plugin_config_get("adjustment_threshold"))) ? $t_item['pokret_billing_adjustment'] : 0)
		) / 60, 3);
		
		if ($t_item['total'] < 0) $t_item['total'] = 0;
		$t_sum_in_hours_total += $t_item['total'];
		
		$t_item['cost'] = $c_cost * $t_item['total'];
		
		$row = array(
			'class' => 'row-data',
			'cells' => array(
				'date' => array(
					'value' => date('Y-m-d', $t_item['date_submitted']),
					'format' => 'string_display',
				),
				'project' => array(
					'value' => (is_null($t_item['parent_id']) ? $t_item['name'] : $t_item['parent_name']),
					'format' => 'string_display',
				),
				'sub-project' => array(
					'value' => (is_null($t_item['parent_id']) ? '' : $t_item['name']),
					'format' => 'string_display',
				),
				'name' => array(
					'value' => $t_item['realname'],
					'format' => 'string_attribute',
				),
				'issue' => array(
					'value' => $t_item['bug_id'],
					'format' => 'string_get_bug_view_link',
				),
				'note' => array(
					'value' => $t_item['bugnote_id'],
					'format' => 'pokret_billing_string_get_bugnote_edit_link',
				),
				'title' => array(
					'value' => $t_item['summary'],
					'format' => 'string_display',
				),
				'description' => array(
					'value' => trim($t_item['note']),
					'format' => 'string_display_links',
				),
				'billable' => array(
					'value' => $t_item['pokret_billing_billable'] ? lang_get( 'yes' ) : lang_get( 'no' ),
					'format' => 'string_attribute',
				),
				'reviewed' => array(
					'value' => $t_item['pokret_billing_reviewed'] ? lang_get( 'yes' ) : lang_get( 'no' ),
					'format' => 'string_attribute',
				),
				'billed' => array(
					'value' => $t_item['pokret_billing_billed'] ? lang_get( 'yes' ) : lang_get( 'no' ),
					'format' => 'string_attribute',
				),
				'hours' => array(
					'value' => $t_item['time_tracking_hours'],
					'format' => 'string_attribute',
				),
				'multiplier' => array(
					'value' => $t_item['pokret_billing_multiplier'],
					'format' => 'string_attribute',
				),
				'adjustment' => array(
					'value' => $t_item['adjustment'],
					'format' => 'string_attribute',
				),
				'total' => array(
					'value' => $t_item['total'],
					'format' => 'string_attribute',
				),
				'cost' => array(
					'value' => number_format( $t_item['cost'], 2 ),
					'format' => 'string_attribute',
				),
			),
		);
		
		$table['rows'][] = $row;
	}
	
	// Last row with totals
	$row = array(
		'class' => 'row-category-history',
		'cells' => array(
			'total-time' => array(
				'value' => lang_get( 'total_time' ),
				'format' => 'string_attribute',
				'colspan' => 'fill',
				'style' => array(
					'bold' => true,
				),
			),
			'hours' => array(
				'value' => $t_sum_in_hours,
				'format' => 'string_attribute',
				'style' => array(
					'bold' => true,
				),
			),
			'multiplier' => array(
				'value' => round($t_multiplier_total / sizeof($t_bugnote_stats), 2),
				'format' => 'string_attribute',
				'style' => array(
					'bold' => true,
				),
			),
			'adjustment' => array(
				'value' => round($t_adjustment_sum_in_hours, 2),
				'format' => 'string_attribute',
				'style' => array(
					'bold' => true,
				),
			),
			'total' => array(
				'value' => round($t_sum_in_hours_total, 2),
				'format' => 'string_attribute',
				'style' => array(
					'bold' => true,
				),
			),
			'cost' => array(
				'value' => number_format( round($t_sum_in_hours_total, 2) * $c_cost, 2 ),
				'format' => 'string_attribute',
			)
		),
	);
	
	$table['rows'][] = $row;
	
	// remove column from table
	function removeColumnInTable(&$table, $column) {
		unset($table['headings'][$column]);
		
		foreach($table['rows'] as &$row) {
			unset($row['cells'][$column]);
		}
		unset($row);
	}
	
	// remove columns based on permissions / settings
	if (!$t_cost_col) {
		removeColumnInTable($table, 'cost');
	}
	
	foreach(array('adjustment', 'multiplier', 'billable', 'reviewed', 'billed') as $field_name)
	{
		if (!plugin_config_get("{$field_name}_enabled") || !access_has_global_level(plugin_config_get("{$field_name}_threshold"))) {
			removeColumnInTable($table, $field_name);
		}
	}
	
	if ($f_format == 'xlsx')
	{
		$filename = $t_from . '_' . $t_to . '.xlsx';
		PokretBillingAPI::bugnote_report_xlsx_output($table, $filename);
	}
}

function pokret_billing_string_get_bugnote_edit_link($p_bugnote_id)
{
	return sprintf('<a href="bugnote_edit_page.php?bugnote_id=%s" target="_blank">%s</a>', intval($p_bugnote_id), $p_bugnote_id);
}

html_page_top1( plugin_lang_get( 'title' ) );
html_page_top2();
?>
<br/>
<form method="get" action="">
<input type="hidden" name="page" value="<?php echo htmlspecialchars(plugin_get_current()) ?>/report">
<?php # CSRF protection not required here - form does not result in modifications ?>
<table border="0" class="width60" align="center" cellspacing="0">
<?php if (sizeof($errors) > 0 || $updated_bug_note_number !== null): ?>
<tr>
	<td colspan="2">
		<ul>
			<?php if ($updated_bug_note_number !== null): ?>
				<li class="positive"><?php echo sprintf(plugin_lang_get( 'report_success_bug_notes_updated' ), $updated_bug_note_number) ?></li>
			<?php endif ?>
			
			<?php if (sizeof($errors) > 0): ?>
				<?php foreach($errors as $error):?>
					<li class="negative"><?php echo htmlspecialchars($error) ?></li>
				<?php endforeach?>
			<?php endif ?>
		</ul>
	</td>
</tr>
<?php endif ?>
<tr>
	<td class="form-title" colspan="2">
		<?php echo plugin_lang_get( 'report_filter' ) ?>
	</td>
</tr>
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<?php echo plugin_lang_get( 'report_user' ) ?>
	</td>
	<td>
		<select name="user">
			<option value="<?php echo htmlspecialchars(ALL_USERS)?>"><?php echo lang_get( 'all_users' ) ?></option>
			<?php print_user_option_list($f_user) ?>
		</select>
	</td>
</tr>
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<?php echo plugin_lang_get( 'report_project' ) ?>
	</td>
	<td>
		<select name="project">
			<?php print_project_option_list($f_project_trace, true, null, true) ?>
		</select>
		
		<input id="exclude_subprojects" type="checkbox" name="exclude_subprojects" <?php echo ($f_exclude_subprojects) ? 'checked="checked"' : '' ?> value="1" />
		<label for="exclude_subprojects"><?php echo plugin_lang_get( 'report_exclude_subprojects') ?></label>
	</td>
</tr>
<tr <?php echo helper_alternate_class() ?>>
        <td class="category" width="25%">
        	Range
        </td>
        <td>
	        <?php
				$t_filter = array();
				$t_filter['do_filter_by_date'] = 'on';
				$t_filter['start_day'] = $t_bugnote_stats_from_d;
				$t_filter['start_month'] = $t_bugnote_stats_from_m;
				$t_filter['start_year'] = $t_bugnote_stats_from_y;
				$t_filter['end_day'] = $t_bugnote_stats_to_d;
				$t_filter['end_month'] = $t_bugnote_stats_to_m;
				$t_filter['end_year'] = $t_bugnote_stats_to_y;
				print_filter_do_filter_by_date(true);
			?>
		</td>
</tr>
<?php foreach (array('billable', 'reviewed', 'billed') as $field_name): ?>
<?php if (!plugin_config_get("{$field_name}_enabled") || !access_has_global_level(plugin_config_get("{$field_name}_threshold"))) continue; ?>
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<?php echo plugin_lang_get( $field_name ) ?>
	</td>
	<td>
		<select name="billing_<?php echo $field_name ?>">
			<option value=""></option>
			<?php foreach(array(NO => lang_get( 'no' ), YES => lang_get( 'yes' )) as $value => $text): ?>
				<option value="<?php echo htmlspecialchars($value) ?>" <?php echo check_selected($value, $f_field_values[$field_name])?>><?php echo htmlspecialchars($text)?></option>
			<?php endforeach ?>
		</select>
	</td>
</tr>
<?php endforeach; ?>
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<?php echo lang_get( 'time_tracking_cost' ) ?>
	</td>
	<td>
		<input type="text" name="cost" size="6" value="<?php echo $f_cost ?>" />
	</td>
</tr>
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<?php echo plugin_lang_get( 'report_format' ) ?>
	</td>
	<td>
		<select name="format">
			<?php foreach(array('html' => plugin_lang_get('report_format_html'), 'xlsx' => plugin_lang_get('report_format_xlsx')) as $value => $text): ?>
				<option value="<?php echo htmlspecialchars($value) ?>" <?php echo check_selected($value, $f_format)?>><?php echo htmlspecialchars($text)?></option>
			<?php endforeach ?>
		</select>
	</td>
</tr>
<tr>
	<td class="category">
		Generate
	</td>
	<td>
		<input type="submit" class="button" name="submit" value="<?php echo plugin_lang_get( 'report_button_submit' ) ?>" />
	</td>
</tr>

</table>
</form>

<?php if ( !is_blank( $f_submit )  && sizeof($errors) < 1 && sizeof($t_bugnote_stats) > 0): ?>

<br />

<form method="post" action="" name="notes">
	<table border="0" class="width100" cellspacing="0">
	<tr class="row-category-history">
	<?php if (PokretBillingAPI::access_has_global_level_any_feature()): ?>
		<td>&nbsp;</td>
	<?php endif ?>
	<?php foreach ($table['headings'] as $heading) { ?>
		<td class="small-caption">
			<?php echo $heading ?>
		</td>
	<?php } ?>
	</tr>
	<?php foreach ($table['rows'] as $row): ?>
		<tr <?php echo ($row['class'] != 'row-data') ? 'class="'.$row['class'].'"' :  helper_alternate_class() ?>>
			
			<?php if (PokretBillingAPI::access_has_global_level_any_feature()): ?>
				<td>
					<?php if ($row['class'] == 'row-data'): ?>
						<input type="checkbox" name="bug_note_arr[]" value="<?php echo $row['cells']['note']['value'] ?>" />
					<?php else: ?>
						&nbsp;
					<?php endif; ?>
				</td>
			<?php endif ?>
			
			<?php foreach($row['cells'] as $data): ?>
				<td class="small-caption" <?php echo !empty($data['colspan']) ? 'colspan="'. (($data['colspan'] == 'fill') ? sizeof($table['headings']) - sizeof($row['cells']) + 1: $data['colspan']).'"' : ''?>>
					<?php
						if (strlen($data['value']) == 0)
						{
							echo '&nbsp;';
						}
						else
						{
							echo $data['format']($data['value']);
						}
					?>
				</td>
			<?php endforeach; ?>
		</tr>
	<?php endforeach; ?>
	
		<tr>
			<td>
				<?php if( ON == config_get( 'use_javascript' ) ): ?>
					<input id="select-all-checkbox" type="checkbox" name="all_notes" onclick="checkall('notes', this.form.all_notes.checked)" />
				<?php endif ?>
			</td>
			<td colspan="<?php echo sizeof($table['headings']) - 1 ?>">
				<?php if( ON == config_get( 'use_javascript' ) ): ?>
					<label class="small" for="select-all-checkbox"><?php echo lang_get( 'select_all' ) ?></label>
				<?php endif; ?>
				
				<select name="bug_note_action">
					<option value=""></option>
					<?php foreach(PokretBillingAPI::$features as $feature): ?>
						<?php if (!PokretBillingAPI::access_has_global_level_feature($feature)) continue; ?>
						
						<option value="<?php echo $feature?>:<?php echo YES ?>"><?php echo plugin_lang_get('name_' . $feature) ?> : <?php echo lang_get( 'yes' ) ?></option>
						<option value="<?php echo $feature?>:<?php echo NO ?>"><?php echo plugin_lang_get('name_' . $feature) ?> : <?php echo lang_get( 'no' ) ?></option>
					<?php endforeach; ?>
				</select>
				
				<input type="submit" class="button" value="<?php echo lang_get( 'ok' ); ?>" />
			</td>
		</tr>
	</table>
</form>

<?php endif; ?>

<?php
html_page_bottom1( __FILE__ );

