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

require_once 'common_includes.php';

if ( FALSE == form_security_validate('config_admin') ) { exit; };


// runtime setting
if ( isset( $_POST['runtime']) and !empty($_POST['runtime'] ) ) {
    $t_runtime = (int) $_POST['runtime'];

    if ( array_key_exists( $t_runtime, $runtime_values ) ) {

        $query  = "select * from " . plugin_table( 'misc_config' ) . " where admin = 10 and type_misc_config = 2";
        $result = $db->GetAll( $query );

        // if admin has no entries in tbl
        if ( sizeof( $result ) == 0 ) {

            $query = "insert into " . plugin_table( 'misc_config' ) . " (admin, value_misc_config, type_misc_config) values (10, " . $db->qstr( $t_runtime ) . ", 2)";
            $db->Execute( $query );

        } elseif ( sizeof( $result ) == 1 ) {

            $query ="update " . plugin_table( 'misc_config' ) . " set value_misc_config = " . $db->qstr( $t_runtime ) . " where admin = 10 and type_misc_config = 2";
            $db->Execute( $query );
        }
    }
}


// default report setting
if ( isset( $_POST['defaultreport']) and !empty($_POST['defaultreport'] ) ) {
    $t_defaultreport = (int) $_POST['defaultreport'];

    if ( array_key_exists( $t_defaultreport, $reports_list['name'] ) ) {

        $query  = "select * from " . plugin_table( 'misc_config' ) . " where admin = 10 and type_misc_config = 1";
        $result = $db->GetAll( $query );

        // if admin has no entries in tbl
        if ( sizeof( $result ) == 0 ) {

            $query = "insert into " . plugin_table( 'misc_config' ) . " (admin, value_misc_config, type_misc_config) values (10, " . $db->qstr( $t_defaultreport ) . ", 1)";
            $db->Execute( $query );

        } elseif ( sizeof( $result ) == 1 ) {

            $query ="update " . plugin_table( 'misc_config' ) . " set value_misc_config = " . $db->qstr( $t_defaultreport ) . " where admin = 10 and type_misc_config = 1";
            $db->Execute( $query );
        }
    }
}


// chart rendering settings
if (isset($_POST['render']) and !empty($_POST['render'])) {
    $t_render = (int) $_POST['render'];

    if (array_key_exists($t_render, $render_values)) {

        $query  = "select * from " . plugin_table( 'chart_config' ) . " where admin = 10";
        $result = $db->GetAll( $query );

        // if admin has no entries in tbl
        if ( sizeof( $result ) == 0 ) {

            $query = "insert into " . plugin_table( 'chart_config' ) . " (admin, chart_config) values (10, " . $db->qstr( $t_render ) . ")";
            $db->Execute( $query );

        } elseif ( sizeof( $result ) == 1 ) {

            $query ="update " . plugin_table( 'chart_config' ) . " set chart_config = " . $db->qstr( $t_render ) . " where admin = 10";
            $db->Execute( $query );
        }
    }
}

// table rows settings
if (isset($_POST['tblrows']) and !empty($_POST['tblrows'])) {
    $t_tblrows = (int) $_POST['tblrows'];

    if (array_key_exists($t_tblrows, $tblrows_values)) {

        $query  = "select * from " . plugin_table( 'tblrows_config' ) . " where admin = 10";
        $result = $db->GetAll( $query );

        // if admin has no entries in tbl
        if ( sizeof( $result ) == 0 ) {

            $query = "insert into " . plugin_table( 'tblrows_config' ) . " (admin, tblrows_config) values (10, " . $db->qstr( $t_tblrows ) . ")";
            $db->Execute( $query );

        } elseif ( sizeof( $result ) == 1 ) {

            $query ="update " . plugin_table( 'tblrows_config' ) . " set tblrows_config = " . $db->qstr( $t_tblrows ) . " where admin = 10";
            $db->Execute( $query );

        }
    }
}
?>

<br />
<div align="center">

<?php
$t_redirect_url = 'plugin.php?page=MantisStats/config_admin';

html_page_top( null, $t_redirect_url );
echo '<br />';
echo lang_get( 'operation_successful' ) . '<br />';

print_bracket_link( $t_redirect_url, lang_get( 'proceed' ) );
?>

</div>

<?php html_page_bottom(); ?>
