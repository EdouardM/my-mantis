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
$resolved_status_threshold  = config_get( 'bug_resolved_status_threshold' );
$private_bug_threshold      = config_get( 'private_bug_threshold' );
$status_enum_string         = lang_get( 'status_enum_string' );


// start and finish dates and times
$db_datetimes = array();

$db_datetimes['start']  = strtotime( cleanDates('date-from', $dateFrom, 'begOfTimes') . " 00:00:00" );
$db_datetimes['finish'] = strtotime( cleanDates('date-to', $dateTo) . " 23:59:59" );


// issues with at least one monitor are here, sorted by number of monitors in issue
$issues_fetch_from_db = array();

$query = "
        SELECT mbt.id, count(*) AS the_count, mbt.summary, mbt.status
        FROM $mantis_monitor_table mbmt
        LEFT JOIN $mantis_bug_table mbt ON mbmt.bug_id = mbt.id
        WHERE $specific_where
        AND mbt.date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
        AND mbt.date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
        GROUP BY mbt.id
        ORDER BY the_count DESC
        ";
$result = $db->GetAll( $query );

foreach ($result as $row) {
    $issues_fetch_from_db[$row['id']]['the_count']  = $row['the_count'];
    $issues_fetch_from_db[$row['id']]['summary']    = $row['summary'];
    $issues_fetch_from_db[$row['id']]['status']     = $row['status'];
}


// number of all issues by being open or resolved
$query = "
	    SELECT
        SUM(CASE WHEN status < " . $resolved_status_threshold . " THEN 1 ELSE 0 END) as count_open,
        SUM(CASE WHEN status >= " . $resolved_status_threshold . " THEN 1 ELSE 0 END) as count_resolved
	    FROM $mantis_bug_table
	    WHERE $specific_where
        AND date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
        AND date_submitted <= " . $db->qstr( $db_datetimes['finish'] )
        ;
$result = $db->GetAll( $query );
$row = $result[0];

$open_number_of_issues = $row['count_open'];
$resolved_number_of_issues = $row['count_resolved'];


// summary table and prer. for top-list
$open_top_monitored = $open_with_monitors = $open_without_monitors = $open_monitors_sum = $open_average_per_issue = 0;
$resolved_top_monitored = $resolved_with_monitors = $resolved_without_monitors = $resolved_monitors_sum = $resolved_average_per_issue = 0;

$summaries = $counts = array();
$i = 0;

foreach ($issues_fetch_from_db as $key => $val) {
    if ( $val['status'] < $resolved_status_threshold  ) {
        if ( $open_top_monitored == 0 ) { $open_top_monitored = $val['the_count']; }
        $open_monitors_sum = $open_monitors_sum + $val['the_count'];
        $open_with_monitors++;
        $summaries['open'][$key] = $val['summary'];
        $counts['open'][$key] = $val['the_count'];
    } else {
        if ( $resolved_top_monitored == 0 ) { $resolved_top_monitored = $val['the_count']; }
        $resolved_monitors_sum = $resolved_monitors_sum + $val['the_count'];
        $resolved_with_monitors++;
        $summaries['resolved'][$key] = $val['summary'];
        $counts['resolved'][$key] = $val['the_count'];
    }
}

if ( $open_number_of_issues > 0 ) { $open_average_per_issue = round($open_monitors_sum/$open_number_of_issues, 2); }
$open_without_monitors = $open_number_of_issues - $open_with_monitors;

if ( $resolved_number_of_issues > 0 ) { $resolved_average_per_issue = round($resolved_monitors_sum/$resolved_number_of_issues, 2); }
$resolved_without_monitors = $resolved_number_of_issues - $resolved_with_monitors;


$summary_table_print = "

<table class='width100' cellspacing='1'>
<tr>
<td width='100%' class='form-title'>" . lang_get( 'plugin_MantisStats_monitoring_stats' ) . "</td>
<td class='heading'>" . lang_get( 'plugin_MantisStats_in_open_issues' ) . "</td>
<td class='heading'>" . lang_get( 'plugin_MantisStats_in_resolved_iss' ) . "</td>
<td class='heading'>" . lang_get( 'plugin_MantisStats_in_all_issues' ) . "</td>
</tr>

<tr " . helper_alternate_class() . ">
<td>" . lang_get( 'plugin_MantisStats_mostmon_in_single' ) . "</td>
<td class='right'>" . $open_top_monitored . "</td>
<td class='right'>" . $resolved_top_monitored . "</td>
<td class='right'>" . max( $open_top_monitored, $open_top_monitored ) . "</td>
</tr>

<tr " . helper_alternate_class() . ">
<td>" . lang_get( 'plugin_MantisStats_average_monitors' ) . "</td>
<td class='right'>" . $open_average_per_issue . "</td>
<td class='right'>" . $resolved_average_per_issue . "</td>
<td class='right'>";

$all_issues_count = $open_number_of_issues + $resolved_number_of_issues;

if ( $all_issues_count != 0 ) {
	$summary_table_print .= round( ($open_monitors_sum + $resolved_monitors_sum)/$all_issues_count, 2);
} else {
    $summary_table_print .= 0;
}

$summary_table_print .= "</td>
</tr>

<tr " . helper_alternate_class() . ">
<td>" . lang_get( 'plugin_MantisStats_with_no_monitors' ) . "</td>
<td class='right'>" . $open_without_monitors . "</td>
<td class='right'>" . $resolved_without_monitors . "</td>
<td class='right'>";

$all_without_monitors = $open_without_monitors + $resolved_without_monitors;
$summary_table_print .= number_format( $all_without_monitors );

$summary_table_print .= "</td>
</tr>
</table>

";


function toplist ($type) {
    global $private_bug_threshold, $summaries, $counts;

    $i = 0;
    $toplist_table_print = '';

    if ( $type == 'open' ) { $offset_switch = 1; }
    if ( $type == 'resolved' ) { $offset_switch = 2; }

    if ( isset( $summaries[$type] ) ) {

        $out = pagination( count( $summaries[$type] ), $offset_switch, plugin_page( 'issues_by_monitors' ) );

        foreach ( $summaries[$type] as $key => $val ) {

            // if private and access level is not enough then skip
            if(( VS_PRIVATE == bug_get_field( $key, 'view_state' ) ) && ( false == access_has_bug_level( $private_bug_threshold, $key ) ) ) { continue; }

            $i++;

            if ( $i <= ($out['offset']-1)*$out['perpage'] ) { continue; }
            if ( $i > $out['offset']*$out['perpage'] ) { break; }

            $toplist_table_print .= "
            <tr " . helper_alternate_class() . ">
            <td>" . string_get_bug_view_link( $key ) . " - " . $val . "</td>
            <td class='right'>" . $counts[$type][$key] . "</td>
            </tr>";
        }
    }

    if ( isset( $out['pagination'] ) ) {
        $toplist_table_print .= "<tr><td class='right' colspan='2'><ul id='pagesNav'>" . $out['pagination'] . "</td></tr>";
    }

    return $toplist_table_print;
}

$all_with_monitors = $all_issues_count - $all_without_monitors;
$chart_data = '';

if (!( $all_with_monitors == 0 and $all_without_monitors == 0 )) {
    $chart_data = "<set value='" . $all_with_monitors . "' label='" . number_format( $all_with_monitors ) . " " . htmlspecialchars( lang_get( 'plugin_MantisStats_with_monitors' ), ENT_QUOTES ) . "' />";
    $chart_data .= "<set value='" . $all_without_monitors . "' label='" . number_format( $all_without_monitors ) . " " . htmlspecialchars( lang_get( 'plugin_MantisStats_without_monitors' ), ENT_QUOTES ) . "' />";
}

$top_list_header = "
    <table class='width100' cellspacing='1'>
    <tr>
    <td width='100%' class='form-title'>" . lang_get( 'plugin_MantisStats_issue_summary' ) . "</td>
    <td class='heading'>" . lang_get( 'plugin_MantisStats_no_of_monitors' ) . "</td>
    </tr>
";

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
            <div id="title"><?php echo lang_get( 'plugin_MantisStats_monitoring_stats' ); ?></div>
            <div id="scope">&raquo; <?php echo lang_get( 'plugin_MantisStats_project' ); ?>: <?php echo project_get_name( $project_id ); ?></div><div id="sup"><?php if ( $project_id == ALL_PROJECTS ) { echo "<sup>&dagger;</sup>"; } ?></div>
        </div>

        <p class="clear" />

        <div id="filter">
            <strong><?php echo lang_get( 'plugin_MantisStats_timeframe' ); ?></strong>
            <p />
            <form method="get">
                <input type="hidden" name="page" value="MantisStats/issues_by_monitors" />
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
        <strong><?php echo lang_get( 'plugin_MantisStats_summary_table' ); ?></strong>
        <p />
        <?php echo $summary_table_print; ?>

        <p class="space30Before" />
        <strong><?php echo lang_get( 'plugin_MantisStats_top_monitored_op' ); ?><sup>&Dagger;</sup></strong>
        <p />
        <?php echo $top_list_header . toplist('open') . "</table>"; ?>

        <p class="space30Before" />
        <strong><?php echo lang_get( 'plugin_MantisStats_top_monitored_res' ); ?><sup>&Dagger;</sup></strong>
        <p />
        <?php echo $top_list_header . toplist('resolved') . "</table>"; ?>
        <p />

<?php if ( $render_print_js != "no_render" ) { ?>

        <p class="space30Before" />
	    <strong><?php echo lang_get( 'plugin_MantisStats_all_with_without' ); ?></strong>

	    <div id="by_status_all"><?php echo lang_get( 'plugin_MantisStats_all_with_without' ); ?></div>
	    <script type="text/javascript">
	    // <![CDATA[
        <?php echo $render_print_js; ?>
	    var myChart = new FusionCharts("<?php echo plugin_file('Doughnut3D.swf'); ?>", "myChartIdByStatusA", "560", "280", "0", "0");
        myChart.setDataXML("<chart pieYScale='30' plotFillAlpha='80' pieInnerfaceAlpha='60' slicingDistance='35' startingAngle='190' showValues='0' showLabels='1' showLegend='1'><?php echo $chart_data; ?></chart>");
        myChart.setDataXML("<chart pieYScale='30' plotFillAlpha='80' pieInnerfaceAlpha='60' slicingDistance='35' startingAngle='190' showValues='0' showLabels='1' showLegend='1'><?php echo $chart_data; ?></chart>");
	    myChart.render("by_status_all");
	    // ]]>
	    </script>
	    <div id="unableDiv1"><?php echo lang_get( 'plugin_MantisStats_smt_missing' ); ?></div>

<?php } ?>

        <p class="space40Before" />
        <?php if ( $project_id == ALL_PROJECTS ) { echo "&dagger; " . lang_get( 'plugin_MantisStats_priv_proj_skip' ); } ?>
        <br />
        &Dagger; <?php echo lang_get( 'plugin_MantisStats_priv_iss_skip' ); ?>
        <?php if ( $final_runtime_value == 1 ) { printf( "<p class='graycolor'>" . lang_get( 'plugin_MantisStats_runtime_string' ) . "</p>", round(microtime(true) - $starttime, 5) ); } ?>
    </div>

    <div id="sidebar"><?php echo $sidebar; ?></div>
</div>

<div id="footer"><?php html_page_bottom();?></div>