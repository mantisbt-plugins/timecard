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
		$this->page = 'config_page';

		$this->version = '0.9.0';
		$this->requires		= array(
			'MantisCore' => '1.2.0',
		);

		$this->author		= 'John Reese';
		$this->contact		= 'jreese@leetcode.net';
		$this->url			= 'http://leetcode.net';
	}

	/**
	 * Load the Timecard API.
	 */
	function init() {
		require_once( 'Timecard.API.php' );
	}

	/**
	 * Plugin configuration
	 */
	function config() {
		return array(
			'view_threshold' => VIEWER,
			'estimate_threshold' => DEVELOPER,
			'update_threshold' => DEVELOPER,
			'manage_threshold' => ADMINISTRATOR,

			'use_timecard' => OFF,
		);
	}

	/**
	 * Event hooks.
	 */
	function hooks() {
		return array(
			'EVENT_REPORT_BUG_FORM' => 'report_bug_form',
			'EVENT_REPORT_BUG' => 'report_bug',
			'EVENT_UPDATE_BUG_FORM' => 'update_bug_form',
			'EVENT_UPDATE_BUG' => 'update_bug',
			'EVENT_VIEW_BUG_DETAILS' => 'view_bug',

			'EVENT_MANAGE_PROJECT_CREATE_FORM' => 'project_create_form',
			'EVENT_MANAGE_PROJECT_CREATE' => 'project_update',
			'EVENT_MANAGE_PROJECT_UPDATE_FORM' => 'project_update_form',
			'EVENT_MANAGE_PROJECT_UPDATE' => 'project_update',
		);
	}

	/**
	 * When reporting a bug, show appropriate form elements to the user.
	 * @param string Event name
	 * @param int Project ID
	 * @param boolean Advanced view
	 */
	function report_bug_form( $p_event, $p_project_id, $p_advanced ) {
		if ( plugin_config_get( 'use_timecard' ) ) {
			$t_project = TimecardProject::load( $p_project_id );

			echo '<tr ', helper_alternate_class(), '><td class="category">', plugin_lang_get( 'timecard' ),
				'</td><td><input name="plugin_timecard" value="', $t_project->timecard, '" size="15" maxlength="64"/></td></tr>';
		}

		echo '<tr ', helper_alternate_class(), '><td class="category">', plugin_lang_get( 'estimate' ),
			'</td><td><input name="plugin_timecard_estimate" value="0" size="8" maxlength="64"/>',
			plugin_lang_get( 'estimate_hours' ), '</td></tr>';
	}

	/**
	 * Process form information when a bug is reported.
	 * @param string Event name
	 * @param object Bug Data
	 * @param int Bug ID
	 */
	function report_bug( $p_event, $p_data, $p_bug_id ) {
		$t_bug = new TimecardBug( $p_bug_id );
		$t_bug->estimate = gpc_get_int( 'plugin_timecard_estimate', 0 );

		if ( plugin_config_get( 'use_timecard' ) ) {
			$t_bug->timecard = gpc_get_string( 'plugin_timecard', '' );
		}

		$t_bug->save();
	}

	/**
	 * When updating a bug, show appropriate form elements to the user.
	 * @param string Event name
	 * @param int Bug ID
	 * @param boolean Advanced view
	 */
	function update_bug_form( $p_event, $p_bug_id, $p_advanced ) {
		$t_bug = TimecardBug::load( $p_bug_id, true );

		echo '<tr ', helper_alternate_class(), '><td class="category">', plugin_lang_get( 'estimate' ),
			'</td><td><input name="plugin_timecard_estimate" value="', $t_bug->estimate, '" size="8" maxlength="64"/>',
			plugin_lang_get( 'estimate_hours' ), '</td>';

		if ( plugin_config_get( 'use_timecard' ) ) {
			echo '<td class="category">', plugin_lang_get( 'timecard' ),
				'</td><td><input name="plugin_timecard" value="', $t_bug->timecard,
				'" size="15" maxlength="64"/></td><td colspan="2"></td>';
		} else {
			echo '<td colspan="4"></td>';
		}

		echo '</tr>';
	}

	/**
	 * Process form information when a bug is updated.
	 * @param string Event name
	 * @param object Bug data
	 * @param int Bug ID
	 */
	function update_bug( $p_event, $p_data, $p_bug_id ) {
		$t_bug = TimecardBug::load( $p_bug_id, true );
		$t_bug->estimate = gpc_get_int( 'plugin_timecard_estimate', 0 );

		if ( plugin_config_get( 'use_timecard' ) ) {
			$t_bug->timecard = gpc_get_string( 'plugin_timecard', '' );
		}

		$t_bug->save();
	}

	/**
	 * Show timecard and estimate information when viewing bugs.
	 * @param string Event name
	 * @param int Bug ID
	 * @param boolean Advanced view
	 */
	function view_bug( $p_event, $p_bug_id, $p_advanced ) {
		$t_bug = TimecardBug::load( $p_bug_id, true );
		$t_bug->calculate();

		echo '<tr ', helper_alternate_class(), '><td class="category">', plugin_lang_get( 'estimate' ),
			'</td><td>';

		if ( $t_bug->estimate > 0 ) {
			$t_bug->calculate();
			echo sprintf( plugin_lang_get( 'estimate_display' ), $t_bug->estimate, $t_bug->estimate - $t_bug->spent );
		} else {
			echo plugin_lang_get( 'estimate_zero' );
		}

		echo '</td>';

		if ( plugin_config_get( 'use_timecard' ) ) {
			echo '<td class="category">', plugin_lang_get( 'timecard' ), '</td><td>',
				string_display_line( $t_bug->timecard ), '<td colspan="2"></td>';
		} else {
			echo '<td colspan="4"></td>';
		}

		echo '</tr>';
	}

	/**
	 * When creating a project, optionally show a form element for the
	 * project's default timecard string.
	 */
	function project_create_form( $p_event ) {
		if ( plugin_config_get( 'use_timecard' ) ) {
			echo '<tr ', helper_alternate_class(), '><td class="category">', plugin_lang_get( 'default_timecard' ),
				'</td><td><input name="plugin_timecard" size="15" maxlength="64"/></td></tr>';
		}
	}

	/**
	 * When updating a project, optionally show a form element for the
	 * project's default timecard string.
	 */
	function project_update_form( $p_event, $p_project_id ) {
		if ( plugin_config_get( 'use_timecard' ) ) {
			$t_project = TimecardProject::load( $p_project_id );
			echo '<tr ', helper_alternate_class(), '><td class="category">', plugin_lang_get( 'default_timecard' ),
				'</td><td><input name="plugin_timecard" size="15" maxlength="64" value="', $t_project->timecard,'"/></td></tr>';
		}
	}

	/**
	 * When creating or updating a project, save the given default timecard
	 * string to the database.
	 */
	function project_update( $p_event, $p_project_id ) {
		if ( plugin_config_get( 'use_timecard' ) ) {
			$f_timecard = trim( gpc_get_string( 'plugin_timecard', '' ) );
			$t_project = TimecardProject::load( $p_project_id );

			if ( $t_project->timecard != $f_timecard ) {
				$t_project->timecard = $f_timecard;
				$t_project->save();
			}
		}
	}

	/**
	 * Plugin schema.
	 */
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
				id				I		NOTNULL UNSIGNED PRIMARY,
				bug_id			I		NOTNULL UNSIGNED,
				bugnote_id		I		NOTNULL UNSIGNED,
				user_id			I		NOTNULL UNSIGNED,
				timestamp		T		NOTNULL,
				spent			I		NOTNULL UNSIGNED
				" ) ),
		);
	}
}

