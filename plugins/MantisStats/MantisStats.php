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


class MantisStatsPlugin extends MantisPlugin {

    // Plugin definition
	function register() {
		$this->name         = lang_get( 'plugin_MantisStats_title' );
		$this->description  = lang_get ( 'plugin_MantisStats_description' );
		$this->page         = 'config_admin';

		$this->version      = '1.5.2';
		$this->requires     = array('MantisCore' => '1.2.0');

		$this->author       = 'Avetis Avagyan';
		$this->contact      = 'plugin.support@mantisstats.org';
		$this->url          = 'https://www.mantisstats.org';
	}

    // Plugin configuration
	function config() {
        // MantiStats menu location: 'EVENT_MENU_SUMMARY' or 'EVENT_MENU_MAIN'
		return array(
            'menu_location'     => 'EVENT_MENU_SUMMARY',
            'access_threshold'  => VIEWER
		);
	}

    // Creating chart_config table to store admin/user settings for charts
    function schema() {
        return array(
            array( 'CreateTableSQL', array( plugin_table( 'chart_config' ), "
                id                  I       NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
                user_id             I       DEFAULT NULL UNSIGNED,
                project_id          I       DEFAULT NULL UNSIGNED,
                report_id           I       DEFAULT NULL UNSIGNED,
                admin               I       DEFAULT NULL UNSIGNED,
                chart_config        I       DEFAULT NULL UNSIGNED
                " )
            ),
            array( 'CreateTableSQL', array( plugin_table( 'tblrows_config' ), "
                id                  I       NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
                user_id             I       DEFAULT NULL UNSIGNED,
                project_id          I       DEFAULT NULL UNSIGNED,
                report_id           I       DEFAULT NULL UNSIGNED,
                admin               I       DEFAULT NULL UNSIGNED,
                tblrows_config      I       DEFAULT NULL UNSIGNED
                " )
            ),
            array( 'CreateTableSQL', array( plugin_table( 'misc_config' ), "
                id                  I       NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
                user_id             I       DEFAULT NULL UNSIGNED,
                project_id          I       DEFAULT NULL UNSIGNED,
                report_id           I       DEFAULT NULL UNSIGNED,
                admin               I       DEFAULT NULL UNSIGNED,
                value_misc_config   I       DEFAULT NULL UNSIGNED,
                type_misc_config    I       DEFAULT NULL UNSIGNED
                " )
            ),
        );
    }

    // Plugin hooks
    function hooks() {
        $tmp = self::config();

        return array(
            $tmp['menu_location'] => 'showreport_menu',
            'EVENT_LAYOUT_RESOURCES'    => 'resources',
        );
    }

    // Loading needed styles and javascripts
    function resources() {
        if ( is_page_name( 'plugin.php' ) ) {
            return
                "
                    <link rel='stylesheet' type='text/css' href='" . plugin_file( 'main.css?v1.5.1' ) . "'>
                    <link rel='stylesheet' type='text/css' href='" . plugin_file( 'jquery-ui.css' ) . "'>

                    <script type='text/javascript' src='" . plugin_file( 'jquery-min.js' ) . "'></script>
                    <script type='text/javascript' src='" . plugin_file( 'jquery-ui-min.js' ) . "'></script>
                ";
        }
    }

	function showreport_menu() {
		if ( access_has_global_level( plugin_config_get( 'access_threshold' ) ) ) {
			return array( '<a href="' . plugin_page( 'start' ) . '">' . plugin_lang_get( 'title' ) . '</a>' );
		}
	}

}

?>