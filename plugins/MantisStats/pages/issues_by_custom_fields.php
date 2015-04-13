<?php

# MantisStats - a statistics plugin for MantisBT
#
# Copyright (c) MantisStats.Org
#
# MantsStats is free for use, but is not open-source software. A copy of
# License was delivered to you during the software download. See LICENSE file.
#
# MantisStats is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See License for more
# details.
#
# https://www.mantisstats.org


html_page_top( lang_get( 'summary_link' ) );
echo "<br />";
if ( plugin_config_get( 'menu_location' ) == 'EVENT_MENU_SUMMARY' ) { print_summary_menu( 'summary_page.php' ); }

require_once 'common_includes.php';


// config and initialization vars
$project_id                         = helper_get_current_project();
$specific_where                     = helper_project_specific_where( $project_id );
$mantis_bug_table                   = db_get_table( 'mantis_bug_table' );
$mantis_custom_field_string_table   = db_get_table( 'mantis_custom_field_string_table' );
$resolved_status_threshold          = config_get( 'bug_resolved_status_threshold' );
$status_enum_string                 = lang_get( 'status_enum_string' );
$status_values                      = MantisEnum::getValues($status_enum_string);
$count_states                       = count_states();
$custom_field_names                 = custom_field_names();


// Custom fields list - the ones matching threshold vs. user permission lvl.
$cleanedCustomFieldIds = array();

$customFieldsIds = custom_field_get_linked_ids( $project_id );
foreach ( $customFieldsIds as $key => $val ) {
    if ( custom_field_has_read_access_by_project_id( $val, $project_id, $t_user_id ) ) {
        $cleanedCustomFieldIds[$val] = $custom_field_names[$val];
    }
}

asort( $cleanedCustomFieldIds );

if ( sizeof( $cleanedCustomFieldIds ) > 0 ) {
    $selectedCustomField = key( $cleanedCustomFieldIds );
} else {
    $selectedCustomField = -1;
}

if ( isset( $_GET['customField'] ) and !empty( $_GET['customField'] ) ) {
    foreach ( $cleanedCustomFieldIds as $k => $v) {
        if ( $k == strip_tags( $_GET['customField'] ) ) {
            $selectedCustomField = $k;
            $_SESSION['customField'] = $k;
            break;
        }
    }
} elseif ( isset( $_SESSION['customField'] ) and !empty( $_SESSION['customField'] ) ) {
    foreach ( $cleanedCustomFieldIds as $k => $v) {
        if ( $k == strip_tags( $_SESSION['customField'] ) ) {
            $selectedCustomField = $k;
            break;
        }
    }
}


$customFieldsDropDown = "<select name='customField' id='customField'>";

foreach ( $cleanedCustomFieldIds as $key => $val ) {
    $selected = "";
    if ( $selectedCustomField == $key ) { $selected = " selected "; }
    $customFieldsDropDown .= "<option value='" . $key . "'" . $selected . ">" . $val . "</option>";
}

$customFieldsDropDown .= "</select>";


// start and finish dates and times
$db_datetimes = array();

$db_datetimes['start']  = strtotime( cleanDates('date-from', $dateFrom, 'begOfTimes') . " 00:00:00" );
$db_datetimes['finish'] = strtotime( cleanDates('date-to', $dateTo) . " 23:59:59" );


// issues counts grouped by custom fields
$issues_fetch_from_db = array();

$query = "
        SELECT mcfst.value, count(*) as the_count, mbt.status
        FROM $mantis_bug_table mbt
        LEFT JOIN
            (
                SELECT * FROM $mantis_custom_field_string_table WHERE field_id = $selectedCustomField
            ) mcfst ON mbt.id = mcfst.bug_id
        WHERE $specific_where
        AND mbt.date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
        AND mbt.date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
        GROUP BY mcfst.value, mbt.status
        ";
$result = $db->GetAll( $query );

foreach ( $result as $row ) {
   if ( $row['value'] == NULL or $row['value'] == '' ) {
       $tmp = lang_get( 'plugin_MantisStats_novalue' );
   } else {
       if ( custom_field_type( $selectedCustomField ) == 8 ) { // date field, to do what?
           $tmp = date( "Y-m-d", $row['value'] );
       } else {
           $tmp = $row['value'];
       }
   }
    if ( isset( $issues_fetch_from_db[$tmp][$row['status']] ) ) {
        $issues_fetch_from_db[$tmp][$row['status']] = $issues_fetch_from_db[$tmp][$row['status']] + $row['the_count'];
    } else {
        $issues_fetch_from_db[$tmp][$row['status']] = $row['the_count'];
    }
}


// issues list into array for chart
$issues_fetch_from_db_chart = array();

$query = "
        SELECT mcfst.value, count(*) as the_count
        FROM $mantis_bug_table mbt
        LEFT JOIN
            (
                SELECT * FROM $mantis_custom_field_string_table WHERE field_id = $selectedCustomField
            ) mcfst ON mbt.id = mcfst.bug_id
        WHERE $specific_where
        AND mbt.date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
        AND mbt.date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
        GROUP BY mcfst.value
        ";
$result = $db->GetAll( $query );

foreach ( $result as $row ) {
   if ( $row['value'] == NULL or $row['value'] == '' ) {
       $tmp = lang_get( 'plugin_MantisStats_novalue' );
   } else {
       if ( custom_field_type( $selectedCustomField ) == 8 ) { // date field, to do what?
           $tmp = date( "Y-m-d", $row['value'] );
       } else {
           $tmp = $row['value'];
       }
   }
   $issues_fetch_from_db_chart[$tmp] = $row['the_count'];
}

if ( $selectedCustomField == -1 ) {
    $tmp_title = lang_get( 'plugin_MantisStats_by_custom_fields_long' );
    $tmp_title_chart = lang_get( 'plugin_MantisStats_by_custom_fields_chrt' );
} else {
    $tmp_title = sprintf( lang_get( 'plugin_MantisStats_by_etc' ), $custom_field_names[$selectedCustomField] );
    $tmp_title_chart = sprintf( lang_get( 'plugin_MantisStats_by_etc_chart' ), $custom_field_names[$selectedCustomField] );
}


// building tables
function data_table ( $type ) {
	global $status_values, $resolved_status_threshold, $tmp_title, $status_enum_string, $issues_fetch_from_db, $count_states;

    // needed arrays and vars

	$table_rows = $table_rows_sum = array();
	$data_table_print = '';
	$grand_total = 0;

	// table heading - issue states [ open | resolved ]
	$data_table_print .= "<table class='width100' cellspacing='1'><tr><td width='100%' class='form-title'>" . $tmp_title . "</td>";
	foreach ( $status_values as $key => $val ) {
		if ( ( $type == 'open' and $val >= $resolved_status_threshold ) || ( $type == 'resolved' and $val < $resolved_status_threshold ) ) { continue; }
		$data_table_print .= "<td class='heading'>" . MantisEnum::getLabel( $status_enum_string, $val ) . "</td>";
	}
	$data_table_print .= "<td class='heading'>" . lang_get( 'plugin_MantisStats_total' ) . "</td></tr>";

	// table rows below heading
	foreach ( $issues_fetch_from_db as $key => $val ) {

		$table_rows[$key] = "<tr><td>" . $key . "</td>";
		$table_rows_sum[$key] = 0;

		$inner_open_and_resolved = $issues_fetch_from_db[$key];

		foreach ( $status_values as $k => $v ) {
			if ( ( $v < $resolved_status_threshold and $type == "open" ) || ( $v >= $resolved_status_threshold and $type == "resolved" ) ) {
				if ( !array_key_exists( $v, $inner_open_and_resolved ) ) {
					$table_rows[$key] .= "<td class='right'>0</td>";
				} else {
					$table_rows[$key] .= "<td class='right'>" . number_format( $inner_open_and_resolved[$v] ) . "</td>";
					$table_rows_sum[$key] = $table_rows_sum[$key] + $inner_open_and_resolved[$v];
				}
			}
		}
		$table_rows[$key] .= "<td class='right'>" . number_format( $table_rows_sum[$key] ) . "</td></tr>";
		$grand_total = $grand_total + $table_rows_sum[$key];
	}

    $count_non_null_rows = 0;
    foreach ( $table_rows_sum as $key => $val ) {
        if ( $val != 0 ) { $count_non_null_rows++; }
    }

	// sorting by row_totals and concluding data tables...
	arsort( $table_rows_sum );

    if ( $type == 'open' ) {
        $out = pagination( $count_non_null_rows, 1, plugin_page( 'issues_by_custom_fields' ) );
    } elseif ( $type == 'resolved' ) {
        $out = pagination( $count_non_null_rows, 2, plugin_page( 'issues_by_custom_fields' ) );
    }

    $i = 0;
	foreach ( $table_rows_sum as $key => $val ) {

		if ( !$val ) { break; }
        $i++;

		$data_table_print .= str_replace( "<tr>", "<tr " . helper_alternate_class() . ">", $table_rows[$key] );

        if ( $i <= ($out['offset']-1)*$out['perpage'] ) { continue; }
        if ( $i > $out['offset']*$out['perpage'] ) { break; }

    }

    $colspan = $count_states[$type] + 2;

    $data_table_print .= "
        <tr><td colspan='" . $colspan . "'><ul id='pagesNav'>";

    if ( $out['pagination'] ) {
        $data_table_print .= $out['pagination'];
    }

    $data_table_print .= "</ul></td></tr>
    ";

	// totals row
    $data_table_print .= "
        <tr>
            <td class='right' colspan='" . $colspan . "'><strong>" . lang_get( 'plugin_MantisStats_grand_total' ) . " " . number_format( $grand_total ) . "</strong></td>
        </tr>
    </table>
    ";

	return $data_table_print;
}


// building chart
$chart_data = "";
$i = 0;

foreach ( $issues_fetch_from_db_chart as $key => $val ) {
    if ( $i >= MAX_LINES_IN_BAR_CHARTS ) { break; }
    $i++;
	$chart_data .= "<set value='" . $val . "' label='" . number_format( $val ) . " " . $key . "' />";
}

?>


<script>
    $(function() {
        $( "#from" ).datepicker({
            firstDay: 1,
            changeMonth: true,
            changeYear: true,
            maxDate: new Date(),
            showButtonPanel: true,
            dateFormat: "yy-mm-dd",
            defaultDate: -14
        });
    });
</script>
<script>
    $(function() {
        $( "#to" ).datepicker({
            firstDay: 1,
            changeMonth: true,
            changeYear: true,
            maxDate: new Date(),
            showButtonPanel: true,
            dateFormat: "yy-mm-dd"
        });
    });
</script>


<div id="wrapper">

    <div id="databox">
        <div id="titleIcon">
            <a href="<?php echo plugin_page( $current_page_clean ); ?>"><img src="<?php echo plugin_file('images/ReportsIcon.png'); ?>" width="36" height="39" align="left" /></a>
        </div>
        <div id="titleText">
            <div id="title"><?php echo lang_get( 'plugin_MantisStats_by_custom_fields_long' ); ?></div>
            <div id="scope">&raquo; <?php echo lang_get( 'plugin_MantisStats_project' ); ?>: <?php echo project_get_name( $project_id ); ?></div><div id="sup"><?php if ( $project_id == ALL_PROJECTS ) { echo "<sup>&dagger;</sup>"; } ?></div>
        </div>

        <p class="clear" />

        <div id="filter">
            <strong><?php echo lang_get( 'plugin_MantisStats_timeframe' ); ?></strong>
            <p />
            <form method="get">
                <input type="hidden" name="page" value="MantisStats/issues_by_custom_fields" />
                <?php echo form_security_field( 'date_picker' ) ?>
                <div>
                    <label class="ind">From</label>
                    <input type="text" name="date-from" id="from" value="<?php echo cleanDates('date-from', $dateFrom, 'begOfTimes'); ?>" />
                </div>
                <div>
                    <label class="ind">To</label>
                    <input type="text" name="date-to" id="to"  value="<?php echo cleanDates('date-to', $dateTo); ?>" />
                </div>
                <p />
                <div>
                    <label class="ind"><?php echo lang_get( 'plugin_MantisStats_by_custom_field' ); ?></label>
                    <?php echo $customFieldsDropDown; ?>
                </div>

                <input type="submit" id="displaysubmit" value=<?php echo lang_get( 'plugin_MantisStats_display' ); ?> class="button" />
            </form>
        </div>

        <p class="space30Before" />
        <strong><?php echo lang_get( 'plugin_MantisStats_open_issues' ); ?></strong>
        <p />
        <?php echo data_table("open"); ?>

        <p class="space30Before" />
        <strong><?php echo lang_get( 'plugin_MantisStats_resolved_issues' ); ?></strong>
        <p />
        <?php echo data_table("resolved"); ?>

        <p />

<?php if ( $render_print_js != "no_render" ) { ?>

        <p class="space30Before" />
	    <strong><?php echo $tmp_title_chart; ?></strong>

	    <div id="by_custom_fields_all"><?php echo lang_get( 'plugin_MantisStats_all_issues' ); ?></div>
	    <script type="text/javascript">
	    // <![CDATA[

        <?php echo $render_print_js; ?>

	    var myChart = new FusionCharts("<?php echo plugin_file('Doughnut3D.swf'); ?>", "myChartIdByStatusA", "560", "280", "0", "0");
	    myChart.setDataXML("<chart pieYScale='30' plotFillAlpha='80' pieInnerfaceAlpha='60' slicingDistance='35' startingAngle='190' showValues='0' showLabels='1' showLegend='1'><?php echo $chart_data; ?></chart>");
	    myChart.render("by_custom_fields_all");
	    // ]]>
	    </script>
	    <div id="unableDiv1"><?php echo lang_get( 'plugin_MantisStats_smt_missing' ); ?></div>

<?php } ?>

        <p class="space40Before" />
        <?php if ( $project_id == ALL_PROJECTS ) { echo "&dagger; " . lang_get( 'plugin_MantisStats_priv_proj_skip' ); } ?>
        <?php if ( $final_runtime_value == 1 ) { printf( "<p class='graycolor'>" . lang_get( 'plugin_MantisStats_runtime_string' ) . "</p>", round( microtime(true) - $starttime, 5 ) ); } ?>
    </div>

    <div id="sidebar"><?php echo $sidebar; ?></div>
</div>

<div id="footer"><?php html_page_bottom();?></div>