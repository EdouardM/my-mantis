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
$bug_history_table          = db_get_table( 'mantis_bug_history_table' );
$resolved_status_threshold  = config_get( 'bug_resolved_status_threshold' );
$private_bug_threshold      = config_get( 'private_bug_threshold' );


// start and finish dates and times
$db_datetimes = array();

$db_datetimes['start']  = strtotime( cleanDates('date-from', $dateFrom, 'begOfTimes') . " 00:00:00" );
$db_datetimes['finish'] = strtotime( cleanDates('date-to', $dateTo) . " 23:59:59" );


// needed arrays
$top_issue_with_handlers = $number_of_issues = $curr_without_handlers =
$handlers_in_issues = $issues_with_handlers = $average_handlers = $without_handlers = array();


// top issue with most handlers [open|resolved]
$query = "
        SELECT COUNT( DISTINCT new_value ) AS the_count
        FROM $bug_history_table mht
        LEFT JOIN $mantis_bug_table mbt ON mht.bug_id = mbt.id
        WHERE $specific_where
        AND mbt.status < $resolved_status_threshold
        AND field_name =  'handler_id'
        AND new_value != 0
        AND mbt.date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
        AND mbt.date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
        GROUP BY bug_id
        ORDER BY the_count DESC
        LIMIT 1
        ";
$result = $db->GetAll( $query );

if ( isset( $result[0] ) ) {
    $row = $result[0];
    $top_issue_with_handlers['open'] = $row['the_count'];
} else { $top_issue_with_handlers['open'] = 0; }

$query = "
        SELECT COUNT( DISTINCT new_value ) AS the_count
        FROM $bug_history_table mht
        LEFT JOIN $mantis_bug_table mbt ON mht.bug_id = mbt.id
        WHERE $specific_where
        AND mbt.status >= $resolved_status_threshold
        AND field_name =  'handler_id'
        AND new_value != 0
        AND mbt.date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
        AND mbt.date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
        GROUP BY bug_id
        ORDER BY the_count DESC
        LIMIT 1
        ";
$result = $db->GetAll( $query );

if ( isset( $result[0] ) ) {
    $row = $result[0];
    $top_issue_with_handlers['resolved'] = $row['the_count'];
} else { $top_issue_with_handlers['resolved'] = 0; }


// number of issues [open|resolved|all], number issues currently with no handlers [open|resolved|all]
$query = "
        SELECT 'open' as issues_type, count(*) as the_count, sum(IF(handler_id = 0, 1, 0)) as curr_no_handler
        FROM $mantis_bug_table
        WHERE $specific_where
        AND date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
        AND date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
        AND status < " . $resolved_status_threshold . "
        ";
$result = $db->GetAll( $query );
$row = $result[0];
$number_of_issues['open'] = $row['the_count'];

if ( $row['curr_no_handler'] ) {
    $curr_without_handlers['open'] = $row['curr_no_handler'];
} else { $curr_without_handlers['open'] = 0; }

$query = "
        SELECT 'resolved' as issues_type, count(*) as the_count, sum(IF(handler_id = 0, 1, 0)) as curr_no_handler
        FROM $mantis_bug_table
        WHERE $specific_where
        AND date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
        AND date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
        AND status >= " . $resolved_status_threshold . "
        ";
$result = $db->GetAll( $query );
$row = $result[0];
$number_of_issues['resolved'] = $row['the_count'];

if ( $row['curr_no_handler'] ) {
    $curr_without_handlers['resolved'] = $row['curr_no_handler'];
} else { $curr_without_handlers['resolved'] = 0; }

$number_of_issues['all'] = $number_of_issues['open'] + $number_of_issues['resolved'];
$curr_without_handlers['all'] = $curr_without_handlers['open'] + $curr_without_handlers['resolved'];


// average N. of handlers in issues [open|resolved|all]
// N. of cases without handlers [open|resolved|all]
$query = "
        SELECT sum(the_count) as handlers_sum, sum(1) as with_handlers
        FROM
        (
            SELECT COUNT( DISTINCT new_value ) AS the_count
            FROM $bug_history_table mht
            LEFT JOIN $mantis_bug_table mbt ON mht.bug_id = mbt.id
            WHERE $specific_where
            AND mbt.status < $resolved_status_threshold
            AND field_name =  'handler_id'
            AND new_value != 0
            AND mbt.date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
            AND mbt.date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
            GROUP BY bug_id
        ) as inner_tbl
        ";
$result = $db->GetAll( $query );
$row = $result[0];

if ( $number_of_issues['open'] ) {
    $average_handlers['open'] = round( $row['handlers_sum'] / $number_of_issues['open'], 2 );
} else { $average_handlers['open'] = 0; }

$without_handlers['open'] = $number_of_issues['open'] - $row['with_handlers'];
$tmp = $row['handlers_sum'];

$query = "
        SELECT sum(the_count) as handlers_sum, sum(1) as with_handlers
        FROM
        (
            SELECT COUNT( DISTINCT new_value ) AS the_count
            FROM $bug_history_table mht
            LEFT JOIN $mantis_bug_table mbt ON mht.bug_id = mbt.id
            WHERE $specific_where
            AND mbt.status >= $resolved_status_threshold
            AND field_name =  'handler_id'
            AND new_value != 0
            AND mbt.date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
            AND mbt.date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
            GROUP BY bug_id
        ) as inner_tbl
        ";
$result = $db->GetAll( $query );
$row = $result[0];

if ( $number_of_issues['resolved'] ) {
    $average_handlers['resolved'] = round( $row['handlers_sum'] / $number_of_issues['resolved'], 2 );
} else { $average_handlers['resolved'] = 0; }

$without_handlers['resolved'] = $number_of_issues['resolved'] - $row['with_handlers'];

if ( $number_of_issues['all'] ) {
    $average_handlers['all'] = round( ($tmp + $row['handlers_sum']) / $number_of_issues['all'], 2 );
} else { $average_handlers['all'] = 0; }

$without_handlers['all'] = $without_handlers['open'] + $without_handlers['resolved'];


// printing summary table
$summary_table_print = "

<table class='width100' cellspacing='1'>
<tr>
<td width='100%' class='form-title'>" . lang_get( 'plugin_MantisStats_handler_stats' ) . "</td>
<td class='heading'>" . lang_get( 'plugin_MantisStats_in_open_issues' ) . "</td>
<td class='heading'>" . lang_get( 'plugin_MantisStats_in_resolved_iss' ) . "</td>
<td class='heading'>" . lang_get( 'plugin_MantisStats_in_all_issues' ) . "</td>
</tr>

<tr " . helper_alternate_class() . ">
<td>" . lang_get( 'plugin_MantisStats_mosthnd_in_single' ) . "</td>
<td class='right'>" . $top_issue_with_handlers['open'] . "</td>
<td class='right'>" . $top_issue_with_handlers['resolved'] . "</td>
<td class='right'>" . max( $top_issue_with_handlers['open'], $top_issue_with_handlers['resolved'] ) . "</td>
</tr>

<tr " . helper_alternate_class() . ">
<td>" . lang_get( 'plugin_MantisStats_average_handlers' ) . "</td>
<td class='right'>" . $average_handlers['open'] . "</td>
<td class='right'>" . $average_handlers['resolved'] . "</td>
<td class='right'>" . $average_handlers['all'] . "</td>
</tr>

<tr " . helper_alternate_class() . ">
<td>" . lang_get( 'plugin_MantisStats_never_assto_hnd' ) . "</td>
<td class='right'>" . number_format( $without_handlers['open'] ) . "</td>
<td class='right'>" . number_format( $without_handlers['resolved'] ) . "</td>
<td class='right'>" . number_format( $without_handlers['all'] ) . "</td>
</tr>
<tr " . helper_alternate_class() . ">
<td>" . lang_get( 'plugin_MantisStats_currently_no_hnd' ) . "</td>
<td class='right'>" . number_format( $curr_without_handlers['open'] ) . "</td>
<td class='right'>" . number_format( $curr_without_handlers['resolved'] ) . "</td>
<td class='right'>" . number_format( $curr_without_handlers['all'] ) . "</td>
</tr>
</table>

";


// issues with at least one handler are here, sorted by number of handers in issues
$issues_fetch_from_db = array();

$query = "
        SELECT mbt.id, COUNT( DISTINCT new_value ) AS the_count, mbt.summary, mbt.status
        FROM $bug_history_table mht
        LEFT JOIN $mantis_bug_table mbt ON mht.bug_id = mbt.id
        WHERE $specific_where
        AND field_name =  'handler_id'
        AND new_value != '0'
        AND mbt.date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
        AND mbt.date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
        GROUP BY mbt.id
        ORDER BY the_count DESC
        ";
$result = $db->GetAll( $query );

foreach ($result as $row) {
    $issues_fetch_from_db[$row['id']]['the_count']  = $row['the_count'];
    $issues_fetch_from_db[$row['id']]['status']     = $row['status'];
    $issues_fetch_from_db[$row['id']]['summary']    = $row['summary'];
}


function toplist ($type) {
	global $issues_fetch_from_db, $private_bug_threshold, $resolved_status_threshold;
    $summaries = $counts = array();

    $i = 0;
    $toplist_table_print = '';

    if ( $type == 'open' ) { $offset_switch = 1; }
    if ( $type == 'resolved' ) { $offset_switch = 2; }

    foreach ( $issues_fetch_from_db as $key => $val ) {
       if (($val['status'] < $resolved_status_threshold and $type == "open") || ($val['status'] >= $resolved_status_threshold and $type == "resolved")) {
            $summaries[$key] = $val['summary'];
            $counts[$key] = $val['the_count'];
        }
    }

    $out = pagination( count( $summaries ), $offset_switch, plugin_page( 'issues_by_handlers' ) );

    if ( count( $summaries ) > 0 ) {
        foreach ( $summaries as $key => $val ) {

            // if private and access level is not enough then skip
            if(( VS_PRIVATE == bug_get_field( $key, 'view_state' ) ) && ( false == access_has_bug_level( $private_bug_threshold, $key ) ) ) { continue; }

            $i++;

            if ( $i <= ($out['offset']-1)*$out['perpage'] ) { continue; }
            if ( $i > $out['offset']*$out['perpage'] ) { break; }



            $toplist_table_print .= "
            <tr " . helper_alternate_class() . ">
            <td>" . string_get_bug_view_link( $key ) . " - " . $val . "</td>
            <td class='right'>" . $counts[$key] . "</td>
            </tr>";
        }
    }

    if ( $out['pagination'] ) {
        $toplist_table_print .= "<tr><td class='right' colspan='2'><ul id='pagesNav'>" . $out['pagination'] . "</td></tr>";
    }

    return $toplist_table_print;
}

$top_list_header = "
    <table class='width100' cellspacing='1'>
    <tr>
    <td width='100%' class='form-title'>" . lang_get( 'plugin_MantisStats_issue_summary' ) . "</td>
    <td class='heading'>" . lang_get( 'plugin_MantisStats_no_of_handlers' ) . "</td>
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
            <div id="title"><?php echo lang_get( 'plugin_MantisStats_handler_stats' ); ?></div>
            <div id="scope">&raquo; <?php echo lang_get( 'plugin_MantisStats_project' ); ?>: <?php echo project_get_name( $project_id ); ?></div><div id="sup"><?php if ( $project_id == ALL_PROJECTS ) { echo "<sup>&dagger;</sup>"; } ?></div>
        </div>

        <p class="clear" />

        <div id="filter">
            <strong><?php echo lang_get( 'plugin_MantisStats_timeframe' ); ?></strong>
            <p />
            <form method="get">
                <input type="hidden" name="page" value="MantisStats/issues_by_handlers" />
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
        <strong><?php echo lang_get( 'plugin_MantisStats_iss_open_top_hnd' ); ?><sup>&Dagger;</sup></strong>
        <p />
        <?php echo $top_list_header . toplist('open') . "</table>"; ?>

        <p class="space30Before" />
        <strong><?php echo lang_get( 'plugin_MantisStats_iss_res_top_hnd' ); ?><sup>&Dagger;</sup></strong>
        <p />
        <?php echo $top_list_header . toplist('resolved') . "</table>"; ?>
        <p />

        <p class="space40Before" />
        <?php if ( $project_id == ALL_PROJECTS ) { echo "&dagger; " . lang_get( 'plugin_MantisStats_priv_proj_skip' ); } ?>
        <br />
        &Dagger; <?php echo lang_get( 'plugin_MantisStats_priv_iss_skip' ); ?>
        <?php if ( $final_runtime_value == 1 ) { printf( "<p class='graycolor'>" . lang_get( 'plugin_MantisStats_runtime_string' ) . "</p>", round(microtime(true) - $starttime, 5) ); } ?>
    </div>

    <div id="sidebar"><?php echo $sidebar; ?></div>
</div>

<div id="footer"><?php html_page_bottom();?></div>