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


auth_reauthenticate();
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

html_page_top( lang_get( 'summary_link' ) );
print_manage_menu();

require_once 'common_includes.php';


/* 1. default report */

// let's see if admin has selected default report
$admin_value = 0;

$query  = "select * from " . plugin_table( 'misc_config' ) . " where admin = 10 and type_misc_config = 1"; # 1 - which report is default TODO
$result = $db->GetAll( $query );

if ( sizeof( $result ) == 1 ) {
    $row = $result[0];
    if ( array_key_exists( $row['value_misc_config'], $reports_list['name'] ) ) {
        $admin_value = $row['value_misc_config'];
    }
}

// if admin has default report setting then use it, otherwise use default
if ( $admin_value == 0 ) {
    $final_defaultreport_value = $defaultreport_default;
} elseif ( $admin_value != 0 ) {
    $final_defaultreport_value = $admin_value;
}


/* 2. chart rendering */

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

// if admin has rendering settings use them, otherwise use defaults
if ( $admin_value == 0 ) {
    $final_render_value = $render_default;
} elseif ( $admin_value != 0 ) {
    $final_render_value = $admin_value;
}


/* 3. number of table rows */

// let's see if admin has tables rows settings
$admin_value = 0;

$query  = "select * from " . plugin_table( 'tblrows_config' ) . " where admin = 10";
$result = $db->GetAll( $query );

if ( sizeof( $result ) == 1 ) {
    $row = $result[0];
    if ( array_key_exists( $row['tblrows_config'], $tblrows_values ) ) {
        $admin_value = $row['tblrows_config'];
    }
}

// if admin has tables rows settings use them, otherwise use defaults
if ( $admin_value == 0 ) {
    $final_tblrows_value = $tblrows_default;
} elseif ( $admin_value != 0 ) {
    $final_tblrows_value = $admin_value;
}


/* 4. Run-time of reports */

// let's see if admin has run-time settings
$admin_value = 0;

$query  = "select * from " . plugin_table( 'misc_config' ) . " where admin = 10 and type_misc_config = 2"; # 2 is run-time show/hide
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

?>

<br />

        <div align="center">

            <form action="<?php echo plugin_page( 'config_admin_update' ) ?>" method="post">
            <?php echo form_security_field( 'config_admin' ) ?>
            <table class="width75" cellspacing="1">
            <tr>
                <td colspan="2" class="form-title">

<?php echo plugin_lang_get( 'configuration' ); ?>

                </td>
            </tr>

            <tr class="row-1">
                <td class="category" width="25%"><?php echo lang_get( 'plugin_MantisStats_reports' ); ?></td>
                <td>
                    <strong><?php echo lang_get( 'plugin_MantisStats_default_report'); ?></strong>

                    <p />

<?php
echo "<select name='defaultreport'>";

foreach ($reports_list['grouped'] as $k1 => $v1) {
    foreach ($v1 as $k2 => $v2) {

        if ( $v2 == 17 ) { // Settings
            continue;
        }

        $val = $reports_list['name'][$v2];

        $selected = '';
        if ( $v2 == $final_defaultreport_value ) { $selected = "selected"; }
        if ( $v2 == 1 ) { $val .= " (" . lang_get( 'plugin_MantisStats_recommended' ) . ") "; }

        echo "<option " . $selected . " value='" . $v2 . "' /> [" . $k1 . "]: " . $val . "</option>";
    }
}

echo "</select>";
?>

                    <p />
                </td>
            </tr>

            <tr class="row-2">
                <td class="category" width="25%"><?php echo lang_get( 'plugin_MantisStats_charts' ); ?></td>
                <td>
                    <strong><?php echo lang_get( 'plugin_MantisStats_render_using' ); ?></strong>

                    <p />

<?php
foreach ($render_values as $key => $val) {
    $checked = '';
    if ( $key == $final_render_value ) { $checked = "checked"; }
    if ( $key == 10 ) { $val .= " (" . lang_get( 'plugin_MantisStats_recommended' ) . ") "; }

    echo "<label><input " . $checked . " type='radio' class='padded' name='render' value='" . $key . "' /> " . $val . "</label><br />";
}
?>

                    <p />
                </td>
            </tr>

            <tr class="row-1">
                <td class="category" width="25%"><?php echo lang_get( 'plugin_MantisStats_data_tables' ); ?></td>
                <td>
                    <strong><?php echo lang_get( 'plugin_MantisStats_nrows_intables' ); ?></strong>

                    <p />

<?php
foreach ($tblrows_values as $key => $val) {
    $checked = '';
    if ( $key == $final_tblrows_value ) { $checked = "checked"; }
    if ( $key == 20 ) { $val .= " (" . lang_get( 'plugin_MantisStats_recommended' ) . ") "; }

    echo "<label><input " . $checked . " type='radio' class='padded' name='tblrows' value='" . $key . "' /> " . $val . "</label><br />";
}
?>

                    <p />
                </td>
            </tr>

            <tr class="row-2">
                <td class="category" width="25%"><?php echo lang_get( 'plugin_MantisStats_runtime' ); ?></td>
                <td>
                    <strong><?php echo lang_get( 'plugin_MantisStats_runtime_sh' ); ?></strong>

                    <p />

<?php
foreach ($runtime_values as $key => $val) {
    $checked = '';
    if ( $key == $final_runtime_value ) { $checked = "checked"; }

    echo "<label><input " . $checked . " type='radio' class='padded' name='runtime' value='" . $key . "' /> " . $val . "</label><br />";
}
?>

                    <p />
                </td>
            </tr>

            <tr>
                <td class="left">&nbsp;</td>
                <td><input type="submit" class="button" value="<?php echo lang_get( 'plugin_MantisStats_save_config' ); ?>" /></td>
            </tr>

            </table>

            </form>

        </div>

	</td>
</tr>
</table>

<?php
	html_page_bottom();
?>