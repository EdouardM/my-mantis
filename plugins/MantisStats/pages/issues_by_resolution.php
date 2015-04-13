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
$project_id                 = helper_get_current_project();
$specific_where             = helper_project_specific_where( $project_id );
$mantis_bug_table           = db_get_table( 'mantis_bug_table' );
$resolved_status_threshold  = config_get( 'bug_resolved_status_threshold' );
$status_enum_string         = lang_get( 'status_enum_string' );
$resolution_enum_string     = lang_get( 'resolution_enum_string' );
$status_values              = MantisEnum::getValues( $status_enum_string );


// start and finish dates and times
$db_datetimes = array();

$db_datetimes['start']  = strtotime( cleanDates('date-from', $dateFrom, 'begOfTimes') . " 00:00:00" );
$db_datetimes['finish'] = strtotime( cleanDates('date-to', $dateTo) . " 23:59:59" );


// issues counts grouped by resolution
$issues_fetch_from_db = array();

$query = "
        SELECT count(*) as the_count, resolution, status
        FROM $mantis_bug_table
        WHERE $specific_where
        AND date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
        AND date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
        GROUP BY resolution, status
        ";
$result = $db->GetAll( $query );

foreach ($result as $row) {
	$issues_fetch_from_db[$row['resolution']][$row['status']] = $row['the_count'];
}


// issues list into array for chart
$issues_fetch_from_db_chart = array();

$query = "
        SELECT count(*) as the_count, resolution
        FROM $mantis_bug_table
        WHERE $specific_where
        AND date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
        AND date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
        GROUP BY resolution
        ";
$result = $db->GetAll( $query );

foreach ($result as $row) {
    $issues_fetch_from_db_chart[$row['resolution']] = $row['the_count'];
}


// building tables
function data_table ($type) {
	global $status_values, $resolved_status_threshold, $status_enum_string, $resolution_enum_string, $issues_fetch_from_db;
	$table_rows = $table_rows_sum = $controls = array();
	$data_table_print = '';
	$controls['totals'] = 0;
	$controls['colspans'] = 1;

	// table heading - issue states [ open | resolved ]
	$data_table_print .= "<table class='width100' cellspacing='1'><tr><td width='100%' class='form-title'>" . lang_get( 'plugin_MantisStats_by_resol_long' ) . "</td>";
	foreach ($status_values as $key => $val) {
		if (($type == 'open' and $val >= $resolved_status_threshold) || ($type == 'resolved' and $val < $resolved_status_threshold)) {continue;}
		$data_table_print .= "<td class='heading'>" . MantisEnum::getLabel( $status_enum_string, $val ) . "</td>";
		$controls['colspans']++;
	}
	$data_table_print .= "<td class='heading'>" . lang_get( 'plugin_MantisStats_total' ) . "</td></tr>";

	// table rows below heading
	foreach ($issues_fetch_from_db as $key => $val) {

		$table_rows[$key] = "<tr><td>" . MantisEnum::getLabel($resolution_enum_string, $key) . "</td>";
		$table_rows_sum[$key] = 0;

		$inner_open_and_resolved = $issues_fetch_from_db[$key];

		foreach ($status_values as $k => $v) {
			if (($v < $resolved_status_threshold and $type == "open") || ($v >= $resolved_status_threshold and $type == "resolved")) {
				if (!array_key_exists($v, $inner_open_and_resolved)) {
					$table_rows[$key] .= "<td class='right'>0</td>";
				} else {
					$table_rows[$key]		.= "<td class='right'>" . number_format( $inner_open_and_resolved[$v] ) . "</td>";
					$table_rows_sum[$key] = $table_rows_sum[$key] + $inner_open_and_resolved[$v];
				}
			}
		}
		$table_rows[$key] .= "<td class='right'>" . number_format( $table_rows_sum[$key] ) . "</td></tr>";
		$controls['totals'] = $controls['totals'] + $table_rows_sum[$key];
	}

	// sorting by row_totals and concluding data tables...
	arsort($table_rows_sum);

	foreach ($table_rows_sum as $key => $val) {
		if ($val) {
			$data_table_print .= str_replace("<tr>", "<tr " . helper_alternate_class() . ">", $table_rows[$key]);
		}
	}

	// totals row
	$data_table_print .= "<tr><td class='right' colspan='" . $controls['colspans'] . "'><strong>" . lang_get( 'plugin_MantisStats_grand_total' ) . "</strong></td><td class='right'><strong>" . number_format( $controls['totals'] ) . "</strong></td></tr></table>";

	return $data_table_print;
}


// building chart
$chart_data = "";

foreach ($issues_fetch_from_db_chart as $key => $val) {
	$chart_data .= "<set value='" . $val . "' label='" . number_format( $val ) . " " . htmlspecialchars( MantisEnum::getLabel( $resolution_enum_string, $key ), ENT_QUOTES ) . "' />";
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
            <div id="title"><?php echo lang_get( 'plugin_MantisStats_by_resol_long' ); ?></div>
            <div id="scope">&raquo; <?php echo lang_get( 'plugin_MantisStats_project' ); ?>: <?php echo project_get_name( $project_id ); ?></div><div id="sup"><?php if ( $project_id == ALL_PROJECTS ) { echo "<sup>&dagger;</sup>"; } ?></div>
        </div>

        <p class="clear" />

        <div id="filter">
            <strong><?php echo lang_get( 'plugin_MantisStats_timeframe' ); ?></strong>
            <p />
            <form method="get">
                <input type="hidden" name="page" value="MantisStats/issues_by_resolution" />
                <?php echo form_security_field( 'date_picker' ) ?>
                <div>
                    <label class="ind">From</label>
                    <input type="text" name="date-from" id="from" value="<?php echo cleanDates('date-from', $dateFrom, 'begOfTimes'); ?>" />
                </div>
                <div>
                    <label class="ind">To</label>
                    <input type="text" name="date-to" id="to"  value="<?php echo cleanDates('date-to', $dateTo); ?>" />
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
        <strong><?php echo lang_get( 'plugin_MantisStats_by_resol_chart' ); ?></strong>

	    <div id="by_status_all"><?php echo lang_get( 'plugin_MantisStats_all_issues' ); ?></div>
	    <script type="text/javascript">
	    // <![CDATA[

        <?php echo $render_print_js; ?>

	    var myChart = new FusionCharts("<?php echo plugin_file('Doughnut3D.swf'); ?>", "myChartIdByStatusA", "560", "280", "0", "0");
	    myChart.setDataXML("<chart pieYScale='30' plotFillAlpha='80' pieInnerfaceAlpha='60' slicingDistance='35' startingAngle='190' showValues='0' showLabels='1' showLegend='1'><?php echo $chart_data; ?></chart>");
	    myChart.render("by_status_all");
	    // ]]>
	    </script>
	    <div id="unableDiv1"><?php echo lang_get( 'plugin_MantisStats_smt_missing' ); ?></div>

<?php } ?>

        <p class="space40Before" />
        <?php if ( $project_id == ALL_PROJECTS ) { echo "&dagger; " . lang_get( 'plugin_MantisStats_priv_proj_skip' ); } ?>
        <?php if ( $final_runtime_value == 1 ) { printf( "<p class='graycolor'>" . lang_get( 'plugin_MantisStats_runtime_string' ) . "</p>", round(microtime(true) - $starttime, 5) ); } ?>
    </div>

    <div id="sidebar"><?php echo $sidebar; ?></div>
</div>

<div id="footer"><?php html_page_bottom();?></div>