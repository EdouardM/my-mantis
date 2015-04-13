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
$mantis_category_table      = db_get_table( 'mantis_category_table' );
$resolved_status_threshold  = config_get( 'bug_resolved_status_threshold' );
$status_enum_string         = lang_get( 'status_enum_string' );
$status_values              = MantisEnum::getValues( $status_enum_string );
$count_states               = count_states();
$category_names             = category_names();


// start and finish dates and times
$db_datetimes = array();

$db_datetimes['start']  = strtotime( cleanDates('date-from', $dateFrom, 'begOfTimes') . " 00:00:00" );
$db_datetimes['finish'] = strtotime( cleanDates('date-to', $dateTo) . " 23:59:59" );


// issues counts grouped by categories and states
$issues_fetch_from_db = array();

$query = "
        SELECT count(*) as the_count, status, category_id
        FROM $mantis_bug_table
        WHERE $specific_where
        AND date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
        AND date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
        GROUP BY category_id, status
        ";
$result = $db->GetAll( $query );

foreach ($result as $row) {
    $issues_fetch_from_db[$row['category_id']][$row['status']] = $row['the_count'];
}


// Function to make all the rest.
function tables_and_charts ($type, $output) {
	global $status_values, $category_names, $resolved_status_threshold, $status_enum_string, $issues_fetch_from_db, $count_states;

    // needed arrays and vars
	$data_table = $data_table_totals = $category_id = $category_name = $total = array();
	$chart_data = '';
    $grand_total = 0;

	// data rows
    $i = 0;
	foreach ( $category_names as $key => $val ) {
        $i++;

        $data_table_totals[$i] = array('category_id' => $key, 'category_name' => strtolower($val), 'total' => 0);

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

    // sorting by totals, then by category name
    $count_non_null_rows = 0;
    foreach ( $data_table_totals as $key => $row ) {
        $category_id[$key]      = $row['category_id'];
        $category_name[$key]    = $row['category_name'];
        $total[$key]            = $row['total'];
        if ( $row['total'] != 0 ) { $count_non_null_rows++; }
    }

    array_multisort($total, SORT_DESC, $category_name, SORT_ASC, $data_table_totals);

    if ( $type == 'open' ) {
        $out = pagination( $count_non_null_rows, 1, plugin_page( 'issues_by_category' ) );
    } elseif ( $type == 'resolved' ) {
        $out = pagination( $count_non_null_rows, 2, plugin_page( 'issues_by_category' ) );
    }


    // making data table (HTML output): start
    $data_table_print = "
    <table class='width100' cellspacing='1'>
        <tr>
            <td width='100%' class='form-title'>" . lang_get( 'plugin_MantisStats_by_category_long' ) . "</td>";

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

        if ( !$val['total'] ) { break; }
        $i++;


        if ( $i <= ($out['offset']-1)*$out['perpage'] ) { continue; }
        if ( $i > $out['offset']*$out['perpage'] ) { break; }

		$data_table_print .= "
        <tr " . helper_alternate_class() . ">
            <td>" . $category_names[$val['category_id']] . "</td>";

        foreach ( $status_values as $k => $v ) {
            if (($type == 'open' and $v >= $resolved_status_threshold) || ($type == 'resolved' and $v < $resolved_status_threshold)) {continue;}
            $data_table_print .= "
            <td class='right'>" . number_format( $data_table[$val['category_id']][$v] ) . "</td>";
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
            <td class='right' colspan='" . $colspan . "'><strong>" . lang_get( 'plugin_MantisStats_grand_total' ) . " " . number_format( $grand_total ) . "</strong></td>
        </tr>
    </table>
    ";
    // making data table (HTML output): end


    // making charts, excluding empty categories
    $i = 0;
    foreach ($data_table_totals as $key => $val) {
        if ( $i >= MAX_LINES_IN_BAR_CHARTS ) { break; }
        if ( $val['total'] > 0 ) {
            $i++;
            $chart_data .= "<set label='" . htmlspecialchars( $category_names[$val['category_id']], ENT_QUOTES ) . "' value='" . $val['total'] . "'/>";
        }
    }


    // returns
	if ($output == 'chart') { return $chart_data; }
    else                    { return $data_table_print; }

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
            <div id="title"><?php echo lang_get( 'plugin_MantisStats_by_category_long' ); ?></div>
            <div id="scope">&raquo; <?php echo lang_get( 'plugin_MantisStats_project' ); ?>: <?php echo project_get_name( $project_id ); ?></div><div id="sup"><?php if ( $project_id == ALL_PROJECTS ) { echo "<sup>&dagger;</sup>"; } ?></div>
        </div>

        <p class="clear" />

        <div id="filter">
            <strong><?php echo lang_get( 'plugin_MantisStats_timeframe' ); ?></strong>
            <p />
            <form method="get">
                <input type="hidden" name="page" value="MantisStats/issues_by_category" />
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
        <strong><?php echo lang_get( 'plugin_MantisStats_by_category_open' ); ?></strong>

        <div id="issues_by_category_open"><?php echo lang_get( 'plugin_MantisStats_open_issues' ); ?></div>
        <script type="text/javascript">
        // <![CDATA[
        <?php echo $render_print_js; ?>
	    var myChart = new FusionCharts("<?php echo plugin_file('Pareto2D.swf'); ?>", "myChartIdCatOpen", "728", "350", "0", "1");
        myChart.setDataXML("<chart showvalues='0' stack100percent='0' canvasbgangle='0' canvasborderthickness='2' chartleftmargin='15' chartrightmargin='5'  basefontsize='10' outcnvbasefontsize='11' bgcolor='FFFFFF' showcumulativeline='1' linecolor='D3AF1D' showplotborder='1' plotgradientcolor='' plotbordercolor='EFEFEF' showcanvasbg='1' showcanvasbase='1' canvasbgcolor='FFFFFF' canvasbgalpha='100' canvasbasecolor='D3DBCA' showalternatehgridcolor='0' showborder='1' canvasborderalpha='0' divlinealpha='0' showshadow='1' plotfillangle='45' plotfillratio='' plotborderdashed='0' plotborderdashlen='1' anchorradius='3' anchorbgcolor='FFFFFF' anchorborderthickness='3' linethickness='3'><?php echo tables_and_charts("open", "chart"); ?></chart>");
	    myChart.render("issues_by_category_open");
	    // ]]>
	    </script>
	    <div id="unableDiv1"><?php echo lang_get( 'plugin_MantisStats_smt_missing' ); ?></div>

        <p class="space30Before" />
        <strong><?php echo lang_get( 'plugin_MantisStats_by_category_res' ); ?></strong>

	    <div id="issues_by_category_resolved"><?php echo lang_get( 'plugin_MantisStats_resolved_issues' ); ?></div>
	    <script type="text/javascript">
	    // <![CDATA[
        <?php echo $render_print_js; ?>
	    var myChart = new FusionCharts("<?php echo plugin_file('Pareto2D.swf'); ?>", "myChartIdCatResolved", "728", "350", "0", "1");
	    myChart.setDataXML("<chart showvalues='0' stack100percent='0' canvasbgangle='0' canvasborderthickness='2' chartleftmargin='15' chartrightmargin='25' basefontsize='10' outcnvbasefontsize='11' bgcolor='FFFFFF' showcumulativeline='1' linecolor='D3AF1D' showplotborder='1' plotgradientcolor='' plotbordercolor='EFEFEF' showcanvasbg='1' showcanvasbase='1' canvasbgcolor='FFFFFF' canvasbgalpha='100' canvasbasecolor='D3DBCA' showalternatehgridcolor='0' showborder='1' canvasborderalpha='0' divlinealpha='0' showshadow='1' plotfillangle='45' plotfillratio='' plotborderdashed='0' plotborderdashlen='1' anchorradius='3' anchorbgcolor='FFFFFF' anchorborderthickness='3' linethickness='3'><?php echo tables_and_charts("resolved", "chart"); ?></chart>");
	    myChart.render("issues_by_category_resolved");
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