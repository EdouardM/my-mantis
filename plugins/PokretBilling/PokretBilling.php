<?php
/**
 * Copyright © 2013 Andrej Pavlovic. All rights reserved.
 *
 * This code may not be used, copied, modified, sold, or extended without written
 * permission from Andrej Pavlovic (andrej.pavlovic@pokret.org).
 */

require_once( config_get( 'class_path' ) . 'MantisPlugin.class.php' );

class PokretBillingPlugin extends MantisPlugin
{
	function register()
	{
		$this->name = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );
		$this->page = 'config';

		$this->version = '1.2.0';
		$this->requires = array(
			'MantisCore' => '1.2.0',
		);

		$this->author = 'Andrej Pavlovic';
		$this->contact = 'andrej.pavlovic@pokret.org';
		$this->url = 'http://www.pokret.org/';
	}
	
	function init()
	{
		require_once( 'core/pokretbilling_api.php' );
	}
	
	function config()
	{
		return array(
			'time_tracking_enabled' => ON,
			'time_tracking_threshold' => MANAGER,
			
			'report_enabled' => ON,
			'report_threshold' => MANAGER,
			'report_tab_title' => plugin_lang_get( 'title' ),
			
			'multiplier_enabled' => ON,
			'multiplier_threshold' => MANAGER,
			
			'adjustment_enabled' => ON,
			'adjustment_threshold' => MANAGER,
			
			'billable_enabled' => ON,
			'billable_threshold' => MANAGER,
			
			'billed_enabled' => ON,
			'billed_threshold' => MANAGER,
			
			'reviewed_enabled' => ON,
			'reviewed_threshold' => MANAGER,
		);
	}
	
	function hooks()
	{
		return array(
			'EVENT_MENU_MAIN' => 'menu_main',
			'EVENT_VIEW_BUGNOTE' => 'bugnote_view',
			'EVENT_BUGNOTE_EDIT_FORM' => 'bugnote_edit_form',
			'EVENT_BUGNOTE_EDIT' => 'bugnote_edit',
			'EVENT_BUGNOTE_ADD_FORM' => 'bugnote_add_form',
			'EVENT_BUGNOTE_ADD' => 'bugnote_edit',
			'EVENT_VIEW_BUGNOTES_END' => 'bugnotes_view_end',
		);
	}
	
	function menu_main()
	{
		$t_links = array();

		if ( plugin_config_get( 'report_enabled' )
			&& access_has_global_level(plugin_config_get( 'report_threshold' )) )
		{
			$t_page = plugin_page( 'report' );
			$t_lang = plugin_config_get( 'report_tab_title' );
			$t_links[] = "<a href=\"$t_page\">$t_lang</a>";
		}

		return $t_links;
	}
	
	function bugnotes_view_end($p_event_name, $p_bug_id)
	{
		$stats = PokretBillingAPI::bug_get_billing_stats($p_bug_id);
		
		$display_stats = array();
		
		if (plugin_config_get('time_tracking_enabled') && access_has_bug_level( plugin_config_get( 'time_tracking_threshold' ), $p_bug_id )):
			$display_stats[] = array(
				'label' => plugin_lang_get('label_total_time'),
				'value' => db_minutes_to_hhmm($stats['time_actual']),
			);
		endif;
		
		if ((plugin_config_get("multiplier_enabled") && access_has_bug_level( plugin_config_get( "multiplier_threshold" ), $p_bug_id ))
			|| (plugin_config_get("adjustment_enabled") && access_has_bug_level( plugin_config_get( "adjustment_threshold" ), $p_bug_id ))):
			$display_stats[] = array(
				'label' => plugin_lang_get('label_total_adjusted_time'),
				'value' => db_minutes_to_hhmm($stats['time_adjusted']),
			);
		endif;
		
		if (plugin_config_get("billable_enabled") && access_has_bug_level( plugin_config_get( "billable_threshold" ), $p_bug_id )):
			$display_stats[] = array(
				'label' => plugin_lang_get('label_total_billable_time'),
				'value' => db_minutes_to_hhmm($stats['time_billable']),
			);
		endif;
		
		if (sizeof($display_stats) > 0): ?>
			<tr>
				<td colspan="2">
					<?php foreach ($display_stats as $stat): ?>
						<strong><?php echo htmlspecialchars($stat['label'])?></strong>
						<?php echo htmlspecialchars($stat['value']) ?>
					<?php endforeach; ?>
				</td>
			</tr>
		<?php endif;
	}
	
	function bugnote_view($p_event_name, $p_bug_id, $p_bugnote_id, $p_bugnote_is_private)
	{
		$bugnotes = bugnote_get_all_bugnotes( $p_bug_id );
		foreach($bugnotes as $bugnote)
		{
			if ($bugnote->id == $p_bugnote_id)
			{
				$t_bugnote = $bugnote;
				break;
			}
		}
		
		if ($t_bugnote->note_type != TIME_TRACKING)
			return;
		
		$display_stats = array();
		
		if (plugin_config_get('time_tracking_enabled') && access_has_bug_level( plugin_config_get( 'time_tracking_threshold' ), $p_bug_id )):
			$display_stats[] = array(
				'label' => plugin_lang_get('label_time'),
				'value' => db_minutes_to_hhmm(PokretBillingAPI::bugnote_get_time_tracking($p_bugnote_id)),
			);
		endif;
		
		if (plugin_config_get('multiplier_enabled') && access_has_bug_level( plugin_config_get( 'multiplier_threshold' ), $p_bug_id )):
			$display_stats[] = array(
				'label' => plugin_lang_get('label_multiplier'),
				'value' => PokretBillingAPI::bugnote_get_multiplier($p_bugnote_id),
			);
		endif;
		
		if (plugin_config_get('adjustment_enabled') && access_has_bug_level( plugin_config_get( 'adjustment_threshold' ), $p_bug_id )):
			$t_adjustment = PokretBillingAPI::bugnote_get_adjustment($p_bugnote_id);
			
			$t_adjustment_negative = ($t_adjustment < 0)
				? true
				: false;
			
			$t_adjustment = db_minutes_to_hhmm( abs($t_adjustment) );
			
			$display_stats[] = array(
				'label' => plugin_lang_get('label_adjustment'),
				'value' => (($t_adjustment_negative) ? '-' : '') . $t_adjustment,
			);
		endif;
		
		foreach(PokretBillingAPI::$features as $field_name):
			if (plugin_config_get("{$field_name}_enabled") && access_has_bug_level( plugin_config_get( "{$field_name}_threshold" ), $p_bug_id )):
				$field_value = PokretBillingAPI::bugnote_get_field_value($field_name, $p_bugnote_id);
				$display_stats[] = array(
					'label' => plugin_lang_get('label_' . $field_name),
					'value' => ($field_value) ? lang_get( 'yes' ) : lang_get( 'no' ),
				);
			endif;
		endforeach;
		
		if (sizeof($display_stats) > 0):
		
			if ( VS_PRIVATE == $t_bugnote->view_state ) {
				$t_bugnote_css		= 'bugnote-private';
				$t_bugnote_note_css	= 'bugnote-note-private';
			} else {
				$t_bugnote_css		= 'bugnote-public';
				$t_bugnote_note_css	= 'bugnote-note-public';
			}
			?>
			<tr class="bugnote">
				<td class="<?php echo $t_bugnote_css ?>">&nbsp;</td>
				<td class="<?php echo $t_bugnote_note_css ?>">
					
					<?php foreach ($display_stats as $stat): ?>
						<strong><?php echo htmlspecialchars($stat['label'])?></strong>
						<?php echo htmlspecialchars($stat['value']) ?>
					<?php endforeach; ?>
				</td>
			</tr>
		<?php endif;
	}
	
	function bugnote_add_form($p_event_name, $p_bug_id)
	{
		if ( !(config_get('time_tracking_enabled') && access_has_bug_level( config_get( 'time_tracking_edit_threshold' ), $p_bug_id ))
			&& (plugin_config_get('time_tracking_enabled') && access_has_bug_level( plugin_config_get( 'time_tracking_threshold' ), $p_bug_id ))): ?>
			
			<tr <?php echo helper_alternate_class() ?>>
				<td class="category">
					<?php echo lang_get( 'time_tracking' ) ?> (<?php echo plugin_lang_get('hhmm') ?>)
				</td>
				<td>
					<?php if ( config_get( 'time_tracking_stopwatch' ) && config_get( 'use_javascript' ) ) { ?>
					<script type="text/javascript">
						var time_tracking_stopwatch_lang_start = "<?php echo lang_get( 'time_tracking_stopwatch_start' ) ?>";
						var time_tracking_stopwatch_lang_stop = "<?php echo lang_get( 'time_tracking_stopwatch_stop' ) ?>";
					</script>
					<?php
						html_javascript_link( 'time_tracking_stopwatch.js' );
					?>
					<input type="text" name="time_tracking" size="5" value="00:00" />
					<input type="button" name="time_tracking_ssbutton" value="<?php echo lang_get( 'time_tracking_stopwatch_start' ) ?>" onclick="time_tracking_swstartstop()" />
					<input type="button" name="time_tracking_reset" value="<?php echo lang_get( 'time_tracking_stopwatch_reset' ) ?>" onclick="time_tracking_swreset()" />
					<?php } else { ?>
					<input type="text" name="time_tracking" size="5" value="00:00" />
					<?php } ?>
				</td>
			</tr>
		<?php endif;
			
			
		if (plugin_config_get('multiplier_enabled')
			&& access_has_bug_level( plugin_config_get( 'multiplier_threshold' ), $p_bug_id ))
		{
			?>
				<tr <?php echo helper_alternate_class() ?>>
					<td class="category">
						<?php echo plugin_lang_get( 'multiplier' ) ?>
					</td>
					<td>
						<input type="text" name="pokret_billing_multiplier" size="5" value="1" />
					</td>
				</tr>
			<?php
		}
		
		if (plugin_config_get('adjustment_enabled')
			&& access_has_bug_level( plugin_config_get( 'adjustment_threshold' ), $p_bug_id ))
		{
			?>
				<tr <?php echo helper_alternate_class() ?>>
					<td class="category">
						<?php echo plugin_lang_get( 'adjustment') ?>
					</td>
					<td>
						<input type="text" name="pokret_billing_adjustment" size="5" value="00:00" />
					</td>
				</tr>
			<?php
		}
		
		foreach(PokretBillingAPI::$features as $field_name)
		{
			if (plugin_config_get("{$field_name}_enabled")
				&& access_has_bug_level( plugin_config_get( "{$field_name}_threshold" ), $p_bug_id ))
			{
				?>
					<tr <?php echo helper_alternate_class() ?>>
						<td class="category">
							<?php echo plugin_lang_get( $field_name ) ?>
						</td>
						<td>
							<input type="checkbox" name="pokret_billing_<?php echo $field_name ?>" />
						</td>
					</tr>
				<?php
			}
		}
	}
	
	function bugnote_edit_form($p_event_name, $p_bug_id, $p_bugnote_id)
	{
		$t_bugnote_type = bugnote_get_field($p_bugnote_id, 'note_type');
		
		if ($t_bugnote_type != TIME_TRACKING)
			return;
		
		if ( !(config_get('time_tracking_enabled') && access_has_bug_level( config_get( 'time_tracking_edit_threshold' ), $p_bug_id ))
			&& (plugin_config_get('time_tracking_enabled') && access_has_bug_level( plugin_config_get( 'time_tracking_threshold' ), $p_bug_id ))):
			
			$t_time_tracking = bugnote_get_field( $p_bugnote_id, "time_tracking" );
			$t_time_tracking = db_minutes_to_hhmm( $t_time_tracking );
			
			?>
			
			<tr class="row-2">
				<td class="center" colspan="2">
					<b><?php echo lang_get( 'time_tracking') ?> (<?php echo plugin_lang_get('hhmm') ?>)</b><br />
					<input type="text" name="time_tracking" size="5" value="<?php echo $t_time_tracking ?>" />
				</td>
			</tr>
		<?php endif;
		
		if (plugin_config_get('multiplier_enabled')
			&& access_has_bug_level( plugin_config_get( 'multiplier_threshold' ), $p_bug_id ))
		{
			$t_multiplier = PokretBillingAPI::bugnote_get_multiplier($p_bugnote_id);
			?>
				<tr class="row-2">
					<td class="center" colspan="2">
						<b><?php echo plugin_lang_get( 'multiplier') ?></b><br />
						<input type="text" name="pokret_billing_multiplier" size="5" value="<?php echo htmlspecialchars($t_multiplier) ?>" />
					</td>
				</tr>
			<?php
		}
		
		if (plugin_config_get('adjustment_enabled')
			&& access_has_bug_level( plugin_config_get( 'adjustment_threshold' ), $p_bug_id ))
		{
			$t_adjustment = PokretBillingAPI::bugnote_get_adjustment($p_bugnote_id);
			
			$t_adjustment_negative = ($t_adjustment < 0)
				? true
				: false;
			
			$t_adjustment = db_minutes_to_hhmm( abs($t_adjustment) );
			?>
				<tr class="row-2">
					<td class="center" colspan="2">
						<b><?php echo plugin_lang_get( 'adjustment') ?></b><br />
						<input type="text" name="pokret_billing_adjustment" size="5" value="<?php echo htmlspecialchars(($t_adjustment_negative) ? '-' : '') ?><?php echo htmlspecialchars($t_adjustment) ?>" />
					</td>
				</tr>
			<?php
		}
		
		foreach(PokretBillingAPI::$features as $field_name)
		{
			if (plugin_config_get("{$field_name}_enabled")
				&& access_has_bug_level( plugin_config_get( "{$field_name}_threshold" ), $p_bug_id ))
			{
				$t_field_value = PokretBillingAPI::bugnote_get_field_value($field_name, $p_bugnote_id);
				?>
					<tr class="row-2">
						<td class="center" colspan="2">
							<b><?php echo plugin_lang_get( $field_name ) ?></b><br />
							<input type="checkbox" name="pokret_billing_<?php echo $field_name ?>" <?php echo ($t_field_value) ? 'checked="checked"' : ''?> />
						</td>
					</tr>
				<?php
			}
		}
	}
	
	function bugnote_edit($p_event_name, $p_bug_id, $p_bugnote_id)
	{
		$f_time_tracking = gpc_get_string( 'time_tracking', '0:00' );
		$c_time_tracking = helper_duration_to_minutes( $f_time_tracking );
		
		// Ensure we set the correct type
		if ($p_event_name == 'EVENT_BUGNOTE_ADD')
		{
			$t_bugnote_type = bugnote_get_field($p_bugnote_id, 'note_type');
			
			switch($t_bugnote_type)
			{
				case BUGNOTE:
					// update bugnote type
					PokretBillingAPI::bugnote_set_type($p_bugnote_id, TIME_TRACKING);
					break;
					
				case TIME_TRACKING:
					break;
				
				case REMINDER:
				default:
					// This is some special type of note, so don't add time info
					return;
					break;
			}
		}
		
		$t_bugnote_type = bugnote_get_field($p_bugnote_id, 'note_type');
		
		if ($t_bugnote_type != TIME_TRACKING)
			return;
		
		if ( !(config_get('time_tracking_enabled') && access_has_bug_level( config_get( 'time_tracking_edit_threshold' ), $p_bug_id ))
			&& (plugin_config_get('time_tracking_enabled') && access_has_bug_level( plugin_config_get( 'time_tracking_threshold' ), $p_bug_id )))
		{
			bugnote_set_time_tracking($p_bugnote_id, $f_time_tracking);
		}
		
		if (plugin_config_get('multiplier_enabled')
			&& access_has_bug_level( plugin_config_get( 'multiplier_threshold' ), $p_bug_id ))
		{
			$f_multiplier = (float) gpc_get_string('pokret_billing_multiplier', 1);
			PokretBillingAPI::bugnote_set_multiplier($p_bugnote_id, $f_multiplier);
		}
		
		if (plugin_config_get('adjustment_enabled')
			&& access_has_bug_level( plugin_config_get( 'adjustment_threshold' ), $p_bug_id ))
		{
			$f_adjustment = gpc_get_string('pokret_billing_adjustment', 0);
			PokretBillingAPI::bugnote_set_adjustment($p_bugnote_id, $f_adjustment);
		}
		foreach(PokretBillingAPI::$features as $field_name)
		{
			if (plugin_config_get("{$field_name}_enabled")
				&& access_has_bug_level( plugin_config_get( "{$field_name}_threshold" ), $p_bug_id ))
			{
				$f_field_value = gpc_get_bool("pokret_billing_{$field_name}", 0);
				PokretBillingAPI::bugnote_set_field_value($field_name, $f_field_value, $p_bugnote_id);
			}
		}
	}
	
	function install()
	{
		$sqlSchema = array(
			// Columns
			array(
				'AddColumnSQL',
				array(
					db_get_table('mantis_bugnote_table'),
					'pokret_billing_multiplier  N(8.4)  UNSIGNED NOTNULL DEFAULT 1',
				),
			),
			array(
				'AddColumnSQL',
				array(
					db_get_table('mantis_bugnote_table'),
					'pokret_billing_adjustment  I NOTNULL DEFAULT 0',
				),
			),
			array(
				'AddColumnSQL',
				array(
					db_get_table('mantis_bugnote_table'),
					'pokret_billing_billable    L NOTNULL DEFAULT 0',
				),
			),
			array(
				'AddColumnSQL',
				array(
					db_get_table('mantis_bugnote_table'),
					'pokret_billing_billed    L NOTNULL DEFAULT 0',
				),
			),
			array(
				'AddColumnSQL',
				array(
					db_get_table('mantis_bugnote_table'),
					'pokret_billing_reviewed    L NOTNULL DEFAULT 0',
				),
			),
			
			// Indexes
			array(
				'CreateIndexSQL',
				array(
					'idx_pokret_billing_billable',
					db_get_table('mantis_bugnote_table'),
					'pokret_billing_billable',
				),
			),
			array(
				'CreateIndexSQL',
				array(
					'idx_pokret_billing_billed',
					db_get_table('mantis_bugnote_table'),
					'pokret_billing_billed',
				),
			),
			array(
				'CreateIndexSQL',
				array(
					'idx_pokret_billing_reviewed',
					db_get_table('mantis_bugnote_table'),
					'pokret_billing_reviewed',
				),
			),
		);
		
		$this->executeSqlSchema($sqlSchema);
		
		return true;
	}
	
	function uninstall()
	{
		$sqlSchema = array(
			array(
				'DropColumnSQL',
				array(
					db_get_table('mantis_bugnote_table'),
					'pokret_billing_multiplier',
				),
			),
			array(
				'DropColumnSQL',
				array(
					db_get_table('mantis_bugnote_table'),
					'pokret_billing_adjustment',
				),
			),
			
			array(
				'DropIndexSQL',
				array(
					'idx_pokret_billing_billable',
					db_get_table('mantis_bugnote_table'),
				),
			),
			array(
				'DropColumnSQL',
				array(
					db_get_table('mantis_bugnote_table'),
					'pokret_billing_billable',
				),
			),
			
			array(
				'DropIndexSQL',
				array(
					'idx_pokret_billing_billed',
					db_get_table('mantis_bugnote_table'),
				),
			),
			array(
				'DropColumnSQL',
				array(
					db_get_table('mantis_bugnote_table'),
					'pokret_billing_billed',
				),
			),
			
			array(
				'DropIndexSQL',
				array(
					'idx_pokret_billing_reviewed',
					db_get_table('mantis_bugnote_table'),
				),
			),
			array(
				'DropColumnSQL',
				array(
					db_get_table('mantis_bugnote_table'),
					'pokret_billing_reviewed',
				),
			),
		);
		
		$this->executeSqlSchema($sqlSchema);
		
		return true;
	}
	
	private function executeSqlSchema($sqlSchema)
	{
		global $g_db;
		/* @var $g_db ADOConnection */
		
		$t_dict = NewDataDictionary( $g_db );
		/* @var $t_dict ADODB_DataDict */
		
		foreach($sqlSchema as $sqlSchemaItem)
		{
			$sqlArray = call_user_func_array( array( $t_dict, $sqlSchemaItem[0] ), $sqlSchemaItem[1] );
			$status = $t_dict->ExecuteSQLArray($sqlArray);
			
			if ($status != 2)
			{
				plugin_error($g_db->ErrorMsg());
			}
		}
	}
}