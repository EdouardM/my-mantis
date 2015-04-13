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
$mantis_bug_history_table   = db_get_table( 'mantis_bug_history_table' );
$status_enum_string         = lang_get( 'status_enum_string' );
$status_values              = MantisEnum::getValues($status_enum_string);


// start and finish dates and times
$db_datetimes = array();

$db_datetimes['start']  = strtotime( cleanDates('date-from', $dateFrom, 'begOfTimes') . " 00:00:00" );
$db_datetimes['finish'] = strtotime( cleanDates('date-to', $dateTo) . " 23:59:59" );


$one_day        = 60*60*24;
$one_hour       = 60*60;
$one_minute     = 60;

$data_table_print = '';


// table heading - issue states [ open | resolved ]
$data_table_print = "<table class='width100' cellspacing='1'><tr><td width='100%' class='form-title'>" . lang_get( 'plugin_MantisStats_waiting_times' ) . "</td>";

foreach ($status_values as $key => $val) {
    if ($val < $resolved_status_threshold) {
        $data_table_print .= "<td class='heading'>" . MantisEnum::getLabel($status_enum_string, $val) . "</td>";
    }
}
$data_table_print .= "</tr>";


// fetching longest pending
$times = array();

foreach ($status_values as $key => $val) {
	if ( $val >= $resolved_status_threshold ) { continue; }

	    $query = "
            SELECT UNIX_TIMESTAMP() - date_submitted as waiting_time
            FROM $mantis_bug_table
            WHERE $specific_where
            AND date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
            AND date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
            AND status = $val
            ORDER BY waiting_time desc
            LIMIT 1
            ";
	$result = $db->GetAll( $query );

	if ( isset( $result[0] ) ) {

        $row = $result[0];

    	$waiting_time = $row['waiting_time'];

    	if ( $waiting_time > $one_day ) { // days

            $days       = floor($waiting_time/$one_day);
            $hours      = floor(($waiting_time - $days*$one_day)/$one_hour);
            $minutes    = floor(($waiting_time - $days*$one_day - $hours*$one_hour)/$one_minute);
            $seconds    = $waiting_time - $days*$one_day - $hours*$one_hour - $minutes*$one_minute;

            $times[$val] = $days . "d.&nbsp;" . $hours . ":" . add_zero($minutes) . ":" . add_zero($seconds);

	    } elseif ( $waiting_time > 60*60 ) { // hours

		    $hours		= floor($waiting_time/$one_hour);
		    $minutes	= floor(($waiting_time - $hours*$one_hour)/$one_minute);
		    $seconds	= $waiting_time - $hours*$one_hour - $minutes*$one_minute;

		    $times[$val] = $hours . ":" . add_zero($minutes) . ":" . add_zero($seconds);

	    } elseif ( $waiting_time > 60 ) { // minutes

		    $minutes	= floor($waiting_time/$one_minute);
		    $seconds	= $waiting_time - $minutes*$one_minute;

		    $times[$val] = add_zero($minutes) . ":" . add_zero($seconds);

	    } elseif ( $waiting_time > 0) { //seconds

		    $times[$val] = add_zero($seconds);
	    }
    }
}

// table rows below heading
$data_table_print .= "<tr " . helper_alternate_class() . "><td>" . lang_get( 'plugin_MantisStats_longest_time') . "</td>";

foreach ($status_values as $k => $v) {
	if ($v < $resolved_status_threshold) {
		if (!array_key_exists($v, $times)) {
			$data_table_print .= "<td class='right'>&nbsp;</td>";
		} else {
			$data_table_print .= "<td class='right'>" . $times[$v] . "</td>";
		}
	}
}


// fetching average pending
$times = array();

foreach ($status_values as $key => $val) {
	if ( $val >= $resolved_status_threshold ) { continue; }

    	$query = "
            SELECT SUM(UNIX_TIMESTAMP() - date_submitted) as waiting_time, count(*) as num_of_entries
            FROM $mantis_bug_table
            WHERE $specific_where
            AND date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
            AND date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
            AND status = $val
            ORDER BY waiting_time desc
            LIMIT 1
            ";
    $result = $db->GetAll( $query );
    $row = $result[0];

	if ($row['waiting_time'] == NULL || $row['num_of_entries'] == 0) { continue; }

	$waiting_time = round($row['waiting_time']/$row['num_of_entries']);

	if ( $waiting_time > $one_day ) { // days

		$days			= floor($waiting_time/$one_day);
		$hours		= floor(($waiting_time - $days*$one_day)/$one_hour);
		$minutes	= floor(($waiting_time - $days*$one_day - $hours*$one_hour)/$one_minute);
		$seconds	= $waiting_time - $days*$one_day - $hours*$one_hour - $minutes*$one_minute;

		$times[$val] = $days . "d.&nbsp;" . $hours . ":" . add_zero($minutes) . ":" . add_zero($seconds);

	} elseif ( $waiting_time > 60*60 ) { // hours

		$hours		= floor($waiting_time/$one_hour);
		$minutes	= floor(($waiting_time - $hours*$one_hour)/$one_minute);
		$seconds	= $waiting_time - $hours*$one_hour - $minutes*$one_minute;

		$times[$val] = $hours . ":" . add_zero($minutes) . ":" . add_zero($seconds);

	} elseif ( $waiting_time > 60 ) { // minutes

		$minutes	= floor($waiting_time/$one_minute);
		$seconds	= $waiting_time - $minutes*$one_minute;

		$times[$val] = add_zero($minutes) . ":" . add_zero($seconds);

	} elseif ( $waiting_time > 0) { //seconds

		$times[$val] = add_zero($seconds);
	}
}

// table rows below heading
$data_table_print .= "<tr " . helper_alternate_class() . "><td>" . lang_get( 'plugin_MantisStats_average_time') . "</td>";

foreach ($status_values as $k => $v) {
	if ($v < $resolved_status_threshold) {
		if (!array_key_exists($v, $times)) {
			$data_table_print .= "<td class='right'>&nbsp;</td>";
		} else {
			$data_table_print .= "<td class='right'>" . $times[$v] . "</td>";
		}
	}
}


$data_table_print .= "</tr></table>";

$data_table_print_open = $data_table_print;



// table heading - issue states [ open | resolved ]
$data_table_print = "<table class='width100' cellspacing='1'><tr><td width='100%' class='form-title'>" . lang_get( 'plugin_MantisStats_waiting_times' ) . "</td>";

foreach ($status_values as $key => $val) {
	if ($val >= $resolved_status_threshold) {
		$data_table_print .= "<td class='heading'>" . MantisEnum::getLabel($status_enum_string, $val) . "</td>";
	}
}
$data_table_print .= "</tr>";


// fetching longest pending
$times = array();

foreach ($status_values as $key => $val) {
	if ( $val < $resolved_status_threshold ) { continue; }

	$query = "
            SELECT h.date_modified - b.date_submitted as waiting_time
            FROM $mantis_bug_table b
            LEFT JOIN $mantis_bug_history_table h
            ON b.id = h.bug_id
            WHERE $specific_where
            AND h.type = " . NORMAL_TYPE . "
            AND h.field_name = 'status'
            AND h.old_value < $resolved_status_threshold
            AND h.new_value >= $resolved_status_threshold
            AND b.date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
            AND b.date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
            AND b.status = $val
            ORDER BY waiting_time desc
            LIMIT 1
            ";
    $result = $db->GetAll( $query );

    if ( isset( $result[0] ) ) {

        $row = $result[0];

	    $waiting_time = $row['waiting_time'];

	    if ( $waiting_time > $one_day ) { // days

		    $days			= floor($waiting_time/$one_day);
		    $hours		= floor(($waiting_time - $days*$one_day)/$one_hour);
		    $minutes	= floor(($waiting_time - $days*$one_day - $hours*$one_hour)/$one_minute);
		    $seconds	= $waiting_time - $days*$one_day - $hours*$one_hour - $minutes*$one_minute;

		    $times[$val] = $days . "d.&nbsp;" . $hours . ":" . add_zero($minutes) . ":" . add_zero($seconds);

	    } elseif ( $waiting_time > 60*60 ) { // hours

		    $hours		= floor($waiting_time/$one_hour);
		    $minutes	= floor(($waiting_time - $hours*$one_hour)/$one_minute);
		    $seconds	= $waiting_time - $hours*$one_hour - $minutes*$one_minute;

		    $times[$val] = $hours . ":" . add_zero($minutes) . ":" . add_zero($seconds);

	    } elseif ( $waiting_time > 60 ) { // minutes

		    $minutes	= floor($waiting_time/$one_minute);
		    $seconds	= $waiting_time - $minutes*$one_minute;

		    $times[$val] = add_zero($minutes) . ":" . add_zero($seconds);

	    } elseif ( $waiting_time > 0) { //seconds

		    $times[$val] = add_zero($seconds);
	    }
    }
}

// table rows below heading
$data_table_print .= "<tr " . helper_alternate_class() . "><td>" . lang_get( 'plugin_MantisStats_longest_time') . "</td>";

foreach ($status_values as $k => $v) {
	if ($v >= $resolved_status_threshold) {
		if (!array_key_exists($v, $times)) {
			$data_table_print .= "<td class='right'>&nbsp;</td>";
		} else {
			$data_table_print .= "<td class='right'>" . $times[$v] . "</td>";
		}
	}
}


// fetching average pending
$times = array();

foreach ($status_values as $key => $val) {
	if ( $val < $resolved_status_threshold ) { continue; }

	$query = "
            SELECT SUM(h.date_modified - b.date_submitted) as waiting_time, count(*) as num_of_entries
            FROM $mantis_bug_table b
            LEFT JOIN $mantis_bug_history_table h
            ON b.id = h.bug_id
            WHERE $specific_where
            AND h.type = " . NORMAL_TYPE . "
            AND h.field_name = 'status'
            AND h.old_value < $resolved_status_threshold
            AND h.new_value >= $resolved_status_threshold
            AND b.date_submitted >= " . $db->qstr( $db_datetimes['start'] ) . "
            AND b.date_submitted <= " . $db->qstr( $db_datetimes['finish'] ) . "
            AND b.status = $val
            ORDER BY waiting_time desc
            LIMIT 1
            ";
    $result = $db->GetAll( $query );
    $row = $result[0];

	if ($row['waiting_time'] == NULL || $row['num_of_entries'] == 0) { continue; }

	$waiting_time = round($row['waiting_time']/$row['num_of_entries']);

	if ( $waiting_time > $one_day ) { // days

		$days			= floor($waiting_time/$one_day);
		$hours		= floor(($waiting_time - $days*$one_day)/$one_hour);
		$minutes	= floor(($waiting_time - $days*$one_day - $hours*$one_hour)/$one_minute);
		$seconds	= $waiting_time - $days*$one_day - $hours*$one_hour - $minutes*$one_minute;

		$times[$val] = $days . "d.&nbsp;" . $hours . ":" . add_zero($minutes) . ":" . add_zero($seconds);

	} elseif ( $waiting_time > 60*60 ) { // hours

		$hours		= floor($waiting_time/$one_hour);
		$minutes	= floor(($waiting_time - $hours*$one_hour)/$one_minute);
		$seconds	= $waiting_time - $hours*$one_hour - $minutes*$one_minute;

		$times[$val] = $hours . ":" . add_zero($minutes) . ":" . add_zero($seconds);

	} elseif ( $waiting_time > 60 ) { // minutes

		$minutes	= floor($waiting_time/$one_minute);
		$seconds	= $waiting_time - $minutes*$one_minute;

		$times[$val] = add_zero($minutes) . ":" . add_zero($seconds);

	} elseif ( $waiting_time > 0) { //seconds

		$times[$val] = add_zero($seconds);
	}
}

// table rows below heading
$data_table_print .= "<tr " . helper_alternate_class() . "><td>" . lang_get( 'plugin_MantisStats_average_time') . "</td>";

foreach ($status_values as $k => $v) {
	if ($v >= $resolved_status_threshold) {
		if (!array_key_exists($v, $times)) {
			$data_table_print .= "<td class='right'>&nbsp;</td>";
		} else {
			$data_table_print .= "<td class='right'>" . $times[$v] . "</td>";
		}
	}
}


$data_table_print .= "</tr></table>";
$data_table_print_resolved = $data_table_print;

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
            <div id="title"><?php echo lang_get( 'plugin_MantisStats_waiting_times' ); ?></div>
            <div id="scope">&raquo; <?php echo lang_get( 'plugin_MantisStats_project' ); ?>: <?php echo project_get_name( $project_id ); ?></div><div id="sup"><?php if ( $project_id == ALL_PROJECTS ) { echo "<sup>&dagger;</sup>"; } ?></div>
        </div>

        <p class="clear" />

        <div id="filter">
            <strong><?php echo lang_get( 'plugin_MantisStats_timeframe' ); ?></strong>
            <p />
            <form method="get">
                <input type="hidden" name="page" value="MantisStats/issues_by_waitingtimes" />
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
        <?php echo $data_table_print_open; ?>

        <p class="space30Before" />
        <strong><?php echo lang_get( 'plugin_MantisStats_resolved_issues' ); ?></strong>
        <p />
        <?php echo $data_table_print_resolved; ?>

        <p class="space40Before" />
        <?php if ( $project_id == ALL_PROJECTS ) { echo "&dagger; " . lang_get( 'plugin_MantisStats_priv_proj_skip' ); } ?>
        <?php if ( $final_runtime_value == 1 ) { printf( "<p class='graycolor'>" . lang_get( 'plugin_MantisStats_runtime_string' ) . "</p>", round(microtime(true) - $starttime, 5) ); } ?>
    </div>

    <div id="sidebar"><?php echo $sidebar; ?></div>
</div>

<div id="footer"><?php html_page_bottom();?></div>