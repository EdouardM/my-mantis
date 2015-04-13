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


require_once 'common_includes.php';

// if account is anonymous or protected then no changes should be made to the settings
if( user_is_protected( $t_user_id ) ) {
?>

<br />
<div align="center">

<?php
    $t_redirect_url = 'plugin.php?page=MantisStats/config_user';

    html_page_top( null, $t_redirect_url );
    echo '<br />';
    echo "No changes made: protected account" . '<br />';

    print_bracket_link( $t_redirect_url, lang_get( 'proceed' ) );
?>

</div>

<?php
    html_page_bottom();
    exit;
}


if ( FALSE == form_security_validate('config_user') ) { exit; };

// default report setting
if ( isset( $_POST['defaultreport']) and !empty($_POST['defaultreport'] ) ) {
    $t_defaultreport = (int) $_POST['defaultreport'];

    if ( array_key_exists( $t_defaultreport, $reports_list['name'] ) ) {

        $query  = "select * from " . plugin_table( 'misc_config' ) . " where user_id = " . $t_user_id . " and type_misc_config = 1";

        $result = $db->GetAll( $query );

        // if admin has no entries in tbl
        if ( sizeof( $result ) == 0 ) {

            $query = "insert into " . plugin_table( 'misc_config' ) . " (user_id, value_misc_config, type_misc_config) values (" . $t_user_id . ", " . $db->qstr( $t_defaultreport ) . ", 1)";
            $db->Execute( $query );

        } elseif ( sizeof( $result ) == 1 ) {

            $query ="update " . plugin_table( 'misc_config' ) . " set value_misc_config = " . $db->qstr( $t_defaultreport ) . " where user_id = " . $t_user_id . " and type_misc_config = 1";
            $db->Execute( $query );
        }
    }
}

// chart rendering settings
if (isset($_POST['render']) and !empty($_POST['render'])) {
    $t_render = (int) $_POST['render'];

    if (array_key_exists($t_render, $render_values)) {

        $t_user_id = auth_get_current_user_id();

        $query  = "select * from " . plugin_table( 'chart_config' ) . " where user_id = " . $t_user_id;
        $result = $db->GetAll( $query );

        // if user has no entries in tbl
        if ( sizeof( $result ) == 0 ) {

            $query = "insert into " . plugin_table( 'chart_config' ) . " (user_id, chart_config) values (";
            $query .= $t_user_id . ", " . $db->qstr( $t_render ) . ")";
            $db->Execute( $query );

        } elseif ( sizeof( $result ) == 1 ) {

            $query ="update " . plugin_table( 'chart_config' ) . " set chart_config = " . $db->qstr( $t_render ) . " where user_id = " . $t_user_id;
            $db->Execute( $query );

        }
    }
}

// table rows settings
if (isset($_POST['tblrows']) and !empty($_POST['tblrows'])) {
    $t_tblrows = (int) $_POST['tblrows'];

    if (array_key_exists($t_tblrows, $tblrows_values)) {

        $t_user_id = auth_get_current_user_id();

        $query  = "select * from " . plugin_table( 'tblrows_config' ) . " where user_id = " . $t_user_id;
        $result = $db->GetAll( $query );

        // if user has no entries in tbl
        if ( sizeof( $result ) == 0 ) {

            $query ="insert into " . plugin_table( 'tblrows_config' ) . " (user_id, tblrows_config) values (";
            $query .= $t_user_id . ", " . $db->qstr( $t_tblrows ) . ")";
            $db->Execute( $query );

        } elseif ( sizeof( $result ) == 1 ) {

            $query ="update " . plugin_table( 'tblrows_config' ) . " set tblrows_config = " . $db->qstr( $t_tblrows ) . " where user_id = " . $t_user_id;
            $db->Execute( $query );

        }
    }
}
?>

<br />
<div align="center">

<?php
$t_redirect_url = 'plugin.php?page=MantisStats/config_user';

html_page_top( null, $t_redirect_url );
echo '<br />';
echo lang_get( 'operation_successful' ) . '<br />';

print_bracket_link( $t_redirect_url, lang_get( 'proceed' ) );
?>

</div>

<?php html_page_bottom(); ?>
