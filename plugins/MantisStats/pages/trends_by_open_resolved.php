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
$mantis_bug_history_table   = db_get_table( 'mantis_bug_history_table' );
$resolved_status_threshold  = config_get( 'bug_resolved_status_threshold' );

$totals = array();

// 'resolved' options
$resolved_options   = array(1,2);
$resolved_option    = $resolved_options[0];

if ( isset( $_GET['resolution_date_options'] ) and !empty( $_GET['resolution_date_options'] ) ) {
    foreach ( $resolved_options as $k => $v) {
        if ( $v == strip_tags( $_GET['resolution_date_options'] ) ) {
            $resolved_option = $v;
            $_SESSION['resolved_option'] = $v;
            break;
        }
    }
} elseif ( isset( $_SESSION['resolved_option'] ) and !empty( $_SESSION['resolved_option'] ) ) {
    foreach ( $resolved_options as $k => $v) {
        if ( $v == strip_tags( $_SESSION['resolved_option'] ) ) {
            $resolved_option = $v;
            break;
        }
    }
} else { $resolved_option = $resolved_options[0]; }


// start and finish dates and times
$db_datetimes = array();

$db_datetimes['start']  = strtotime( cleanDates('date-from', $dateFrom, 'begOfTimes') . " 00:00:00" );
$db_datetimes['finish'] = strtotime( cleanDates('date-to', $dateTo) . " 23:59:59" );


// [ daily | weekly | monthly | yearly ] granularities
if ( $selectedGranularity == 2 ) {          // Weekly
    $date_format    = 'oW';
    $incr_str       = ' weeks';
} elseif ( $selectedGranularity == 3 ) {    // Monthly
    $date_format = 'Ym';
    $incr_str       = ' months';
} elseif ( $selectedGranularity == 4 ) {    // Yearly
    $date_format = 'Y';
    $incr_str       = ' years';
} else {                                    // If granilarity is Daily
    $date_format = 'Y-m-d';
    $incr_str       = ' days';
}



// Preparing data array
$i = 0;

$incrTimestamp = $db_datetimes['start'];

while ( $incrTimestamp < $db_datetimes['finish'] ) {
    $i++;
    $granularity_items[] = date( $date_format, $incrTimestamp );
    $incrTimestamp = strtotime( date( "Ymd", $db_datetimes['start'] ) . " + " . $i . $incr_str);
}

$dateConditionForResolved = " AND h.date_modified >= " . $db->qstr( $db_datetimes['start'] ) . " AND h.date_modified <= " . $db->qstr( $db_datetimes['finish'] );
if ( $resolved_option == 1 ) {
    $dateConditionForResolved = " AND h.date_modified >= " . $db->qstr( $db_datetimes['start'] ) . " AND h.date_modified <= " . $db->qstr( $db_datetimes['finish'] ) . " AND mbt.date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . " AND mbt.date_submitted <= " . $db->qstr( $db_datetimes['finish'] );
}

$query = "
    SELECT date_submitted as the_date
    FROM $mantis_bug_table
    WHERE date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
    AND date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
    AND $specific_where
    ";
$result = $db->GetAll( $query );

if ( sizeof( $result ) > 0 ) {
    foreach ( $result as $row ) {
        $the_date = date( $date_format, $row['the_date'] );
        if ( isset( $db_data['opened'][$the_date] ) ) {
            $db_data['opened'][$the_date]++;
        } else {
            $db_data['opened'][$the_date] = 1;
        }
    }
    $totals['opened'] = sizeof( $result );
} else { $db_data['opened'] = array(); $totals['opened'] = 0; }

$query = "
    SELECT max(h.date_modified) as the_date, mbt.id
    FROM $mantis_bug_table mbt
    LEFT JOIN $mantis_bug_history_table h
    ON mbt.id = h.bug_id
    AND h.type = " . NORMAL_TYPE . "
    AND h.field_name = 'status'
    WHERE mbt.status >= $resolved_status_threshold
    AND h.old_value < '$resolved_status_threshold'
    AND h.new_value >= '$resolved_status_threshold'
    $dateConditionForResolved
    AND $specific_where
    GROUP BY mbt.id
    ";
$result = $db->GetAll( $query );

if ( sizeof( $result ) > 0 ) {
    foreach ( $result as $row ) {
        $the_date = date( $date_format, $row['the_date'] );
        if ( isset( $db_data['resolved'][$the_date] ) ) {
            $db_data['resolved'][$the_date]++;
        } else {
            $db_data['resolved'][$the_date] = 1;
        }
    }
    $totals['resolved'] = sizeof( $result );
} else { $db_data['resolved'] = array(); $totals['resolved'] = 0; }


$grand_total = $totals['opened'] - $totals['resolved'];
if ( $grand_total > 0 ) { $gstyle = "negative"; $gplus = '+'; } else { $gstyle = "positive"; $gplus = ''; }


// building table
$out = pagination( count( $granularity_items ), 1, plugin_page( 'trends_by_open_resolved' ) );

$data_table_print = "<table class='width100' cellspacing='1'><tr><td width='100%' class='form-title'>" . lang_get( 'plugin_MantisStats_by_date' ) . "</td>";
$data_table_print .= "<td class='heading'>" . lang_get( 'opened' ) . "</td><td class='heading'>" . lang_get( 'resolved' ) . "</td><td class='heading'>" . lang_get( 'balance' ) . "</td></tr>";

rsort($granularity_items);

$i = 0;

foreach ($granularity_items as $key => $val) {

    $i++;

	if ( $selectedGranularity == 2 )      { $show_date = substr($val, 0, 4) . " " . lang_get('plugin_MantisStats_week_short') . " " . substr($val, 4); } // Weekly
    elseif ( $selectedGranularity == 3 )  { $show_date = substr($val, 0, 4) . "." . substr($val, 4); } // Monthly
    else { $show_date = $val; }

    if ( $i <= ($out['offset']-1)*$out['perpage'] ) { continue; }
    if ( $i > $out['offset']*$out['perpage'] ) { break; }

    $data_table_print .= "<tr " . helper_alternate_class() . "><td>" . $show_date . "</td>";

	if ( array_key_exists( $val, $db_data['opened'] ) ) { $show_count = $db_data['opened'][$val]; } else { $show_count = 0; }
	$data_table_print .= "<td class='right'>" . $show_count . "</td>";

    if ( array_key_exists( $val, $db_data['resolved'] ) ) { $show_count = $db_data['resolved'][$val]; } else { $show_count = 0; }
    $data_table_print .= "<td class='right'>" . $show_count . "</td>";

	$balance = @$db_data['opened'][$val] - @$db_data['resolved'][$val];
	if ( $balance > 0 ) { $style = "negative"; $plus = '+'; } else { $style = "positive"; $plus = ''; }

	$data_table_print .=  "<td class='right " . $style . "'>" . $plus . $balance . "</td></tr>";
}

if ( $out['pagination'] ) {
    $data_table_print .= "<tr><td class='right' colspan='4'><ul id='pagesNav'>" . $out['pagination'] . "</td></tr>";
}

$data_table_print .=  "<tr><td class='right  tdtop'><strong>" . lang_get( 'plugin_MantisStats_grand_total' ) . "</strong></td><td class='right tdtop'><strong>" . number_format( $totals['opened'] ) . "</strong></td>";
$data_table_print .= "<td class='right tdtop'><strong>" . number_format( $totals['resolved'] ) . "</strong></td>";
$data_table_print .=  "<td class='right tdtop " . $gstyle . "'><strong>" . $gplus . number_format( $grand_total ) . "</strong></td></tr>";


$data_table_print .= "</table>";


// chart TODO
$chart_data = array ('categories' => '', 'opened' => '', 'resolved' => '');
$granularity_items = array_reverse($granularity_items);

foreach ($granularity_items as $key => $val) {

    if ( $selectedGranularity == 2 )      { $show_date = substr($val, 0, 4) . " " . lang_get('plugin_MantisStats_week_short') . " " . substr($val, 4); } // Weekly
    elseif ( $selectedGranularity == 3 )  { $show_date = substr($val, 0, 4) . "." . substr($val, 4); } // Monthly
    else { $show_date = $val; }

    $chart_data['categories'] .= "<category label='" . $show_date . "' />";
    if ( array_key_exists( $val, $db_data['opened'] ) ) { $show_count = $db_data['opened'][$val]; } else { $show_count = 0; }
    $chart_data['opened'] .= "<set tooltext='" . htmlspecialchars( lang_get( 'opened' ), ENT_QUOTES ) . ": " . number_format( $show_count ) . " [" . $show_date . "]' value='" . $show_count . "' />";
    if ( array_key_exists( $val, $db_data['resolved'] ) ) { $show_count = $db_data['resolved'][$val]; } else { $show_count = 0; }
    $chart_data['resolved'] .= "<set tooltext='" . htmlspecialchars( lang_get( 'resolved' ), ENT_QUOTES ) . ": " . number_format( $show_count ) . " [" . $show_date . "]' value='" . $show_count . "' />";
}

$chart_data_print  = "<categories>" . $chart_data['categories'] . "</categories>";
$chart_data_print .= "<dataset seriesName='" . htmlspecialchars( lang_get( 'opened' ), ENT_QUOTES ) . "' color='0000CC'>" . $chart_data['opened'] . "</dataset>";
$chart_data_print .= "<dataset seriesName='" . htmlspecialchars( lang_get( 'resolved' ), ENT_QUOTES ) . "' color='009933'>" . $chart_data['resolved'] . "</dataset>";

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
            <div id="title"><?php echo lang_get( 'plugin_MantisStats_open_vs_res_long' ); ?></div>
            <div id="scope">&raquo; <?php echo lang_get( 'plugin_MantisStats_project' ); ?>: <?php echo project_get_name( $project_id ); ?></div><div id="sup"><?php if ( $project_id == ALL_PROJECTS ) { echo "<sup>&dagger;</sup>"; } ?></div>
        </div>

        <p class="clear" />

        <div id="filter">
            <strong><?php echo lang_get( 'plugin_MantisStats_timeframe_op_re' ); ?></strong>
            <p />
            <form method="get">
                <input type="hidden" name="page" value="MantisStats/trends_by_open_resolved" />
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
                    <label class="ind"><?php echo lang_get( 'plugin_MantisStats_granularity' ); ?></label>
                    <select name="granularity" id="granularity">
<?php
foreach( $granularities as $key => $val ) {
    if ( $selectedGranularity == $key ) { $selectedFormValue = " selected "; } else { $selectedFormValue = ''; }
    echo "<option " . $selectedFormValue . " value='" . $key . "'>" . $val . "</option>";
}
?>
                    </select>
                </div>

                <p class="space20Before" />
                <input type="radio" class="resolution_date_options" name="resolution_date_options" id="op1" <?php if ( $resolved_option == 1 ) { echo "checked"; } ?> value="1">
                <label for="op1" class="inl"><?php echo lang_get( 'plugin_MantisStats_res_radio_opt1' ); ?></label>
                <p />
                <input type="radio" class="resolution_date_options" name="resolution_date_options" id="op2" <?php if ( $resolved_option == 2 ) { echo "checked"; } ?> value="2">
                <label for="op2" class="inl"><?php echo lang_get( 'plugin_MantisStats_res_radio_opt2' ); ?></label>
                <p />
                <input type="submit" id="displaysubmit" value=<?php echo lang_get( 'plugin_MantisStats_display' ); ?> class="button" />
            </form>
        </div>


        <p class="space30Before" />
        <strong><?php echo lang_get( 'plugin_MantisStats_open_vs_res_long' ); ?></strong>
        <p />
        <?php echo $data_table_print; ?>

<?php if ( $render_print_js != "no_render" ) { ?>

        <p class="space30Before" />
        <strong><?php echo lang_get( 'plugin_MantisStats_open_vs_res_chrt' ); ?></strong>

		<div id="op_res_chart"><?php echo lang_get( 'plugin_MantisStats_by_date' ); ?></div>
		<script type="text/javascript">
		// <![CDATA[

        <?php echo $render_print_js; ?>
		var myChart = new FusionCharts("<?php echo plugin_file('ScrollLine2D.swf'); ?>", "myChartIdMonth", "728", "400", "0", "1");
		myChart.setDataXML("<chart showAboutMenuItem='0' bgColor='FFFFFF' numVDivLines='<?php echo count($granularity_items); ?>' divLineAlpha='30' showValues='0' rotateNames='1' valuePosition='auto' scrollToEnd='1'><?php echo $chart_data_print; ?></chart>");
		myChart.render("op_res_chart");
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