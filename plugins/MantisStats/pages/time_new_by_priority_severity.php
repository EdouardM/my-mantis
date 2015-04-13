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
$mantis_bug_history_table   = db_get_table( 'mantis_bug_history_table' );
$private_bug_threshold      = config_get( 'private_bug_threshold' );
$resolved_status_threshold  = config_get( 'bug_resolved_status_threshold' );
$status_enum_string         = lang_get( 'status_enum_string' );
$priority_enum_string       = lang_get( 'priority_enum_string' );
$severity_enum_string       = lang_get( 'severity_enum_string' );


// start and finish dates and times
$db_datetimes = array();

$db_datetimes['start']  = strtotime( cleanDates('date-from', $dateFrom, 'begOfTimes') . " 00:00:00" );
$db_datetimes['finish'] = strtotime( cleanDates('date-to', $dateTo) . " 23:59:59" );


// state, priority, severity | all is faster?
$extra = array();

$query = "
        SELECT id, status, priority, severity
        FROM $mantis_bug_table
        WHERE $specific_where
        ";
$result = $db->GetAll( $query );

foreach ( $result as $row ) {
    $extra['st'][$row['id']] = MantisEnum::getLabel( $status_enum_string, $row['status'] );
    $extra['pr'][$row['id']] = MantisEnum::getLabel( $priority_enum_string, $row['priority'] );
    $extra['sv'][$row['id']] = MantisEnum::getLabel( $severity_enum_string, $row['severity'] );
}

unset( $result );


// number of notes | all is faster?
$query = "
        SELECT COUNT( * ) AS the_count, mbnt.bug_id
        FROM $mantis_bugnote_table mbnt
        LEFT JOIN $mantis_bug_table mbt ON mbnt.bug_id = mbt.id
        WHERE $specific_where
        GROUP BY bug_id
        ";
$result = $db->GetAll( $query );

foreach ( $result as $row ) {
    $extra['nt'][$row['bug_id']] = $row['the_count'];
}

unset( $result );


// issues creation times
$created_times = array();

$query = "
        SELECT id, date_submitted, summary
        FROM $mantis_bug_table
        WHERE $specific_where
        AND date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
        AND date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
        ";
$result = $db->GetAll( $query );

foreach ( $result as $row ) {
    $created_times[$row['id']] = intval( $row['date_submitted'] );
    $extra['sm'][$row['id']] = $row['summary'];
}

unset( $result );


// status changes FROM or TO 'new'
$status_change_ovalue = $status_change_nvalue = $status_change_time = $status_change_ids_nonunique = array();
$query = "
        SELECT mbht.bug_id, mbht.date_modified
        FROM $mantis_bug_history_table mbht
        LEFT JOIN $mantis_bug_table mbt ON mbht.bug_id = mbt.id
        WHERE $specific_where
        AND mbht.field_name = 'status' AND (mbht.old_value = '10' OR mbht.new_value = '10')
        AND mbt.date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
        AND mbt.date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
        ";

$result = $db->GetAll( $query );

foreach ( $result as $row ) {
    $status_change_time[$row['bug_id']][] = intval( $row['date_modified'] );
    $status_change_ids_nonunique[] = $row['bug_id']; // some IDs will repeat, need to make them unique
}

unset( $result );


// there was status change for these issues
$status_change_ids = array_unique( $status_change_ids_nonunique );


// issues, which are created with no further status change are ["brand new"]
$new_ids = array_diff( array_keys( $created_times ), $status_change_ids );

$list_header = "
    <table class='width100' cellspacing='1'>
    <tr>
    <td width='*' class='form-title'>" . lang_get( 'plugin_MantisStats_issue_summary' ) . "</td>
    <td style='width: 100px'>" . lang_get( 'status' ) . "</td>
    <td style='width: 50px'>" . lang_get( 'notes' ) . "</td>
    <td style='width: 100px'>" . lang_get( 'priority' ) . "</td>
    <td style='width: 70px'>" . lang_get( 'severity' ) . "</td>
    <td style='width: 100px' class='heading'>" . lang_get( 'plugin_MantisStats_waiting_times' ) . "</td>
    </tr>
";

$tmp_wait = array();

foreach ( $new_ids as $key => $val ) {
    $new_wait = $time_now - $created_times[$val];
    $tmp_wait[$val] = $new_wait;
}

$new_wait = $tmp_wait;

array_multisort( $tmp_wait, SORT_DESC, $new_ids );

$out = pagination( count( $new_ids ), 1, plugin_page( 'time_new_by_priority_severity' ) );
$i = 0;

$new_list = "";

foreach ( $new_ids as $key => $val ) {
    // if private and access level is not enough then skip
    if(( VS_PRIVATE == bug_get_field( $val, 'view_state' ) ) && ( false == access_has_bug_level( $private_bug_threshold, $val ) ) ) { continue; }

    if ( !isset( $extra['nt'][$val] ) ) { $extra['nt'][$val] = 0; }

    $i++;
    if ( $i <= ($out['offset']-1)*$out['perpage'] ) { continue; }
    if ( $i > $out['offset']*$out['perpage'] ) { break; }

    $new_list .= "
    <tr " . helper_alternate_class() . ">
    <td>" . string_get_bug_view_link( $val ) . " - " . $extra['sm'][$val] . "</td>
    <td>" . $extra['st'][$val] . "</td>
    <td>" . $extra['nt'][$val] . "</td>
    <td>" . $extra['pr'][$val] . "</td>
    <td>" . $extra['sv'][$val] . "</td>
    <td class='right'>" . waitFormat( $new_wait[$val] ) . "</td>
    </tr>
    ";
}

if ( $out['pagination'] ) {
    $new_list .= "<tr><td class='right' colspan='6'><ul id='pagesNav'>" . $out['pagination'] . "</td></tr>";
}

$iterations_array = array_count_values( $status_change_ids_nonunique );

$tmp_wait = array();

foreach ( $status_change_ids as $key => $val ) {

    $wait = $status_change_time[$val][0] - $created_times[$val];
    $iteration = $iterations_array[$val];

    if ( $iteration > 1 ) {

        $wait = 0;
        $half = $iteration/2;
        $i = 1;
        $j = 2;

        while ( $iteration > $j ) {
            $wait = $wait + $status_change_time[$val][$j] - $status_change_time[$val][$i];
            $i = $i + 2;
            $j = $j + 2;
        }

        if ( round( $half ) == $half ) {
            $wait = $wait + $created_times[$val] - $status_change_time[$val][$i];
        }
    }

    $tmp_wait[$val] = $wait;

}

$rest_wait = $tmp_wait;

array_multisort( $tmp_wait, SORT_DESC, $status_change_ids );

$out = pagination( count( $status_change_ids ), 2, plugin_page( 'time_new_by_priority_severity' ) );
$i = 0;

$rest_list = "";

foreach ( $status_change_ids as $key => $val ) {
    // if private and access level is not enough then skip
    if(( VS_PRIVATE == bug_get_field( $val, 'view_state' ) ) && ( false == access_has_bug_level( $private_bug_threshold, $val ) ) ) { continue; }

    if ( !isset( $extra['nt'][$val] ) ) { $extra['nt'][$val] = 0; }

    $i++;
    if ( $i <= ($out['offset']-1)*$out['perpage'] ) { continue; }
    if ( $i > $out['offset']*$out['perpage'] ) { break; }

    $rest_list .= "
    <tr " . helper_alternate_class() . ">
    <td>" . string_get_bug_view_link( $val ) . " - " . $extra['sm'][$val] . "</td>
    <td>" . $extra['st'][$val] . "</td>
    <td>" . $extra['nt'][$val] . "</td>
    <td>" . $extra['pr'][$val] . "</td>
    <td>" . $extra['sv'][$val] . "</td>
    <td class='right'>" . waitFormat( $rest_wait[$val] ) . "</td>
    </tr>
    ";
}

unset( $extra );

if ( $out['pagination'] ) {
    $rest_list .= "<tr><td class='right' colspan='6'><ul id='pagesNav'>" . $out['pagination'] . "</td></tr>";
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
            <div id="title"><?php echo lang_get( 'plugin_MantisStats_time_new_pr_sv_long' ); ?></div>
            <div id="scope">&raquo; <?php echo lang_get( 'plugin_MantisStats_project' ); ?>: <?php echo project_get_name( $project_id ); ?></div><div id="sup"><?php if ( $project_id == ALL_PROJECTS ) { echo "<sup>&dagger;</sup>"; } ?></div>
        </div>

        <p class="clear" />

        <div id="filter">
            <strong><?php echo lang_get( 'plugin_MantisStats_timeframe' ); ?></strong>
            <p />
            <form method="get">
                <input type="hidden" name="page" value="MantisStats/time_new_by_priority_severity" />
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

        <!-- DOTO: to replace table with css -->
        <table class="space10Before" stype="border-spacing: 0; border-collapse: collapse;">
            <tr>
                <td style="padding: 0;"><img src="<?php echo plugin_file('images/infoIcon.png'); ?>" width="30" height="30" /></td>
                <td style="padding-left: 7px;"><?php echo lang_get( 'plugin_MantisStats_info_waiting_new' ); ?></td>
            </tr>
        </table>

        <p class="space30Before" />
        <strong><?php echo lang_get( 'plugin_MantisStats_time_new_top1' ); ?><sup>&Dagger;</sup></strong>
        <p />
        <?php echo $list_header . $new_list . "</table>"; ?>

        <p class="space30Before" />
        <strong><?php echo lang_get( 'plugin_MantisStats_time_new_top2' ); ?><sup>&Dagger;</sup></strong>
        <p />
        <?php echo $list_header . $rest_list . "</table>"; ?>
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