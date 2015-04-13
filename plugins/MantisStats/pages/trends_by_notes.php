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
$mantis_bugnote_table       = db_get_table( 'mantis_bugnote_table' );


// start and finish dates and times
$db_datetimes = $granularity_items = array();

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

while ( $incrTimestamp <= $db_datetimes['finish'] ) {
    $i++;
    $granularity_items[] = date( $date_format, $incrTimestamp );
    $incrTimestamp = strtotime( date( "o-m-d", $db_datetimes['start'] ) . " + " . $i . $incr_str);
}

$query = "
    SELECT mbnt.date_submitted as the_date
    FROM $mantis_bugnote_table mbnt
    LEFT JOIN $mantis_bug_table mbt
    ON mbnt.bug_id = mbt.id
    WHERE mbnt.date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
    AND mbnt.date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
    AND $specific_where
    ";
$result = $db->GetAll( $query );

if ( sizeof( $result ) > 0 ) {
    foreach ( $result as $row ) {
        $the_date = date( $date_format, $row['the_date'] );
        if ( isset( $db_data['notes'][$the_date] ) ) {
            $db_data['notes'][$the_date]++;
        } else {
            $db_data['notes'][$the_date] = 1;
        }
    }
} else { $db_data['notes'] = array(); }


// granularity-independent totals
$totals = array();
$query = "
    SELECT count(*) as the_count
    FROM $mantis_bugnote_table mbnt
    LEFT JOIN $mantis_bug_table mbt
    ON mbnt.bug_id = mbt.id
    WHERE mbnt.date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
    AND mbnt.date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
    AND $specific_where
    ";
$result = $db->GetAll( $query );
$row = $result[0];
$grand_total = $row['the_count'];


// building table
$out = pagination( count( $granularity_items ), 1, plugin_page( 'trends_by_notes' ) );

$data_table_print = "<table class='width100' cellspacing='1'><tr><td width='100%' class='form-title'>" . lang_get( 'plugin_MantisStats_by_date' ) . "</td>";
$data_table_print .= "<td class='heading'>" . lang_get( 'plugin_MantisStats_notes' ) . "</td></td></tr>";

rsort( $granularity_items );

$i = 0;

foreach ( $granularity_items as $key => $val ) {

    $i++;

	if ( $selectedGranularity == 2 )      { $show_date = substr($val, 0, 4) . " " . lang_get('plugin_MantisStats_week_short') . " " . substr($val, 4); } // Weekly
    elseif ( $selectedGranularity == 3 )  { $show_date = substr($val, 0, 4) . "." . substr($val, 4); } // Monthly
    else { $show_date = $val; }

    if ( $i <= ($out['offset']-1)*$out['perpage'] ) { continue; }
    if ( $i > $out['offset']*$out['perpage'] ) { break; }

    $data_table_print .= "<tr " . helper_alternate_class() . "><td>" . $show_date . "</td>";

	if ( array_key_exists( $val, $db_data['notes'] ) ) { $show_count = $db_data['notes'][$val]; } else { $show_count = 0; }
	$data_table_print .= "<td class='right'>" . number_format($show_count) . "</td></tr>";

}

if ( $out['pagination'] ) {
    $data_table_print .= "<tr><td class='right' colspan='2'><ul id='pagesNav'>" . $out['pagination'] . "</td></tr>";
}

$data_table_print .= "<tr><td class='right'><strong>" . lang_get( 'plugin_MantisStats_grand_total' ) . "</strong></td>";
$data_table_print .= "<td class='right'><strong>" . number_format( $grand_total ) . "</strong></td></tr>";


$data_table_print .= "</table>";


// chart
$chart_data = array ('categories' => '', 'notes' => '');
$granularity_items = array_reverse( $granularity_items );

foreach ( $granularity_items as $key => $val ) {

    if ( $selectedGranularity == 2 )      { $show_date = substr($val, 0, 4) . " " . lang_get('plugin_MantisStats_week_short') . " " . substr($val, 4); } // Weekly
    elseif ( $selectedGranularity == 3 )  { $show_date = substr($val, 0, 4) . "." . substr($val, 4); } // Monthly
    else { $show_date = $val; }

    $chart_data['categories'] .= "<category label='" . $show_date . "' />";
    if ( array_key_exists( $val, $db_data['notes'] ) ) { $show_count = $db_data['notes'][$val]; } else { $show_count = 0; }
    $chart_data['notes'] .= "<set tooltext='" . htmlspecialchars( lang_get( 'plugin_MantisStats_notes' ), ENT_QUOTES ) . ": " . number_format( $show_count ) . " [" . $show_date . "]' value='" . $show_count . "' />";
}

$chart_data_print  = "<categories>" . $chart_data['categories'] . "</categories>";
$chart_data_print .= "<dataset seriesName='" . htmlspecialchars( lang_get( 'plugin_MantisStats_notes' ), ENT_QUOTES ) . "' color='F1683C'>" . $chart_data['notes'] . "</dataset>";

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
            <div id="title"><?php echo lang_get( 'plugin_MantisStats_notes_stats' ); ?></div>
            <div id="scope">&raquo; <?php echo lang_get( 'plugin_MantisStats_project' ); ?>: <?php echo project_get_name( $project_id ); ?></div><div id="sup"><?php if ( $project_id == ALL_PROJECTS ) { echo "<sup>&dagger;</sup>"; } ?></div>
        </div>

        <p class="clear" />

        <div id="filter">
            <strong><?php echo lang_get( 'plugin_MantisStats_timeframe' ); ?></strong>
            <p />
            <form method="get">
                <input type="hidden" name="page" value="MantisStats/trends_by_notes" />
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

                <input type="submit" id="displaysubmit" value=<?php echo lang_get( 'plugin_MantisStats_display' ); ?> class="button" />
            </form>
        </div>


        <p class="space30Before" />
        <?php echo $data_table_print; ?>

<?php if ( $render_print_js != "no_render" ) { ?>

        <p class="space30Before" />
        <strong><?php echo lang_get( 'plugin_MantisStats_notes_chart' ); ?></strong>

		<div id="op_res_chart"><?php echo lang_get( 'plugin_MantisStats_by_date' ); ?></div>
		<script type="text/javascript">
		// <![CDATA[

        <?php echo $render_print_js; ?>
		var myChart = new FusionCharts("<?php echo plugin_file('ScrollLine2D.swf'); ?>", "myChartIdMonth", "728", "400", "0", "1");
		myChart.setDataXML("<chart showLegend='0' showAboutMenuItem='0' bgColor='FFFFFF' numVDivLines='<?php echo count($granularity_items); ?>' divLineAlpha='30' showValues='0' rotateNames='1' valuePosition='auto' scrollToEnd='1'><?php echo $chart_data_print; ?></chart>");
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
