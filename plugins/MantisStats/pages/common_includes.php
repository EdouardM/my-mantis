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

$mantis_custom_field_table = db_get_table( 'mantis_custom_field_table' );

function is_session_started() {
    if ( php_sapi_name() !== 'cli' ) {
        if ( version_compare( phpversion(), '5.4.0', '>=' ) ) {
            return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
        } else {
            return session_id() === '' ? FALSE : TRUE;
        }
    }
    return FALSE;
}

if ( is_session_started() === FALSE ) session_start();


define( 'MAX_PAGINATION', 99 );
define( 'MAX_LINES_IN_BAR_CHARTS', 10 );


// DB connection via ADODB
if ( config_get_global( 'dsn', false ) === false ) {
    $db = ADONewConnection( config_get_global( 'db_schema' ) );
    $db->Connect( $g_hostname, $g_db_username, $g_db_password, $g_database_name );
} else {
    $db = ADONewConnection( config_get_global( 'dsn' ) );
}

// TODO: to switch to Mantis's db functions
if( db_is_mysql() ) {
    $db->query( 'SET NAMES UTF8'  );
}


// Need plugin to be properly installed
$t_plugins      = plugin_find_all();
$t_this_plugin  = plugin_get_current();

if ( plugin_needs_upgrade( $t_plugins[$t_this_plugin] ) ) { exit; }


// Need valid user ID and access rights
$t_user_id = auth_get_current_user_id();
access_ensure_global_level( plugin_config_get( 'access_threshold' ) );


// get time from DB
$query = "SELECT UNIX_TIMESTAMP() AS date_now";
$result = $db->GetAll( $query );
$row = $result[0];
$time_now = intval( $row['date_now'] );


/*********
* Setting runtime settings
* Admin settings override defult setting. There are no user settings for this feature.
* Default we recommend is $runtime_default
****/

$runtime_default = 1;

$runtime_values = array(
    1 => lang_get( 'plugin_MantisStats_runtime_show' ),
    2 => lang_get( 'plugin_MantisStats_runtime_hide' )
);

// let's see if admin has runtime settings
$admin_value = 0;

$query  = "select * from " . plugin_table( 'misc_config' ) . " where admin = 10 and type_misc_config = 2";
$result = $db->GetAll( $query );

if ( sizeof( $result ) == 1 ) {
    $row = $result[0];
    if ( array_key_exists( $row['value_misc_config'], $runtime_values ) ) {
        $admin_value = $row['value_misc_config'];
    }
}

// if admin has default runtime setting then use it, otherwise use default
if ( $admin_value == 0 ) {
    $final_runtime_value = $runtime_default;
} elseif ( $admin_value != 0 ) {
    $final_runtime_value = $admin_value;
}

$starttime = microtime(true);


// dates received from form, if any
$dateFrom = '';
if ( isset( $_GET['date-from'] ) and !empty( $_GET['date-from'] ) ) {
    if ( FALSE == form_security_validate('date_picker') ) { exit; };
    $dateFrom = strip_tags( $_GET['date-from'] );
    $_SESSION['sess-date-from'] = $dateFrom;
} elseif ( isset( $_SESSION['sess-date-from'] ) and !empty( $_SESSION['sess-date-from'] ) ) {
    $dateFrom = $_SESSION['sess-date-from'];
}
$dateTo = '';
if ( isset( $_GET['date-to'] ) and !empty( $_GET['date-to'] ) ) {
    if ( FALSE == form_security_validate('date_picker') ) { exit; };
    $dateTo = strip_tags( $_GET['date-to'] );
    $_SESSION['sess-date-to'] = $dateTo;
} elseif ( isset( $_SESSION['sess-date-to'] ) and !empty( $_SESSION['sess-date-to'] ) ) {
    $dateTo = $_SESSION['sess-date-to'];
}


// Returns array with number of 'open' and 'resolved' states.
function count_states() {

    global $status_values, $resolved_status_threshold;
    $count_states = array();
    $count_states['open'] = $count_states['resolved'] = 0;

    foreach ( $status_values as $key => $val ) {
        if ( $val < $resolved_status_threshold ) {
            $count_states['open']++;
        } else {
            $count_states['resolved']++;
        }
    }
    return $count_states;
}


// Returns array with project names. Long project names are cut to $project_name_length characters.
function project_names() {

    global $db, $mantis_project_table, $specific_where;
    $project_name_length = 50;
    $project_names = array();

    $query = "
            SELECT id, name as project_name
            FROM $mantis_project_table
            WHERE " . str_replace( "project_id", "id", $specific_where ) . "
            ";
    $result = $db->GetAll( $query );

    foreach ( $result as $row ) {
       $project_names[$row['id']] = substr( $row['project_name'], 0, $project_name_length );
    }

    return $project_names;
}


// Returns array with tag names. Long tag names are cut to $tag_name_length characters.
function tag_names() {

    global $db, $mantis_tag_table;
    $tag_name_length = 30;
    $tag_names = array();

    $query = "
            SELECT id, name as tag_name
            FROM $mantis_tag_table
            ";
    $result = $db->GetAll( $query );

    foreach ( $result as $row ) {
       $tag_names[$row['id']] = substr( $row['tag_name'], 0, $tag_name_length );
    }

    return $tag_names;
}


// Returns array with category names. Long category names are cut to $category_name_length characters.
function category_names() {

    global $db, $mantis_category_table;
    $category_name_length = 30;
    $category_names = array();

    $query = "
            SELECT id, name as category_name
            FROM $mantis_category_table
            ";
    $result = $db->GetAll( $query );

    foreach ( $result as $row ) {
       $category_names[$row['id']] = substr( $row['category_name'], 0, $category_name_length );
    }

    return $category_names;
}


// Returns array with user names. Long user names are cut to $user_name_length characters.
function user_names() {

    global $db, $mantis_user_table;

    $user_name_length = 50;
    $user_names = array();

    $query = "
            SELECT id, realname, username
            FROM $mantis_user_table
            ";
    $result = $db->GetAll( $query );

    foreach ( $result as $row ) {
        if ( $row['realname'] )     { $tmp = $row['realname']; }
        elseif ( $row['username'] ) { $tmp = $row['username']; }
        else                        { $tmp = lang_get( 'plugin_MantisStats_unknown' ); }

        $user_names[$row['id']] = substr( $tmp, 0, $user_name_length );
    }

    return $user_names;
}


// Returns array with custom field names. Long custom field names are cut to $custom_field_name_length characters.
function custom_field_names() {

    global $db, $mantis_custom_field_table;
    $custom_field_name_length = 30;
    $custom_field_names = array();

    $query = "
            SELECT id, name as custom_field_name
            FROM $mantis_custom_field_table
            ";
    $result = $db->GetAll( $query );

    foreach ( $result as $row ) {
       $custom_field_names[$row['id']] = substr( $row['custom_field_name'], 0, $custom_field_name_length );
    }

    return $custom_field_names;
}


// Return formatted date
function add_zero( $timeval ) {
    if ( $timeval < 10 ) { $timeval = '0' . $timeval; }
    return $timeval;
}

function waitFormat( $waiting_time ) {

    $one_day        = 60*60*24;
    $one_hour       = 60*60;
    $one_minute     = 60;

    $out = "00:00";

    if ( $waiting_time > $one_day ) { // days

        $days       = floor($waiting_time/$one_day);
        $hours      = floor(($waiting_time - $days*$one_day)/$one_hour);
        $minutes    = floor(($waiting_time - $days*$one_day - $hours*$one_hour)/$one_minute);
        $seconds    = $waiting_time - $days*$one_day - $hours*$one_hour - $minutes*$one_minute;

        $out = $days . "d.&nbsp;" . add_zero($hours) . ":" . add_zero($minutes) . ":" . add_zero($seconds);

    } elseif ( $waiting_time > 60*60 ) { // hours

        $hours        = floor($waiting_time/$one_hour);
        $minutes    = floor(($waiting_time - $hours*$one_hour)/$one_minute);
        $seconds    = $waiting_time - $hours*$one_hour - $minutes*$one_minute;

        $out = add_zero($hours) . ":" . add_zero($minutes) . ":" . add_zero($seconds);

    } elseif ( $waiting_time > 60 ) { // minutes

        $minutes    = floor($waiting_time/$one_minute);
        $seconds    = $waiting_time - $minutes*$one_minute;

        $out = add_zero( $minutes ) . ":" . add_zero( $seconds );

    } elseif ( $waiting_time > 0) { //seconds

        $minutes = 0;
        $seconds = $waiting_time;
        $out = add_zero( $minutes ) . ":" . add_zero( $seconds );

    }

    return $out;
}


// granularity
$granularity_items = array();
$defaultGranularity = 3; // default is Monthly

$granularities = array(
    1 => lang_get( 'plugin_MantisStats_daily' ), 2 => lang_get( 'plugin_MantisStats_weekly' ),
    3 => lang_get( 'plugin_MantisStats_monthly' ), 4 => lang_get( 'plugin_MantisStats_yearly' )
);

$selectedGranularity = $defaultGranularity;

if ( isset( $_GET['granularity'] ) and !empty( $_GET['granularity'] ) ) {
    foreach ( $granularities as $k => $v) {
        if ( $k == strip_tags( $_GET['granularity'] ) ) {
            $selectedGranularity = $k;
            $_SESSION['granularity'] = $k;
            break;
        }
    }
} elseif ( isset( $_SESSION['granularity'] ) and !empty( $_SESSION['granularity'] ) ) {
    foreach ( $granularities as $k => $v) {
        if ( $k == $_SESSION['granularity'] ) {
            $selectedGranularity = $k;
            break;
        }
    }
} else { $selectedGranularity = $defaultGranularity; }


// Cleaning dates
function cleanDates( $dateType, $theDate, $startDateDefault = '' ) {

    global $db, $mantis_bug_table;

    if ( @checkdate( substr( $theDate,5,2 ), substr( $theDate,-2 ), substr( $theDate,0,4 ) ) ) { return $theDate; }
    if ( $dateType == 'date-from' ) {
        if ( $startDateDefault == '2weeks' ) {
            return date("Y-m-d", strtotime("-2 weeks"));
        } elseif ( $startDateDefault == 'begOfTimes' ) {
            $query = "
                    SELECT date_submitted
                    FROM $mantis_bug_table
                    ORDER BY date_submitted
                    LIMIT 1
                    ";
            $result = $db->GetAll( $query );
            if ( sizeof( $result ) > 0 ) {
                $row = $result[0];
                return date( "Y", $row['date_submitted'] ) . "-01-01";
            } else {
                return date( "Y" ) . "-01-01";
            }
        }
    }
    return date("Y-m-d");
}


/*********
* Setting pagination settings
* User settings override admin settings, admin settings override defult setting
* Default we recommend is $tblrows_default
****/
$tblrows_default = 20;

$tblrows_values = array(
    10 => "10 " . lang_get( 'plugin_MantisStats_xrows' ),
    20 => "20 " . lang_get( 'plugin_MantisStats_xrows' ),
    30 => "30 " . lang_get( 'plugin_MantisStats_xrows' ),
    40 => "40 " . lang_get( 'plugin_MantisStats_xrows' ),
    50 => "50 " . lang_get( 'plugin_MantisStats_xrows' )
);

$user_value = 0;

$query = "select * from " . plugin_table( 'tblrows_config' ) . " where user_id = " . $t_user_id;
$result = $db->GetAll( $query );

if ( sizeof( $result ) == 1 ) {
    $row = $result[0];
    if ( array_key_exists( $row['tblrows_config'], $tblrows_values ) ) {
        $user_value = $row['tblrows_config'];
    }
}

$admin_value = 0;

$query = "select * from " . plugin_table( 'tblrows_config' ) . " where admin = 10";
$result = $db->GetAll( $query );

if ( sizeof( $result ) == 1 ) {
    $row = $result[0];
    if ( array_key_exists( $row['tblrows_config'], $tblrows_values ) ) {
        $admin_value = $row['tblrows_config'];
    }
}

if ( $user_value == 0 and $admin_value == 0 )       { $final_tblrows_value = $tblrows_default; }
elseif ( $user_value == 0 and $admin_value != 0 )   { $final_tblrows_value = $admin_value; }
elseif ( $user_value != 0 and $admin_value == 0 )   { $final_tblrows_value = $user_value; }
elseif ( $user_value != 0 and $admin_value != 0 )   { $final_tblrows_value = $user_value; }

define('PERPAGE', $final_tblrows_value);

$offset1 = $offset2 = 1;
if ( isset($_GET['offset1']) and !empty($_GET['offset1']) ) {
    $offset1 = strip_tags( $_GET['offset1'] );
    $offset1 = (int) $offset1;
    if ( !in_array( $offset1, range( 1, MAX_PAGINATION ) ) ) { $offset1 = 1; }
}
if ( isset($_GET['offset2']) and !empty($_GET['offset2']) ) {
    $offset2 = strip_tags($_GET['offset2']);
    $offset2 = (int) $offset2;
    if ( !in_array($offset2, range(1,MAX_PAGINATION)) ) { $offset2 = 1; }
}

function pagination ( $max, $whichoffset, $addtolink ) {
    global $offset1, $offset2;

    $perpage = PERPAGE;
    $offset = "offset" . $whichoffset;

    $staticlink = $addtolink;

    if ( $whichoffset == 1 ) {
        $staticlink .= "&amp;offset2=" . $offset2 . "&amp;";
    } else {
        $staticlink .= "&amp;offset1=" . $offset1 . "&amp;";
    }

    // preparing variables
    $pagination = 0;
    $start = 1;
    $toprepend = $tobecontinued = $tmp = "";
    $out = array();

    if ( $max < 1 ) { $max = 1; }
    if ( $$offset < 1 ) { $$offset = 1; }

    // actual number of pages
    $pages = ceil( $max / $perpage );

    // offset cannot be bigger than number of pages
    if ( $$offset > $pages ) { $$offset = 1; }

    // building the pagination
    if ( $pages > 1 ) {
        if ( $$offset > 9 ) {
            $toprepend = "<li>...</li>\n";
            $start = $$offset - 8;
        }
        if ( ($$offset + 8) < $pages ) {
            $end = $$offset + 8;
            $tobecontinued = "<li>...</li>\n";
        } else {
            $end = $pages;
        }
        $pagination = range($start, $end);
    }

    // prepare links
    if ($pagination != 0) {

        $tmp .= "<li class='first'>" . lang_get( 'plugin_MantisStats_paging' ) . "</li>\n";
        $tmp .= $toprepend;

        foreach($pagination as $key => $val) {
            if ( $val == $$offset ) {
                $tmp .= "<li class='current number'>" . $val . "</li>\n";
            } else {
                $tmp .= "<li><a href='" . $staticlink . $offset . "=" . $val . "' class='number'>" . $val . "</a></li>\n";
            }
        }
        $tmp .= $tobecontinued;
    }

    $out['offset']      = $$offset;
    $out['perpage']     = $perpage;
    $out['pagination']  = $tmp;

    return $out;
}


/*********
* Setting chart rendering settings
* User settings override admin settings, admin settings override defult setting
* Default we recommend is $render_default
****/

$render_default = 10;

$render_values = array(
    10 => lang_get( 'plugin_MantisStats_rendering_1' ),
    20 => lang_get( 'plugin_MantisStats_rendering_2' ),
    30 => lang_get( 'plugin_MantisStats_rendering_3' ),
    40 => lang_get( 'plugin_MantisStats_rendering_4' )
);


$user_value = 0;

$query  = "select * from " . plugin_table( 'chart_config' ) . " where user_id = " . $t_user_id;
$result = $db->GetAll( $query );

if ( sizeof( $result ) == 1 ) {
    $row = $result[0];
    if ( array_key_exists( $row['chart_config'], $render_values ) ) {
        $user_value = $row['chart_config'];
    }
}

if ( $user_value == 0 ) {
    $admin_value = 0;

    $query  = "select * from " . plugin_table( 'chart_config' ) . " where admin = 10";
    $result = $db->GetAll( $query );

    if ( sizeof( $result ) == 1 ) {
        $row = $result[0];
        if ( array_key_exists( $row['chart_config'], $render_values ) ) {
            $admin_value = $row['chart_config'];
        }
    }

    if ( $admin_value != 0 ) { $user_value = $admin_value; }
}

if ( $user_value == 0 ) { $user_value = $render_default; }

if ( $user_value == 20 )       { $render_print_js = "FusionCharts.setCurrentRenderer('flash');"; }
elseif ( $user_value == 30 )   { $render_print_js = "FusionCharts.setCurrentRenderer('javascript');"; }
elseif ( $user_value == 40 )   { $render_print_js = "no_render"; }
else                           { $render_print_js = ''; }


// if chart rendering permitted, print JavaScript stuff
if ( $render_print_js != "no_render" ) { ?>

        <script type="text/javascript" src="<?php echo plugin_file('FusionCharts.js'); ?>"></script>
        <script type="text/javascript">
        // <![CDATA[
        function FC_Rendered(DOMId) {
            var divRef = document.getElementById("unableDiv1");
            divRef.innerHTML = "";
            var divRef = document.getElementById("unableDiv2");
            divRef.innerHTML = "";
        }
        // ]]>
        </script>

<?php
}


/*********
* Sidebar with logo
****/
$defaultreport_default = 1;

$reports_list['name'] = array(
    1  => lang_get( 'plugin_MantisStats_by_project' ),
    2  => lang_get( 'plugin_MantisStats_by_status' ),
    3  => lang_get( 'plugin_MantisStats_by_severity' ),
    4  => lang_get( 'plugin_MantisStats_by_priority' ),
    5  => lang_get( 'plugin_MantisStats_by_resol' ),
    6  => lang_get( 'plugin_MantisStats_by_category' ),
    8  => lang_get( 'plugin_MantisStats_monitoring_stats' ),
    9  => lang_get( 'plugin_MantisStats_handler_stats' ),
    10 => lang_get( 'plugin_MantisStats_notes_stats' ),
    11 => lang_get( 'plugin_MantisStats_handlers_current' ),
    12 => lang_get( 'plugin_MantisStats_handlers_all' ),
    13 => lang_get( 'plugin_MantisStats_reporters' ),
    14 => lang_get( 'plugin_MantisStats_monitors' ),
    15 => lang_get( 'plugin_MantisStats_notes_stats' ),
    16 => lang_get( 'plugin_MantisStats_open_vs_res' ),
    17 => lang_get( 'plugin_MantisStats_tag_stats' ),
    18 => lang_get( 'plugin_MantisStats_gen_settigs' ),
    19 => lang_get( 'plugin_MantisStats_by_reprod' ),
    20 => lang_get( 'plugin_MantisStats_notes_stats' ),
    21 => lang_get( 'plugin_MantisStats_by_custom_fields'),
    22 => lang_get( 'plugin_MantisStats_time_new_pr_sv'),
    23 => lang_get( 'plugin_MantisStats_assigned_time')
);

$reports_list['filename'] = array(
    1  => 'issues_by_projects',
    2  => 'issues_by_status',
    3  => 'issues_by_severity',
    4  => 'issues_by_priority',
    5  => 'issues_by_resolution',
    6  => 'issues_by_category',
    8  => 'issues_by_monitors',
    9  => 'issues_by_handlers',
    10 => 'issues_by_notes',
    11 => 'people_by_handlers',
    12 => 'people_by_handlers_all',
    13 => 'people_by_reporters',
    14 => 'people_by_monitors',
    15 => 'people_by_notes',
    16 => 'trends_by_open_resolved',
    17 => 'issues_by_tags',
    18 => 'config_user',
    19 => 'issues_by_reproducibility',
    20 => 'trends_by_notes',
    21 => 'issues_by_custom_fields',
    22 => 'time_new_by_priority_severity',
    23 => 'people_assigned_time'
);

$reports_list['grouped'] = array(
    lang_get( 'plugin_MantisStats_issues' )        => array(1,2,19,3,4,5,6,21,8,9,10,17),
    lang_get( 'plugin_MantisStats_people' )        => array(11,12,13,14,15,23),
    lang_get( 'plugin_MantisStats_times' )         => array(22),
    lang_get( 'plugin_MantisStats_trends' )        => array(16,20),
    lang_get( 'plugin_MantisStats_configuration' ) => array(18)
);


$sidebar = "
        <a href='" . plugin_page( 'start' ) . "'><img src='" . plugin_file('images/MantisStatsLogo.png') . "' width='200' height='70' alt='MantisStats' /></a>
        <p class='space40Before' />
";


$current_page = $current_page_clean = '';

if ( isset( $_REQUEST['page'] ) and !empty( $_REQUEST['page'] ) ) {
    $current_page = strip_tags( $_REQUEST['page'] );
}

foreach ( $reports_list['grouped'] as $k1 => $v1 ) {

    $sidebar .= "<p class='space30Before'><strong>" . ucfirst( $k1 ) . "</strong>";

    foreach ( $v1 as $k2 => $v2 ) {
        if ( $v2 == 16 and !access_has_global_level( config_get( 'tag_view_threshold' ) ) ) { // Tags
            continue;
        }

        if ( $reports_list['filename'][$v2] != substr( $current_page, strlen( plugin_lang_get( 'title' ) ) + 1 ) ) {
            $sidebar .= "<p class='spaceMin10Before' /><a href='" . plugin_page( $reports_list['filename'][$v2] ) . "'>" . $reports_list['name'][$v2] . "</a>\n";
        } else {
            $sidebar .= "<p class='spaceMin10Before' />" . $reports_list['name'][$v2] . "\n";
            $current_page_clean = $reports_list['filename'][$v2];
        }
    }
}

?>