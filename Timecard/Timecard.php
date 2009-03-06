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

			'use_updates' => ON,
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
			'EVENT_VIEW_BUG_EXTRA' => 'view_bug_extra',
			'EVENT_VIEW_BUGNOTES_START' => 'view_bugnotes_start',
			'EVENT_VIEW_BUGNOTE' => 'view_bugnote',

			'EVENT_BUGNOTE_ADD_FORM' => 'bugnote_add_form',
			'EVENT_BUGNOTE_ADD' => 'bugnote_add',
			'EVENT_BUGNOTE_EDIT_FORM' => 'bugnote_edit_form',
			'EVENT_BUGNOTE_EDIT' => 'bugnote_edit',

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
		if ( !access_has_project_level( plugin_config_get( 'estimate_threshold' ), $p_project_id ) ) {
			return;
		}

		echo '<tr ', helper_alternate_class(), '><td class="category">', plugin_lang_get( 'estimate' ),
			'<input type="hidden" name="plugin_timecard" value="1"/>',
			'</td><td><input name="plugin_timecard_estimate" size="8" maxlength="64"/>',
			plugin_lang_get( 'hours' ), '</td></tr>';

		if ( plugin_config_get( 'use_timecard' ) ) {
			$t_project = TimecardProject::load( $p_project_id );

			echo '<tr ', helper_alternate_class(), '><td class="category">', plugin_lang_get( 'timecard' ),
				'</td><td><input name="plugin_timecard_string" value="', $t_project->timecard, '" size="15" maxlength="64"/></td></tr>';
		}
	}

	/**
	 * Process form information when a bug is reported.
	 * @param string Event name
	 * @param object Bug Data
	 * @param int Bug ID
	 */
	function report_bug( $p_event, $p_data, $p_bug_id ) {
		if ( !access_has_bug_level( plugin_config_get( 'estimate_threshold' ), $p_bug_id ) ) {
			return;
		}

		if ( ! gpc_get_bool( 'plugin_timecard', 0 ) ) {
			return;
		}

		$t_bug = new TimecardBug( $p_bug_id );
		$t_estimate = gpc_get_string( 'plugin_timecard_estimate', '' );

		if ( is_blank( $t_estimate ) ) {
			$t_bug->estimate = -1;
		} else {
			$t_ebug->stimate = gpc_get_int( 'plugin_timecard_estimate', 0 );
		}

		if ( plugin_config_get( 'use_timecard' ) ) {
			$t_bug->timecard = gpc_get_string( 'plugin_timecard_string', '' );
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
		if ( !access_has_bug_level( plugin_config_get( 'estimate_threshold' ), $p_bug_id ) ) {
			return;
		}

		$t_bug = TimecardBug::load( $p_bug_id, true );

		echo '<tr ', helper_alternate_class(), '><td class="category">', plugin_lang_get( 'estimate' ),
			'<input type="hidden" name="plugin_timecard" value="1"/>',
			'</td><td><input name="plugin_timecard_estimate" value="', ( $t_bug->estimate < 0 ? '' : $t_bug->estimate ),
			'" size="8" maxlength="64"/>', plugin_lang_get( 'hours' ), '</td>';

		if ( plugin_config_get( 'use_timecard' ) ) {
			echo '<td class="category">', plugin_lang_get( 'timecard' ),
				'</td><td><input name="plugin_timecard_string" value="', $t_bug->timecard,
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
		if ( !access_has_bug_level( plugin_config_get( 'estimate_threshold' ), $p_bug_id ) ) {
			return;
		}

		if ( ! gpc_get_bool( 'plugin_timecard', 0 ) ) {
			return;
		}

		$t_bug = TimecardBug::load( $p_bug_id, true );
		$t_estimate = gpc_get_string( 'plugin_timecard_estimate', '' );

		if ( !is_numeric( $t_estimate ) ) {
			$t_bug->estimate = -1;
		} else {
			$t_bug->estimate = gpc_get_int( 'plugin_timecard_estimate', 0 );
		}

		if ( plugin_config_get( 'use_timecard' ) ) {
			$t_bug->timecard = gpc_get_string( 'plugin_timecard_string', '' );
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
		if ( !access_has_bug_level( plugin_config_get( 'view_threshold' ), $p_bug_id ) ) {
			return;
		}

		$t_bug = TimecardBug::load( $p_bug_id, true );
		$t_bug->calculate();

		$t_columns = 6;
		echo '<tr ', helper_alternate_class(), '>';

		echo '<td class="category">', plugin_lang_get( 'estimate' ), '</td><td>';

		if ( $t_bug->estimate >= 0 ) {
			if ( plugin_config_get( 'use_updates' ) ) {
				$t_bug->calculate();
				if ( $t_bug->spent > $t_bug->estimate ) {
					echo sprintf( plugin_lang_get( 'estimate_over' ), $t_bug->estimate, $t_bug->spent - $t_bug->estimate );
				} else {
					echo sprintf( plugin_lang_get( 'estimate_display' ), $t_bug->estimate, $t_bug->estimate - $t_bug->spent );
				}
			} else {
				echo $t_bug->estimate, plugin_lang_get( 'hours' );
			}
		} else {
			echo plugin_lang_get( 'estimate_zero' );
		}

		echo '</td>';

		$t_columns -= 2;

		if ( plugin_config_get( 'use_timecard' ) ) {
			echo '<td class="category">', plugin_lang_get( 'timecard' ), '</td><td>',
				string_display_line( $t_bug->timecard ), '</td>';
			$t_columns -= 2;
		}

		if ( $t_columns > 0 ) {
			echo '<td colspan="', $t_columns, '"></td>';
		}

		echo '</tr>';
	}

	/**
	 * Show form to update time spent on a bug, separate from a bugnote.
	 * @param string Event name
	 * @param int Bug ID
	 */
	function view_bug_extra( $p_event, $p_bug_id ) {
		if ( !plugin_config_get( 'use_updates' ) ||
			!access_has_bug_level( plugin_config_get( 'update_threshold' ), $p_bug_id ) ) {
			return;
		}

		echo '<br/><form action="', plugin_page( 'log_time' ), '" method="post">',
			form_security_field( 'plugin_Timecard_log_time' ),
			'<table class="width50" cellspacing="1" align="center"><tr><td class="form-title">',
			plugin_lang_get( 'log_time_spent' ), '</td></tr><tr ', helper_alternate_class(), '><td class="category">',
			plugin_lang_get( 'time_spent' ), '</td><td><input type="hidden" name="bug_id" value="', $p_bug_id, '"/>',
			'<input name="spent" value="0" size="6"/>', plugin_lang_get( 'hours' ), '</td></tr>',
			'<tr><td class="center" colspan="2"><input type="submit" value="', plugin_lang_get( 'log_time_spent' ), '"/></td></tr>',
			'</table></form>';
	}

	/**
	 * Generate and cache a dict of TimecardUpdate objects keyed by bugnote ID.
	 * @param string Event name
	 * @param int Bug ID
	 */
	function view_bugnotes_start( $p_event, $p_bug_id ) {
		$this->update_cache = array();

		if ( !plugin_config_get( 'use_updates' ) ||
			!access_has_bug_level( plugin_config_get( 'view_threshold' ), $p_bug_id ) ) {
			return;
		}

		$t_updates = TimecardUpdate::load_by_bug( $p_bug_id );

		foreach( $t_updates as $t_update ) {
			$this->update_cache[ $t_update->bugnote_id ] = $t_update;
		}
	}

	/**
	 * Show any available TimecardUpdate objects with their associated bugnotes.
	 * @param string Event name
	 * @param int Bug ID
	 * @param int Bugnote ID
	 * @param boolean Private note
	 */
	function view_bugnote( $p_event, $p_bug_id, $p_bugnote_id, $p_private ) {
		if ( isset( $this->update_cache[ $p_bugnote_id ] ) ) {
			$t_update = $this->update_cache[ $p_bugnote_id ];
			$t_css = $p_private ? 'bugnote-private' : 'bugnote-public';
			$t_css2 = $p_private ? 'bugnote-note-private' : 'bugnote-note-public';

			echo '<tr class="bugnote"><td class="', $t_css, '">', plugin_lang_get( 'time_spent' ),
				'</td><td class="', $t_css2, '">', $t_update->spent, plugin_lang_get( 'hours' ), '</td></tr>';
		}
	}

	/**
	 * Show appropriate forms for updating time spent.
	 * @param string Event name
	 * @param int Bug ID
	 */
	function bugnote_add_form( $p_event, $p_bug_id ) {
		if ( !plugin_config_get( 'use_updates' ) ||
			!access_has_bug_level( plugin_config_get( 'update_threshold' ), $p_bug_id ) ) {
			return;
		}

		echo '<tr ', helper_alternate_class(), '><td class="category">', plugin_lang_get( 'time_spent' ),
			'</td><td><input name="plugin_timecard_spent" value="0" size="6"/>', plugin_lang_get( 'hours' ), '</td></tr>';
	}

	/**
	 * Process form data when bugnotes are added.
	 * @param string Event name
	 * @param int Bug ID
	 * @param int Bugnote ID
	 */
	function bugnote_add( $p_event, $p_bug_id, $p_bugnote_id ) {
		if ( !plugin_config_get( 'use_updates' ) ||
			!access_has_bug_level( plugin_config_get( 'update_threshold' ), $p_bug_id ) ) {
			return;
		}

		$f_spent = gpc_get_int( 'plugin_timecard_spent', 0 );
		if ( $f_spent > 0 ) {
			$t_update = new TimecardUpdate( $p_bug_id, $p_bugnote_id, auth_get_current_user_id(), $f_spent );
			$t_update->save();
		}
	}

	/**
	 * Show appropriate forms for updating time spent.
	 * @param string Event name
	 * @param int Bug ID
	 * @param int Bugnote ID
	 */
	function bugnote_edit_form( $p_event, $p_bug_id, $p_bugnote_id ) {
		if ( !plugin_config_get( 'use_updates' ) ||
			!access_has_bug_level( plugin_config_get( 'update_threshold' ), $p_bug_id ) ) {
			return;
		}
		
		$t_update = TimecardUpdate::load_by_bugnote( $p_bugnote_id );
		if ( $t_update != null ) {
			echo '<tr ', helper_alternate_class(), '><td class="category">', plugin_lang_get( 'time_spent' ),
				'</td><td><input type="hidden" name="plugin_timecard_id" value="', $t_update->id, '"/>',
				'<input name="plugin_timecard_spent" value="', $t_update->spent, '" size="6"/>', plugin_lang_get( 'hours' ), '</td></tr>';
		}
	}

	/**
	 * Process form data when bugnotes are edited.
	 * @param string Event name
	 * @param int Bug ID
	 * @param int Bugnote ID
	 */
	function bugnote_edit( $p_event, $p_bug_id, $p_bugnote_id ) {
		if ( !plugin_config_get( 'use_updates' ) ||
			!access_has_bug_level( plugin_config_get( 'update_threshold' ), $p_bug_id ) ) {
			return;
		}

		$f_update_id = gpc_get_int( 'plugin_timecard_id', 0 );
		$f_spent = gpc_get_int( 'plugin_timecard_spent', 0 );

		if ( $f_update_id > 0 ) {
			$t_update = TimecardUpdate::load( $f_update_id );

			if ( $f_spent > 0 && $f_spent != $t_update->spent ) {
				$t_update->spent = $f_spent;
				$t_update->save();

			} else if ( $f_spent === 0 ) {
				$t_update->delete();
			}
		}
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
				estimate		I		NOTNULL DEFAULT '-1'
				" ) ),
			array( 'CreateTableSQL', array( plugin_table( 'update' ), "
				id				I		NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
				bug_id			I		NOTNULL UNSIGNED,
				bugnote_id		I		NOTNULL UNSIGNED,
				user_id			I		NOTNULL UNSIGNED,
				timestamp		T		NOTNULL,
				spent			I		NOTNULL UNSIGNED
				" ) ),
		);
	}
}

