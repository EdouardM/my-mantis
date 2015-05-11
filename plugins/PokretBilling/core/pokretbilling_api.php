<?php
/**
 * Copyright © 2013 Andrej Pavlovic. All rights reserved.
 *
 * This code may not be used, copied, modified, sold, or extended without written
 * permission from Andrej Pavlovic (andrej.pavlovic@pokret.org).
 */

class PokretBillingAPI
{
	static $features = array(
		'billable',
		'reviewed',
		'billed',
	);
	
	static function access_has_global_level_feature($feature) {
		if (false === in_array($feature, self::$features)) {
			throw new Exception('Feature not supported: ' . $feature);
		}
		
		return plugin_config_get( $feature . '_enabled' )
			&& access_has_global_level(plugin_config_get( $feature . '_threshold' ));
	}
	
	static function access_has_global_level_any_feature() {
		foreach(self::$features as $feature) {
			if (self::access_has_global_level_feature($feature)) {
				return true;
			}
		}
		
		return false;
	}
	
	static function bugnote_get_multiplier($p_bugnote_id)
	{
		bugnote_clear_cache($p_bugnote_id);
		return bugnote_get_field($p_bugnote_id, 'pokret_billing_multiplier');
	}
	
	static function bugnote_get_time_tracking($p_bugnote_id)
	{
		bugnote_clear_cache($p_bugnote_id);
		return bugnote_get_field($p_bugnote_id, 'time_tracking');
	}
	
	static function bugnote_set_multiplier($p_bugnote_id, $p_bugnote_multiplier)
	{
		$c_bugnote_id = db_prepare_int( $p_bugnote_id );
		$t_bugnote_table = db_get_table( 'mantis_bugnote_table' );
	
		$query = "UPDATE $t_bugnote_table
					SET pokret_billing_multiplier = " . db_param() . "
					WHERE id=" . db_param();
		db_query_bound( $query, array( $p_bugnote_multiplier, $c_bugnote_id ) );
		
		return true;
	}
	
	static function bugnote_set_type($p_bugnote_id, $p_bugnote_type)
	{
		$c_bugnote_id = db_prepare_int( $p_bugnote_id );
		$t_bugnote_table = db_get_table( 'mantis_bugnote_table' );
	
		$query = "UPDATE $t_bugnote_table
					SET note_type = " . db_param() . "
					WHERE id=" . db_param();
		db_query_bound( $query, array( $p_bugnote_type, $c_bugnote_id ) );
		
		return true;
	}
	
	static function bugnote_get_adjustment($p_bugnote_id)
	{
		bugnote_clear_cache($p_bugnote_id);
		return bugnote_get_field($p_bugnote_id, 'pokret_billing_adjustment');
	}
	
	static function bugnote_set_adjustment($p_bugnote_id, $p_bugnote_adjustment)
	{
		$c_bugnote_adjustment = trim($p_bugnote_adjustment);
		
		$c_bugnote_adjustment_negative = (substr($c_bugnote_adjustment, 0, 1) == '-')
			? true
			: false;
		
		$c_bugnote_adjustment = trim($c_bugnote_adjustment, '-');
		
		$c_bugnote_adjustment = helper_duration_to_minutes( $c_bugnote_adjustment )
			* ( ($c_bugnote_adjustment_negative) ? -1 : 1);
		
		$c_bugnote_id = db_prepare_int( $p_bugnote_id );
		$t_bugnote_table = db_get_table( 'mantis_bugnote_table' );
		
		$query = "UPDATE $t_bugnote_table
					SET pokret_billing_adjustment = " . db_param() . "
					WHERE id=" . db_param();
		db_query_bound( $query, array( $c_bugnote_adjustment, $c_bugnote_id ) );
		
		return true;
	}
	
	static function bugnote_get_field_value($p_field_name, $p_bugnote_id)
	{
		bugnote_clear_cache($p_bugnote_id);
		return bugnote_get_field($p_bugnote_id, "pokret_billing_{$p_field_name}");
	}
	
	static function bugnote_set_field_value($p_field_name, $p_field_value, $p_bugnote_id)
	{
		if (!in_array($p_field_name, array(
			'billable',
			'billed',
			'reviewed'
		)))
		{
			trigger_error( 'Unsupported field value: ' . $p_field_name, ERROR );
		}
		$c_field_name = $p_field_name;
		
		$t_bugnote_table = db_get_table( 'mantis_bugnote_table' );
		
		$query = "UPDATE $t_bugnote_table
					SET `pokret_billing_{$c_field_name}` = " . db_param() . "
					WHERE id=" . db_param();
		db_query_bound( $query, array( $p_field_value, $p_bugnote_id ) );
		
		return true;
	}
	
	static function bug_get_billing_stats($p_bug_id)
	{
		$t_bugnote_table = db_get_table( 'mantis_bugnote_table' );
		
		$query = "SELECT
				bn.id as bugnote_id,
				SUM(bn.time_tracking) as time_actual,
				ROUND(
					 SUM(
					 	bn.time_tracking * bn.pokret_billing_multiplier + bn.pokret_billing_adjustment
					 )
				    ,0
				) as time_adjusted,
				ROUND(
					 SUM(
						IF(
						   bn.pokret_billing_billable = 1
						  ,bn.time_tracking * bn.pokret_billing_multiplier + bn.pokret_billing_adjustment
						  ,0
						)
					 )
				    ,0
				) as time_billable
			FROM
				$t_bugnote_table bn
			WHERE bn.bug_id = ". db_param() ."
			GROUP BY
				bn.bug_id";
		
		$result = db_query_bound($query, array(
			$p_bug_id,
		));
		
		$row = db_fetch_array($result);
		
		if ($row['time_adjusted'] < 0) {
			$row['time_adjusted'] = 0;
		}
		
		if ($row['time_billable'] < 0) {
			$row['time_billable'] = 0;
		}
		
		return $row;
	}
	
	# --------------------
	# Returns an array of bugnote stats
	# $p_from - Starting date (yyyy-mm-dd) inclusive, if blank, then ignored.
	# $p_to - Ending date (yyyy-mm-dd) inclusive, if blank, then ignored.
	static function bugnote_stats_get_project_array( $p_project_id, $p_user_id, $p_from, $p_to, $p_exclude_subprojects, $p_is_field_values )
	{
		$c_project_id = db_prepare_int( $p_project_id );
		
		$c_to = strtotime( $p_to ) + SECONDS_PER_DAY - 1;
		$c_from = strtotime( $p_from );
		
		if ( $c_to === false || $c_from === false )
		{
			error_parameters( array( $p_from, $p_to ) );
			trigger_error( ERROR_GENERIC, ERROR );
		}

		// MySQL
		$t_bug_table = db_get_table( 'mantis_bug_table' );
		$t_user_table = db_get_table( 'mantis_user_table' );
		$t_bugnote_table = db_get_table( 'mantis_bugnote_table' );
		$t_bugnote_text_table = db_get_table( 'mantis_bugnote_text_table' );
		$t_project_table = db_get_table( 'mantis_project_table' );
		$t_project_hierarchy_table = db_get_table( 'mantis_project_hierarchy_table' );

		if ( !is_blank( $c_from ) ) {
			$t_from_where = " AND bn.date_submitted >= $c_from";
		} else {
			$t_from_where = '';
		}

		if ( !is_blank( $c_to ) ) {
			$t_to_where = " AND bn.date_submitted <= $c_to";
		} else {
			$t_to_where = '';
		}
		
		// specify which projects to include - user must have access to them
		$c_project_ids = array();
		
		if ( ALL_PROJECTS != $c_project_id ) {
			if (!$p_exclude_subprojects)
			{
				// include subprojects
				$c_project_ids = current_user_get_all_accessible_subprojects($c_project_id);
			}
			
			// include selected project
			$c_project_ids[] = $c_project_id;
		} else {
			// get immediate projects under ALL_PROJECTS
			$c_project_ids = current_user_get_accessible_projects();
			
			if (!$p_exclude_subprojects)
			{
				// get subprojects of ALL_PROJECTS
				foreach($c_project_ids as $c_project_id) {
					$c_project_ids = array_merge($c_project_ids, current_user_get_all_accessible_subprojects($c_project_id));
				}
			}
		}

		if (sizeof($c_project_ids) < 1) {
			// user not allowed to access any project
			return array();
		}
		
		$t_project_where = " AND b.project_id IN (".implode(',', $c_project_ids).") ";

		if ( ALL_USERS != $p_user_id) {
			$t_user_where = " AND bn.reporter_id = $p_user_id ";
		} else {
			$t_user_where = '';
		}
		
		$t_other_fields_where = '';
		foreach(array('billable', 'reviewed', 'billed') as $field_name)
		{
			if (!isset($p_is_field_values[$field_name]))
				continue;
			
			if ($p_is_field_values[$field_name] === true)
			{
				$t_other_fields_where .= " AND bn.pokret_billing_{$field_name} = 1 ";
			}
			elseif ($p_is_field_values[$field_name] === false)
			{
				$t_other_fields_where .= " AND bn.pokret_billing_{$field_name} != 1 ";
			}
		}

		$t_results = array();

		$query = "SELECT
				u.realname,
				b.summary,
				bn.bug_id,
				b.project_id,
				p.name,
				bn.time_tracking,
				bn.id as bugnote_id,
				bn.pokret_billing_multiplier,
				bn.pokret_billing_adjustment,
				bn.pokret_billing_billable,
				bn.pokret_billing_reviewed,
				bn.pokret_billing_billed,
				bn.date_submitted,
				bt.note,
				ph.child_id,
				ph.parent_id,
				p2.name as parent_name
			FROM
				$t_user_table u,
				$t_bugnote_table bn,
				$t_bugnote_text_table bt,
				$t_bug_table b,
				$t_project_table p
			LEFT JOIN $t_project_hierarchy_table ph ON ph.child_id = p.id
			LEFT JOIN $t_project_table p2 ON ph.parent_id = p2.id
			WHERE u.id = bn.reporter_id AND
				(bn.time_tracking != 0 OR bn.pokret_billing_adjustment != 0) AND
				bn.bug_id = b.id AND
				bn.bugnote_text_id = bt.id AND
				p.id = b.project_id
				$t_project_where
				$t_from_where
				$t_to_where
				$t_user_where
				$t_other_fields_where
			ORDER BY
				bn.date_submitted ASC,
				bn.bug_id ASC";
		
		$result = db_query( $query );

		while ( $row = db_fetch_array( $result ) ) {
			$t_results[] = $row;
		}

		return $t_results;
	}
	
	static function bugnote_report_xlsx_output($table, $filename)
	{
		ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.config_get( 'plugin_path' ).'/PokretBilling/library/PHPExcel');
		
		/** PHPExcel */
		include 'PHPExcel.php';
		
		/** PHPExcel_Writer_Excel2007 */
		include 'PHPExcel/Writer/Excel2007.php';
		
		// Create new PHPExcel object
		$objPHPExcel = new PHPExcel();
		
		// Add some data
		$objPHPExcel->setActiveSheetIndex(0);
		$worksheet = $objPHPExcel->getActiveSheet();

		// Workaround for weird PHP behaviour
		// @see http://phpexcel.codeplex.com/discussions/250120
		ob_end_clean();
		
		// set headers
		header("Pragma: public");
	    header("Expires: 0");
	    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	    header("Cache-Control: public");
	    header("Content-Description: File Transfer");
		header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
		header('Content-Disposition: attachment; filename="'.$filename.'"');
	    header("Content-Transfer-Encoding: binary");
		
		$current_row = 1;
		
		// add table headings
		$i=0;
		foreach($table['headings'] as $heading)
		{
			$worksheet->setCellValueByColumnAndRow($i, $current_row, $heading);
			$worksheet->getStyleByColumnAndRow($i, $current_row)->getFont()->setBold(true);
			$i++;
		}
		++$current_row;
		
		// add rows
		foreach($table['rows'] as $row)
		{
			$csv_row = array();
			
			$i=0;
			foreach ($row['cells'] as $data)
			{
				$worksheet->setCellValueByColumnAndRow($i, $current_row, $data['value']);
				
				$cellStyle = $worksheet->getStyleByColumnAndRow($i, $current_row);
				$cellStyle->getAlignment()->setWrapText(true);
				
				if (!empty($data['style']['bold']))
				{
					$cellStyle->getFont()->setBold(true);
				}
				
				if (!empty($data['colspan']))
				{
					$colspan = $data['colspan'];
					
					if ('fill' == $colspan) {
						$colspan = sizeof($table['headings']) - sizeof($row['cells']) + 1;
					}
					
					for ($j = 1; $j < $colspan; $i++, $j++);
				}
				
				$i++;
			}
			
			++$current_row;
		}
		
		for($i=0;$i<sizeof($table['headings']);++$i)
		{
			$worksheet->getColumnDimensionByColumn($i)->setAutoSize(true);
		}
		
		// Save Excel 2007 file
		$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);

		// Workaround for weird PHP behaviour
		// @see http://phpexcel.codeplex.com/discussions/250120
		ob_end_clean();

		$tmp_file_path = tempnam(sys_get_temp_dir(), 'billing');
		$objWriter->save($tmp_file_path);
		readfile($tmp_file_path);
		unlink($tmp_file_path);

		die(0);
	}
}
