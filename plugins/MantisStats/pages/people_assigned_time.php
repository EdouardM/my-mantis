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
$mantis_user_table          = db_get_table( 'mantis_user_table' );
$mantis_bug_history_table   = db_get_table( 'mantis_bug_history_table' );
$resolved_status_threshold  = config_get( 'bug_resolved_status_threshold' );


// start and finish dates and times
$db_datetimes = $data = $timedata = $bugs_and_states = array();

$db_datetimes['start']  = strtotime( cleanDates('date-from', $dateFrom, 'begOfTimes') . " 00:00:00" );
$db_datetimes['finish'] = strtotime( cleanDates('date-to', $dateTo) . " 23:59:59" );


// get all issues with their states
$query = "
        SELECT id, status
        FROM $mantis_bug_table mbt
        WHERE $specific_where
        AND mbt.date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
        AND mbt.date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
        ";
$result = $db->GetAll( $query );

foreach ( $result as $row ) {
    $bugs_and_states[$row['id']] = $row['status'];
}

unset( $result );


// status changes FROM or TO 'assigned'
$query = "
        SELECT mbht.field_name, mbht.bug_id, mbht.old_value, mbht.new_value, mbht.date_modified
        FROM $mantis_bug_history_table mbht
        LEFT JOIN $mantis_bug_table mbt ON mbht.bug_id = mbt.id
        WHERE $specific_where
        AND mbht.field_name = 'handler_id' OR (mbht.field_name = 'status' AND (mbht.old_value = '50' OR mbht.new_value = '50'))
        AND mbt.date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
        AND mbt.date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
        ORDER BY mbht.bug_id, mbht.date_modified
        ";
$result = $db->GetAll( $query );

$i = $bug_id = 0;

foreach ( $result as $row ) {
    $data['f_name'][$i] = $row['field_name'];
    $data['bug_id'][$i] = intval( $row['bug_id'] );
    $data['ovalue'][$i] = intval( $row['old_value'] );
    $data['nvalue'][$i] = intval( $row['new_value'] );
    $data['d_time'][$i] = intval( $row['date_modified'] );
    $i++;
}

unset( $result );

if ( sizeof( $data ) > 0 ) {

    foreach ( $data['f_name'] as $key => $val ) {

        $action = '';

        if ( $bug_id != $data['bug_id'][$key] ) {
            $bug_id = $data['bug_id'][$key];
            $handler = $old_handler = 0;
            $counter = $old_counter = 0;
        }

        if ( $val == 'handler_id' ) {

            $action = 'hchange';
            $old_handler = $handler;
            $handler = $data['nvalue'][$key];

        } elseif ( $val == 'status' ) {

            $action = 'schange';
            $old_counter = $counter;
            if ( $data['nvalue'][$key] == 50 ) {
                $counter = 1;
            } else {
                $counter = 0;
            }

        }

        // handler change: we act only if tkt is in assigned state
        if ( $action == 'hchange' ) {

            if ( $counter == 1 ) {

                if ( $handler != 0 and $old_handler == 0 ) {
                    $timedata[$handler][$data['bug_id'][$key]][] = $data['d_time'][$key];
                }

                if ( $handler == 0 and $old_handler != 0 ) {
                    $timedata[$old_handler][$data['bug_id'][$key]][] = $data['d_time'][$key];
                }

                if ( $handler != 0 and $old_handler != 0 ) {
                    $timedata[$handler][$data['bug_id'][$key]][] = $data['d_time'][$key];
                    $timedata[$old_handler][$data['bug_id'][$key]][] = $data['d_time'][$key];
                }

            }

        }

        // state change: we act only if tkt is currently assigned to someone
        if ( $action == 'schange' ) {

            if ( $handler != 0 ) {

                if ( $counter == 1 and $old_counter == 0 ) {
                    $timedata[$handler][$data['bug_id'][$key]][] = $data['d_time'][$key];
                }

                if ( $counter == 0 and $old_counter == 1 ) {
                    $timedata[$handler][$data['bug_id'][$key]][] = $data['d_time'][$key];
                }

            }

        }
    }
}

unset( $data );

function toplist( $type ) {

    global $timedata, $resolved_status_threshold, $bugs_and_states, $time_now;

    $toplist = "
        <table class='width100' cellspacing='1'>
        <tr>
        <td width='*' class='form-title'>" . lang_get( 'plugin_MantisStats_handler_name' ) . "</td>
        <td style='width: 100px' class='heading'>" . lang_get( 'plugin_MantisStats_avg_time_in_assigned' ) . "</td>
        <td style='width: 100px' class='heading'>" . lang_get( 'plugin_MantisStats_no_of_issues' ) . "</td>
        </tr>
    ";

    $assigned_time_avg = $no_of_issues_per_user = array();
    $user_names = user_names();

    if ( $type == 'open' ) { $offset_switch = 1; }
    if ( $type == 'resolved' ) { $offset_switch = 2; }

    foreach ( $timedata as $k1 => $v1 ) {

        $wait = 0;
        $no_of_issues_per_user[$k1] = 0;

        foreach ( $v1 as $k2 => $v2 ) {

            if ( $type == 'open' and $bugs_and_states[$k2] >= $resolved_status_threshold ) { continue; }
            if ( $type == 'resolved' and $bugs_and_states[$k2] < $resolved_status_threshold ) { continue; }

            $iteration = sizeof( $v2 );
            $half = $iteration/2;

            $i = 0;
            $j = 1;

            while ( $iteration > $j ) {
                $wait = $wait + $v2[$j] - $v2[$i];
                $i = $i + 2;
                $j = $j + 2;
            }

            if ( round( $half ) != $half ) {
                $wait = $wait + $time_now - $v2[$i];
            }

            $no_of_issues_per_user[$k1]++;
        }

        if ( $wait != 0 and $no_of_issues_per_user[$k1] != 0 ) {
            $assigned_time_avg[$k1] = round( $wait/$no_of_issues_per_user[$k1] );
        }
    }

    unset( $timedata );

    arsort( $assigned_time_avg );

    $out = pagination( count( $assigned_time_avg ), $offset_switch, plugin_page( 'people_assigned_time' ) );

    $i = 0;

    foreach ( $assigned_time_avg as $key => $val ) {
        $i++;
        if ( $i <= ($out['offset']-1)*$out['perpage'] ) { continue; }
        if ( $i > $out['offset']*$out['perpage'] ) { break; }

        if ( !isset( $user_names[$key] ) ) { $user_names[$key] = "User" . $key; }

        $toplist .= "
        <tr " . helper_alternate_class() . ">
        <td>" . $user_names[$key] . "</td>
        <td class='right'>" . waitFormat( $val ) . "</td>
        <td class='right'>" . $no_of_issues_per_user[$key] . "</td>
        </tr>
        ";
    }

    if ( $out['pagination'] ) {
        $toplist .= "<tr><td class='right' colspan='6'><ul id='pagesNav'>" . $out['pagination'] . "</td></tr>";
    }

    $toplist .= "</table>";
    return $toplist;
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
            <div id="title"><?php echo lang_get( 'plugin_MantisStats_avg_time_in_assigned' ); ?></div>
            <div id="scope">&raquo; <?php echo lang_get( 'plugin_MantisStats_project' ); ?>: <?php echo project_get_name( $project_id ); ?></div><div id="sup"><?php if ( $project_id == ALL_PROJECTS ) { echo "<sup>&dagger;</sup>"; } ?></div>
        </div>

        <p class="clear" />

        <div id="filter">
            <strong><?php echo lang_get( 'plugin_MantisStats_timeframe' ); ?></strong>
            <p />
            <form method="get">
                <input type="hidden" name="page" value="MantisStats/people_assigned_time" />
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
        <?php echo toplist('open') . "</table>"; ?>

        <p class="space30Before" />
        <strong><?php echo lang_get( 'plugin_MantisStats_resolved_issues' ); ?></strong>
        <p />
        <?php echo toplist('resolved') . "</table>"; ?>

        <p class="space40Before" />
        <?php if ( $project_id == ALL_PROJECTS ) { echo "&dagger; " . lang_get( 'plugin_MantisStats_priv_proj_skip' ); } ?>
        <?php if ( $final_runtime_value == 1 ) { printf( "<p class='graycolor'>" . lang_get( 'plugin_MantisStats_runtime_string' ) . "</p>", round(microtime(true) - $starttime, 5) ); } ?>
    </div>

    <div id="sidebar"><?php echo $sidebar; ?></div>
</div>

<div id="footer"><?php html_page_bottom();?></div>