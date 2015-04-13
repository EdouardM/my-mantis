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

// let's see if user has default report settings
$user_value = 0;

$query  = "select * from " . plugin_table( 'misc_config' ) . " where user_id = " . $t_user_id . " and type_misc_config = 1";
$result = $db->GetAll( $query );

if ( sizeof( $result ) == 1 ) {
    $row = $result[0];
    if ( array_key_exists( $row['value_misc_config'], $reports_list['name'] ) ) {
        $user_value = $row['value_misc_config'];
    }
}

// let's see if admin has default report settings
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
} elseif ( $user_value == 0 and $admin_value != 0 ) {
    $final_defaultreport_value = $admin_value;
} elseif ( $user_value != 0 and $admin_value == 0 ) {
    $final_defaultreport_value = $user_value;
} elseif ( $user_value != 0 and $admin_value != 0 ) {
    $final_defaultreport_value = $user_value;
}

print_successful_redirect( plugin_page( $reports_list['filename'][$final_defaultreport_value], true ) );

?>