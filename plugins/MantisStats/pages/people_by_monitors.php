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
$mantis_monitor_table       = db_get_table( 'mantis_bug_monitor_table' );
$mantis_user_table          = db_get_table( 'mantis_user_table' );
$resolved_status_threshold  = config_get( 'bug_resolved_status_threshold' );
$status_enum_string         = lang_get( 'status_enum_string' );
$status_values              = MantisEnum::getValues( $status_enum_string );


// start and finish dates and times
$db_datetimes = array();

$db_datetimes['start']  = strtotime( cleanDates('date-from', $dateFrom, 'begOfTimes') . " 00:00:00" );
$db_datetimes['finish'] = strtotime( cleanDates('date-to', $dateTo) . " 23:59:59" );


// issues list into array for tables
$issues_fetch_from_db = array();

$query = "
        SELECT mut.id, count(*) AS the_count
        FROM $mantis_monitor_table mbmt
        LEFT JOIN $mantis_bug_table mbt ON mbmt.bug_id = mbt.id
        LEFT JOIN $mantis_user_table mut ON mbmt.user_id = mut.id
        WHERE $specific_where
        AND status < " . $resolved_status_threshold . "
        AND mbt.date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
        AND mbt.date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
        GROUP BY mut.id
        ";
$result = $db->GetAll( $query );

foreach ($result as $row) {
    $issues_fetch_from_db[$row['id']]['open'] = $row['the_count'];
}

$query = "
        SELECT mut.id, count(*) AS the_count
        FROM $mantis_monitor_table mbmt
        LEFT JOIN $mantis_bug_table mbt ON mbmt.bug_id = mbt.id
        LEFT JOIN $mantis_user_table mut ON mbmt.user_id = mut.id
        WHERE $specific_where
        AND status >= " . $resolved_status_threshold . "
        AND mbt.date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
        AND mbt.date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
        GROUP BY mut.id
        ";
$result = $db->GetAll( $query );

foreach ($result as $row) {
    $issues_fetch_from_db[$row['id']]['resolved'] = $row['the_count'];
}


// building tables
function tables_and_charts ( $output ) {
	global $status_values, $resolved_status_threshold, $issues_fetch_from_db;

    $user_names = user_names();

	$data_table_totals = array();
	$data_table_print = $chart_print = '';
	$grand_total = $i = 0;

	// table heading
	$data_table_print = "
        <table class='width100' cellspacing='1'>
            <tr>
                <td width='100%' class='form-title'>" . lang_get( 'plugin_MantisStats_name' ) . "</td>
	            <td class='heading'>" . lang_get( 'plugin_MantisStats_mon_open_issues' ) . "</td>
                <td class='heading'>" . lang_get( 'plugin_MantisStats_mon_res_issues' ) . "</td>
	            <td class='heading'>" . lang_get( 'plugin_MantisStats_mon_all_issues' ) . "</td>
            </tr>
    ";

	// table rows below heading
	foreach ($issues_fetch_from_db as $k1 => $v1) {

        if ( !isset( $v1['open'] ) )         { $issues_fetch_from_db[$k1]['open'] = 0; }
        if ( !isset( $v1['resolved'] ) )     { $issues_fetch_from_db[$k1]['resolved'] = 0; }

		$data_table_totals[$k1] = @$v1['open'] + @$v1['resolved'];
		$grand_total = $grand_total + $data_table_totals[$k1];
	}

    if( count( $data_table_totals ) > 0 ) {

        $out = pagination( count( $data_table_totals ), 1, plugin_page( 'people_by_monitors' ) );
        arsort( $data_table_totals );

        foreach ( $data_table_totals as $k2 => $v2 ) {

            $i++;

            if ( $i <= ($out['offset']-1)*$out['perpage'] ) { continue; }
            if ( $i > $out['offset']*$out['perpage'] ) { break; }

            $data_table_print .= "<tr " . helper_alternate_class() . ">";
            $data_table_print .= "<td>" . $user_names[$k2] . "</td>";
            $data_table_print .= "<td class='right'>" . number_format( $issues_fetch_from_db[$k2]['open'] ) . "</td>";
            $data_table_print .= "<td class='right'>" . number_format( $issues_fetch_from_db[$k2]['resolved'] ) . "</td>";
            $data_table_print .= "<td class='right'>" . number_format( $v2 ) . "</td>";
            $data_table_print .= "</tr>";
        }

        if ( $out['pagination'] ) {
            $data_table_print .= "<tr><td class='right' colspan='4'><ul id='pagesNav'>" . $out['pagination'] . "</td></tr>";
        }

        $data_table_print .= "<tr><td class='right' colspan='4'><strong>" . lang_get( 'plugin_MantisStats_grand_total' ) . " " . number_format( $grand_total ) . "</strong></td></tr>";
    }

    $data_table_print .= "</table>";


    // Chart...
    $i = 0;
    if( count( $data_table_totals ) > 0 ) {
        foreach ($data_table_totals as $k3 => $v3) {
            if ( $i >= MAX_LINES_IN_BAR_CHARTS ) { break; }

            $i++;
            $chart_print .= "<set label='" . htmlspecialchars( $user_names[$k3], ENT_QUOTES ) . "' value='" . $v3 . "' toolText='" . number_format( $v3 ) . " [" . htmlspecialchars( $user_names[$k3], ENT_QUOTES ) . "]'/>";
        }
    }

	if ($output == 'chart') { return $chart_print; }
    else                    { return $data_table_print; }
}

$chart             = tables_and_charts( "chart" );
$chart_height      = 50 + 30*substr_count($chart, "set label");

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
            <div id="title"><?php echo lang_get( 'plugin_MantisStats_monitors' ); ?></div>
            <div id="scope">&raquo; <?php echo lang_get( 'plugin_MantisStats_project' ); ?>: <?php echo project_get_name( $project_id ); ?></div><div id="sup"><?php if ( $project_id == ALL_PROJECTS ) { echo "<sup>&dagger;</sup>"; } ?></div>
        </div>

        <p class="clear" />

        <div id="filter">
            <strong><?php echo lang_get( 'plugin_MantisStats_timeframe' ); ?></strong>
            <p />
            <form method="get">
                <input type="hidden" name="page" value="MantisStats/people_by_monitors" />
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
        <strong><?php echo lang_get( 'plugin_MantisStats_top_monitors' ); ?></strong>
        <p />
        <?php echo tables_and_charts("data"); ?>


<?php if ( $render_print_js != "no_render" ) { ?>

        <p class="space30Before" />
        <strong><?php echo lang_get( 'plugin_MantisStats_top_mon_chart' ); ?></strong>

    	<div id="monitors_by_chart"><?php echo lang_get( 'plugin_MantisStats_top_monitors' ); ?></div>
	    <script type="text/javascript">
	    // <![CDATA[
        <?php echo $render_print_js; ?>
	    var myChart = new FusionCharts("<?php echo plugin_file('Bar2D.swf'); ?>", "myChartIdProjOp", "728", "<?php echo $chart_height; ?>", "0", "1");
	    myChart.setDataXML("<chart showvalues='0' stack100percent='0' canvasbgangle='0' canvasborderthickness='2' chartleftmargin='15' chartrightmargin='25' basefontsize='10' outcnvbasefontsize='11' bgcolor='FFFFFF' showcumulativeline='1' linecolor='D3AF1D' showplotborder='1' plotgradientcolor='' plotbordercolor='EFEFEF' showcanvasbg='1' showcanvasbase='1' canvasbgcolor='FFFFFF' canvasbgalpha='100' canvasbasecolor='D3DBCA' showalternatehgridcolor='0' showborder='1' canvasborderalpha='0' divlinealpha='0' showshadow='1' plotfillangle='45' plotfillratio='' plotborderdashed='0' plotborderdashlen='1' anchorradius='3' anchorbgcolor='FFFFFF' anchorborderthickness='3' linethickness='3'><?php echo $chart; ?></chart>");
	    myChart.render("monitors_by_chart");
	    // ]]>
	    </script>
	    <div id="unableDiv1"><?php echo lang_get( 'plugin_MantisStats_smt_missing' ); ?></div>

<?php } ?>

        <p class="space40Before" />
        <?php if ( $project_id == ALL_PROJECTS ) { echo "&dagger; " . lang_get( 'plugin_MantisStats_priv_proj_skip' ); } ?>
        <?php printf( lang_get( 'plugin_MantisStats_charts_maxdisp' ), MAX_LINES_IN_BAR_CHARTS ); ?>
        <?php if ( $final_runtime_value == 1 ) { printf( "<p class='graycolor'>" . lang_get( 'plugin_MantisStats_runtime_string' ) . "</p>", round(microtime(true) - $starttime, 5) ); } ?>
    </div>

    <div id="sidebar"><?php echo $sidebar; ?></div>
</div>

<div id="footer"><?php html_page_bottom();?></div>