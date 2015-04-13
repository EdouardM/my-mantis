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

// This function to be removed when mantis 1.2.18 is released, see http://www.mantisbt.org/bugs/view.php?id=17073
function own_helper_project_specific_where( $p_project_id, $p_user_id = null ) {
    if( null === $p_user_id ) {
        $p_user_id = auth_get_current_user_id();
    }

    $t_project_ids = user_get_all_accessible_projects( $p_user_id, $p_project_id );

    if( 0 == count( $t_project_ids ) ) {
        $t_project_filter = ' 1<>1';
    } else if( 1 == count( $t_project_ids ) ) {
        $t_project_filter = ' project_id=' . reset( $t_project_ids );
    } else {
        $t_project_filter = ' project_id IN (' . join( ',', $t_project_ids ) . ')';
    }

    return $t_project_filter;
}


$project_id                 = ALL_PROJECTS;
$specific_where             = own_helper_project_specific_where( $project_id );
$mantis_bug_table           = db_get_table( 'mantis_bug_table' );
$mantis_project_table       = db_get_table( 'mantis_project_table' );
$resolved_status_threshold  = config_get( 'bug_resolved_status_threshold' );
$status_enum_string         = lang_get( 'status_enum_string' );
$status_enum_string         = lang_get( 'status_enum_string' );
$status_values              = MantisEnum::getValues( $status_enum_string );
$count_states               = count_states();
$project_names              = project_names();


// start and finish dates and times
$db_datetimes = array();

$db_datetimes['start']  = strtotime( cleanDates('date-from', $dateFrom, 'begOfTimes') . " 00:00:00" );
$db_datetimes['finish'] = strtotime( cleanDates('date-to', $dateTo) . " 23:59:59" );


// issues counts grouped by projects and states
$issues_fetch_from_db = array();

$query = "
        SELECT count(*) as the_count, status, project_id
        FROM $mantis_bug_table
        WHERE $specific_where
        AND date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
        AND date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
        GROUP BY project_id, status
        ";
$result = $db->GetAll( $query );

foreach ($result as $row) {
    $issues_fetch_from_db[$row['project_id']][$row['status']] = $row['the_count'];
}


// Function to make all the rest. TODO: to break this into smaller functions in the future.
function tables_and_charts ($type, $output) {
	global $project_names, $status_values, $resolved_status_threshold, $status_enum_string, $issues_fetch_from_db, $count_states;

    // needed arrays and vars
	$data_table = $data_table_totals = $project_id = $project_name = $total = array();
	$chart_data = '';
    $grand_total = 0;

	// data rows
    $i = 0;
	foreach ( $project_names as $key => $val ) {
        $i++;

        $data_table_totals[$i] = array('project_id' => $key, 'project_name' => strtolower($val), 'total' => 0);

        foreach ( $status_values as $k => $v ) {
            if ( ($v < $resolved_status_threshold and $type == "open") || ($v >= $resolved_status_threshold and $type == "resolved") ) {
                if ( isset($issues_fetch_from_db[$key][$v]) ) {
            		$data_table[$key][$v] = $issues_fetch_from_db[$key][$v];
                    $data_table_totals[$i]['total'] = $data_table_totals[$i]['total'] + $issues_fetch_from_db[$key][$v];
                } else {
                    $data_table[$key][$v] = 0;
                }
            }
        }
        $grand_total = $grand_total + $data_table_totals[$i]['total'];
    }

    // sorting by totals, then by project name
    foreach ($data_table_totals as $key => $row) {
        $project_id[$key]       = $row['project_id'];
        $project_name[$key]     = $row['project_name'];
        $total[$key]            = $row['total'];
    }

    array_multisort($total, SORT_DESC, $project_name, SORT_ASC, $data_table_totals);

    if ( $type == 'open' ) {
        $out = pagination(count($project_names), 1, plugin_page( 'issues_by_projects' ));
    } elseif ( $type == 'resolved' ) {
        $out = pagination(count($project_names), 2, plugin_page( 'issues_by_projects' ));
    }

    // making data table (HTML output): start
    $data_table_print = "
    <table class='width100' cellspacing='1'>
        <tr>
            <td width='100%' class='form-title'>" . lang_get( 'plugin_MantisStats_project_name' ) . "</td>";

    foreach ( $status_values as $key => $val ) {
        if (( $type == 'open' and $val >= $resolved_status_threshold ) || ( $type == 'resolved' and $val < $resolved_status_threshold )) {continue;}
        $data_table_print .= "
            <td class='heading'>" . MantisEnum::getLabel($status_enum_string, $val) . "</td>";
    }

    $data_table_print .= "
            <td class='heading'>" . lang_get( 'plugin_MantisStats_total' ) . "</td>
        </tr>";

    $i = 0;
    foreach ($data_table_totals as $key => $val) {
        $i++;



        if ( $i <= ($out['offset']-1)*$out['perpage'] ) { continue; }
        if ( $i > $out['offset']*$out['perpage'] ) { break; }

		$data_table_print .= "
        <tr " . helper_alternate_class() . ">
            <td>" . $project_names[$val['project_id']] . "</td>";

        foreach ( $status_values as $k => $v ) {
            if (($type == 'open' and $v >= $resolved_status_threshold) || ($type == 'resolved' and $v < $resolved_status_threshold)) {continue;}
            $data_table_print .= "
            <td class='right'>" . number_format( $data_table[$val['project_id']][$v] ) . "</td>";
        }
        $data_table_print .= "
            <td class='right'>" . number_format( $val['total'] ) . "</td>
        </tr>";
	}

    $colspan = $count_states[$type] + 2;

    $data_table_print .= "
        <tr><td colspan='" . $colspan . "'><ul id='pagesNav'>";

    if ($out['pagination']) {
        $data_table_print .= $out['pagination'];
    }

    $data_table_print .= "</ul></td></tr>
    ";

    $data_table_print .= "
        <tr>
            <td class='right' colspan='" . $colspan . "'><strong>" . lang_get( 'plugin_MantisStats_grand_total' ) . " " . number_format($grand_total) . "</strong></td>
        </tr>
    </table>
    ";
    // making data table (HTML output): end


    // making charts, excluding empty projects
    $i = 0;
    foreach ($data_table_totals as $key => $val) {
        if ( $i >= MAX_LINES_IN_BAR_CHARTS ) { break; }
        if ( $val['total'] > 0 ) {
            $i++;
            $chart_data .= "<set label='" . htmlspecialchars( $project_names[$val['project_id']], ENT_QUOTES ) . "' value='" . $val['total'] . "' toolText='" . number_format( $val['total'] ) . " [" . htmlspecialchars( $project_names[$val['project_id']], ENT_QUOTES ) . "]'/>";
        }
    }


    // returns
	if ($output == 'chart') { return $chart_data; }
    else                    { return $data_table_print; }

}


// calculating charts height
$chart_open             = tables_and_charts("open", "chart");
$chart_height_open      = 50 + 30*substr_count($chart_open, "set label");
$chart_resolved         = tables_and_charts("resolved", "chart");
$chart_height_resolved  = 50 + 30*substr_count($chart_resolved, "set label");

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
            <div id="title"><?php echo lang_get( 'plugin_MantisStats_by_project_long' ); ?></div>
            <div id="scope">&raquo; <?php echo lang_get( 'plugin_MantisStats_project' ); ?>: <?php echo project_get_name( $project_id ); ?></div><div id="sup"><?php if ( $project_id == ALL_PROJECTS ) { echo "<sup>&dagger;</sup>"; } ?></div>
        </div>

        <p class="clear" />

        <div id="filter">
            <strong><?php echo lang_get( 'plugin_MantisStats_timeframe' ); ?></strong>
            <p />
            <form method="get">
                <input type="hidden" name="page" value="MantisStats/issues_by_projects" />
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
        <?php echo tables_and_charts("open", "table"); ?>

        <p class="space30Before" />
        <strong><?php echo lang_get( 'plugin_MantisStats_resolved_issues' ); ?></strong>
        <p />
        <?php echo tables_and_charts("resolved", "table"); ?>

<?php if ( $render_print_js != "no_render" ) { ?>

        <p class="space30Before" />
        <strong><?php echo lang_get( 'plugin_MantisStats_open_iss_chrt' ); ?></strong>

        <div id="issues_by_projects_open"><?php echo lang_get( 'plugin_MantisStats_open_issues' ); ?></div>
        <script type="text/javascript">
        // <![CDATA[
        <?php echo $render_print_js; ?>
	    var myChart = new FusionCharts("<?php echo plugin_file('Bar2D.swf'); ?>", "myChartIdProjOp", "728", "<?php echo $chart_height_open; ?>", "0", "1");
        myChart.setDataXML("<chart showvalues='0' stack100percent='0' canvasbgangle='0' canvasborderthickness='2' chartleftmargin='15' chartrightmargin='25' basefontsize='10' outcnvbasefontsize='11' bgcolor='FFFFFF' showcumulativeline='1' linecolor='D3AF1D' showplotborder='1' plotgradientcolor='' plotbordercolor='EFEFEF' showcanvasbg='1' showcanvasbase='1' canvasbgcolor='FFFFFF' canvasbgalpha='100' canvasbasecolor='D3DBCA' showalternatehgridcolor='0' showborder='1' canvasborderalpha='0' divlinealpha='0' showshadow='1' plotfillangle='45' plotfillratio='' plotborderdashed='0' plotborderdashlen='1' anchorradius='3' anchorbgcolor='FFFFFF' anchorborderthickness='3' linethickness='3'><?php echo $chart_open; ?></chart>");
	    myChart.render("issues_by_projects_open");
	    // ]]>
	    </script>
	    <div id="unableDiv1"><?php echo lang_get( 'plugin_MantisStats_smt_missing' ); ?></div>

        <p class="space30Before" />
        <strong><?php echo lang_get( 'plugin_MantisStats_resolved_iss_chrt' ); ?></strong>

	    <div id="project_resolved_chart"><?php echo lang_get( 'plugin_MantisStats_resolved_issues' ); ?></div>
	    <script type="text/javascript">
	    // <![CDATA[
        <?php echo $render_print_js; ?>
	    var myChart = new FusionCharts("<?php echo plugin_file('Bar2D.swf'); ?>", "myChartIdProjRe", "728", "<?php echo $chart_height_resolved; ?>", "0", "1");
	    myChart.setDataXML("<chart showvalues='0' stack100percent='0' canvasbgangle='0' canvasborderthickness='2' chartleftmargin='15' chartrightmargin='25' basefontsize='10' outcnvbasefontsize='11' bgcolor='FFFFFF' showcumulativeline='1' linecolor='D3AF1D' showplotborder='1' plotgradientcolor='' plotbordercolor='EFEFEF' showcanvasbg='1' showcanvasbase='1' canvasbgcolor='FFFFFF' canvasbgalpha='100' canvasbasecolor='D3DBCA' showalternatehgridcolor='0' showborder='1' canvasborderalpha='0' divlinealpha='0' showshadow='1' plotfillangle='45' plotfillratio='' plotborderdashed='0' plotborderdashlen='1' anchorradius='3' anchorbgcolor='FFFFFF' anchorborderthickness='3' linethickness='3'><?php echo $chart_resolved; ?></chart>");
	    myChart.render("project_resolved_chart");
	    // ]]>
	    </script>
	    <div id="unableDiv2"><?php echo lang_get( 'plugin_MantisStats_smt_missing' ); ?></div>

<?php } ?>

        <p class="space40Before" />
        <?php if ( $project_id == ALL_PROJECTS ) { echo "&dagger; " . lang_get( 'plugin_MantisStats_priv_proj_skip' ); } ?>
        <?php printf( lang_get( 'plugin_MantisStats_charts_maxdisp' ), MAX_LINES_IN_BAR_CHARTS ); ?>
        <?php if ( $final_runtime_value == 1 ) { printf( "<p class='graycolor'>" . lang_get( 'plugin_MantisStats_runtime_string' ) . "</p>", round(microtime(true) - $starttime, 5) ); } ?>
    </div>

    <div id="sidebar"><?php echo $sidebar; ?></div>
</div>

<div id="footer"><?php html_page_bottom();?></div>