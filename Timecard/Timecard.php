<?php
# Copyright (C) 2008	John Reese
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

class TimecardPlugin extends MantisPlugin {

	function register() {
		$this->name = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );

		$this->version = '0.9.0';
		$this->requires		= array(
			'MantisCore' => '1.2.0',
		);

		$this->author		= 'John Reese';
		$this->contact		= 'jreese@leetcode.net';
		$this->url			= 'http://leetcode.net';
	}

	function init() {
		require_once( 'Timecard.API.php' );
	}

	function config() {
		return array(
			'manage_threshold' => ADMINISTRATOR,

			'use_timecard_number' => OFF,
		);
	}

	function hooks() {
		$t_events = array();

		if ( ON == plugin_config_get( 'use_timecard_number', null, true ) ) {
			$t_events['EVENT_MANAGE_PROJECT_CREATE_FORM'] = 'project_create_form';
			$t_events['EVENT_MANAGE_PROJECT_CREATE'] = 'project_create';
			$t_events['EVENT_MANAGE_PROJECT_UPDATE_FORM'] = 'project_update_form';
			$t_events['EVENT_MANAGE_PROJECT_UPDATE'] = 'project_update';
		}

		return $t_events;
	}

	function project_create_form() {
		echo '<tr ', helper_alternate_class(), '><td class="category">', plugin_lang_get( 'default_timecard_number' ),
			'</td><td><input name="plugin_Timecard_number" size="15" maxlength="64"/></td></tr>';
	}

	function project_update_form( $p_project_id ) {
		echo '<tr ', helper_alternate_class(), '><td class="category">', plugin_lang_get( 'default_timecard_number' ),
			'</td><td><input name="plugin_Timecard_number" size="15" maxlength="64"/></td></tr>';
	}

	function project_create( $p_project_id ) {
		$f_default_number = gpc_get_string( 'plugin_Timecard_number', '' );
	}

	function project_update( $p_project_id ) {
		$f_default_number = gpc_get_string( 'plugin_Timecard_number', '' );
	}

	function schema() {
		return array(
			# 2009-01-09 0.9.0
			array( 'CreateTableSQL', array( plugin_table( 'project' ), "
				project_id		I		NOTNULL UNSIGNED PRIMARY,
				timecard		C(64)	NOTNULL DEFAULT \" '' \"
				" ) ),
			array( 'CreateTableSQL', array( plugin_table( 'estimate' ), "
				bug_id			I		NOTNULL UNSIGNED PRIMARY,
				timecard		C(64)	NOTNULL DEFAULT \" '' \",
				estimate		I		NOTNULL UNSIGNED DEFAULT '0'
				" ) ),
			array( 'CreateTableSQL', array( plugin_table( 'update' ), "
				bug_id			I		NOTNULL UNSIGNED,
				bugnote_id		I		NOTNULL UNSIGNED,
				user_id			I		NOTNULL UNSIGNED,
				timestamp		T		NOTNULL,
				spent			I		NOTNULL UNSIGNED
				" ) ),
		);
	}
}

