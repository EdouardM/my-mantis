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


$add_string = array();


/* 1. default report */

// let's see if user has rendering settings
$user_value = 0;

$query  = "select * from " . plugin_table( 'misc_config' ) . " where user_id = " . $t_user_id . " and type_misc_config = 1";
$result = $db->GetAll( $query );

if ( sizeof( $result ) == 1 ) {
    $row = $result[0];
    if ( array_key_exists( $row['value_misc_config'], $reports_list['name'] ) ) {
        $user_value = $row['value_misc_config'];
    }
}

// let's see if admin has rendering settings
$admin_value = 0;

$query  = "select * from " . plugin_table( 'misc_config' ) . " where admin = 10 and type_misc_config = 1";
$result = $db->GetAll( $query );

if ( sizeof( $result ) == 1 ) {
    $row = $result[0];
    if ( array_key_exists( $row['value_misc_config'], $reports_list['name'] ) ) {
        $admin_value = $row['value_misc_config'];
    }
}

// all options: --, -+, +-, ++
if ( $user_value == 0 and $admin_value == 0 ) {
    $final_defaultreport_value = $defaultreport_default;
    $add_string['defaultreport'][$defaultreport_default] = " (" . lang_get( 'plugin_MantisStats_recommended' ) . ") ";
} elseif ( $user_value == 0 and $admin_value != 0 ) {
    $final_defaultreport_value = $admin_value;
    $add_string['defaultreport'][$admin_value] = " (" . lang_get( 'plugin_MantisStats_recby_youradmin' ) . ") ";
} elseif ( $user_value != 0 and $admin_value == 0 ) {
    $final_defaultreport_value = $user_value;
    $add_string['defaultreport'][$defaultreport_default] = " (" . lang_get( 'plugin_MantisStats_recommended' ) . ") ";
} elseif ( $user_value != 0 and $admin_value != 0 ) {
    $final_defaultreport_value = $user_value;
    $add_string['defaultreport'][$admin_value] = " (" . lang_get( 'plugin_MantisStats_recby_youradmin' ) . ") ";
}


/* 2. chart rendering */

// let's see if user has rendering settings
$user_value = 0;

$query  = "select * from " . plugin_table( 'chart_config' ) . " where user_id = " . $t_user_id;
$result = $db->GetAll( $query );

if ( sizeof( $result ) == 1 ) {
    $row = $result[0];
    if ( array_key_exists( $row['chart_config'], $render_values ) ) {
        $user_value = $row['chart_config'];
    }
}

// let's see if admin has rendering settings
$admin_value = 0;

$query  = "select * from " . plugin_table( 'chart_config' ) . " where admin = 10";
$result = $db->GetAll( $query );

if ( sizeof( $result ) == 1 ) {
    $row = $result[0];
    if ( array_key_exists( $row['chart_config'], $render_values ) ) {
        $admin_value = $row['chart_config'];
    }
}

// all options: --, -+, +-, ++
if ( $user_value == 0 and $admin_value == 0 ) {
    $final_render_value = $render_default;
    $add_string['render'][$render_default] = " (" . lang_get( 'plugin_MantisStats_recommended' ) . ") ";
} elseif ( $user_value == 0 and $admin_value != 0 ) {
    $final_render_value = $admin_value;
    $add_string['render'][$admin_value] = " (" . lang_get( 'plugin_MantisStats_recby_youradmin' ) . ") ";
} elseif ( $user_value != 0 and $admin_value == 0 ) {
    $final_render_value = $user_value;
    $add_string['render'][$render_default] = " (" . lang_get( 'plugin_MantisStats_recommended' ) . ") ";
} elseif ( $user_value != 0 and $admin_value != 0 ) {
    $final_render_value = $user_value;
    $add_string['render'][$admin_value] = " (" . lang_get( 'plugin_MantisStats_recby_youradmin' ) . ") ";
}


/* 3. table rows settings */

// let's see if user has table rows settings
$user_value = 0;

$query  = "select * from " . plugin_table( 'tblrows_config' ) . " where user_id = " . $t_user_id;
$result = $db->GetAll( $query );

if ( sizeof( $result ) == 1 ) {
    $row = $result[0];
    if ( array_key_exists( $row['tblrows_config'], $tblrows_values ) ) {
        $user_value = $row['tblrows_config'];
    }
}

// let's see if admin has table rows settings
$admin_value = 0;

$query  = "select * from " . plugin_table( 'tblrows_config' ) . " where admin = 10";
$result = $db->GetAll( $query );

if ( sizeof( $result ) == 1 ) {
    $row = $result[0];
    if ( array_key_exists( $row['tblrows_config'], $tblrows_values ) ) {
        $admin_value = $row['tblrows_config'];
    }
}

// all options: --, -+, +-, ++
if ( $user_value == 0 and $admin_value == 0 ) {
    $final_tblrows_value = $tblrows_default;
    $add_string['tblrows'][$tblrows_default] = " (" . lang_get( 'plugin_MantisStats_recommended' ) . ") ";
} elseif ( $user_value == 0 and $admin_value != 0 ) {
    $final_tblrows_value = $admin_value;
    $add_string['tblrows'][$admin_value] = " (" . lang_get( 'plugin_MantisStats_recby_youradmin' ) . ") ";
} elseif ( $user_value != 0 and $admin_value == 0 ) {
    $final_tblrows_value = $user_value;
    $add_string['tblrows'][$tblrows_default] = " (" . lang_get( 'plugin_MantisStats_recommended' ) . ") ";
} elseif ( $user_value != 0 and $admin_value != 0 ) {
    $final_tblrows_value = $user_value;
    $add_string['tblrows'][$admin_value] = " (" . lang_get( 'plugin_MantisStats_recby_youradmin' ) . ") ";
}

?>

<div id="wrapper">

    <div id="databox">
        <div id="titleText">
            <div id="title"><?php echo lang_get( 'plugin_MantisStats_gen_settigs' ); ?></div>
            <div id="scope">&raquo; <?php echo lang_get( 'plugin_MantisStats_project' ); ?>: <?php echo project_get_name( ALL_PROJECTS ); ?></div>
        </div>
        <p class="clear" />
        <p class="space40Before" />

        <form action="<?php echo plugin_page( 'config_user_update' ) ?>" method="post">
            <?php echo form_security_field( 'config_user' ) ?>
            <p class="space40Before" />
            <strong><?php echo lang_get( 'plugin_MantisStats_default_report'); ?></strong>
            <p />

<?php
echo "<select name='defaultreport'>";

foreach ($reports_list['grouped'] as $k1 => $v1) {
    foreach ($v1 as $k2 => $v2) {

        if ( $v2 == 17 or  // Settings
             ( $v2 == 16 and !access_has_global_level( config_get( 'tag_view_threshold' ) ) ) ) { // Tags
            continue;
        }

        $val = $reports_list['name'][$v2];

        $selected = '';
        if ( $v2 == $final_defaultreport_value ) { $selected = "selected"; }
        if ( isset($add_string['defaultreport'][$v2]) ) { $val .= $add_string['defaultreport'][$v2]; }

        echo "<option " . $selected . " value='" . $v2 . "' /> [" . $k1 . "]: " . $val . "</option>";
    }
}

echo "</select>";
?>

            <p class="space40Before" />
            <strong><?php echo lang_get( 'plugin_MantisStats_render_using' ); ?></strong>
            <p />

<?php
// chart rendering
foreach ($render_values as $key => $val) {
    $checked = '';
    if ( $key == $final_render_value ) { $checked = "checked"; }
    if ( isset($add_string['render'][$key]) ) { $val .= $add_string['render'][$key]; }

    echo "<label><input " . $checked . " type='radio' class='padded' name='render' value='" . $key . "' /> " . $val . "</label><br />";
}
?>

            <p class="space40Before" />
            <strong><?php echo lang_get( 'plugin_MantisStats_nrows_intables' ); ?></strong>
            <p />

<?php
// table rows
foreach ($tblrows_values as $key => $val) {
    $checked = '';
    if ( $key == $final_tblrows_value ) { $checked = "checked"; }
    if ( isset($add_string['tblrows'][$key]) ) { $val .= $add_string['tblrows'][$key]; }

    echo "<label><input " . $checked . " type='radio' class='padded' name='tblrows' value='" . $key . "' /> " . $val . "</label><br />";
}
?>

            <p />
            <input type="submit" value="<?php echo lang_get( 'plugin_MantisStats_save_config' ); ?>" />
        </form>

    </div>

    <div id="sidebar"><?php echo $sidebar; ?></div>
</div>

<div id="footer"><?php html_page_bottom();?></div>
