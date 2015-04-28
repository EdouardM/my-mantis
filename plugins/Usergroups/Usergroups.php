<?php
class UsergroupsPlugin extends MantisPlugin {
 
	function register() {
		$this->name        = 'Usergroups';
		$this->description = lang_get( 'usergroups_description' );
		$this->version     = '1.20';
		$this->requires    = array('MantisCore'       => '1.2.0',);
		$this->author      = 'Cas Nuy';
		$this->contact     = 'Cas-at-nuy.info';
		$this->url         = 'http://www.nuy.info';
		$this->page	       = 'config';	
	}

	function init() { 
		// Allow adding user to one or more groups
		event_declare('EVENT_MANAGE_USER_FORM');
		plugin_event_hook('EVENT_MANAGE_USER_FORM', 'DefUgroup');
		// Delete usergroups when user is deleted
		event_declare('EVENT_ACCOUNT_DELETED');
		plugin_event_hook('EVENT_ACCOUNT_DELETED', 'DelUgroup');
	}

	function config() {
		return array(
			'mail_group'		=> ON,
			);
	}

	
	function DefUgroup(){
		include 'plugins/Usergroups/pages/ugrp_form.php';
	}
	
	function DelUgroup($p_event,$f_user_id){
 		$ugrp_table	= plugin_table('usergroup');
		$sql = "delete from $ugrp_table where user_id=$f_user_id";
		$result		= db_query_bound($sql);
	}

	function schema() {
		return array(
			array( 'CreateTableSQL', array( plugin_table( 'groups' ), "
						group_id 			I       NOTNULL AUTOINCREMENT UNSIGNED PRIMARY,
						group_name			C(10)	DEFAULT NULL ,
						group_desc			C(50)	DEFAULT NULL ,
						group_mail			C(50)	DEFAULT NULL 
						" ) ),
			array( 'CreateTableSQL', array( plugin_table( 'usergroup' ), "
						ugroup_id 			I       NOTNULL AUTOINCREMENT UNSIGNED PRIMARY,
						user_id				I		NOT NULL ,
						group_id			I		NOT NULL 
						" ) ),
		);
	} 
}