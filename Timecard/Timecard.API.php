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

/**
 * Object representation of a bug, and it's associated timecard and hours
 * estimate, as well as calculated information from TimecardUpdate objects.
 */
class TimecardBug {
	private $new = true;

	public $bug_id;
	public $timecard;
	public $estimate;
	public $timestamp;

	public $updates;
	public $spent;

	/**
	 * Create a new TimecardBug object.
	 * @param int Bug ID
	 * @param string Timecard string
	 * @param int Estimate of hours required
	 */
	function __construct( $p_bug_id, $p_timecard='', $p_estimate=-1 ) {
		$this->bug_id = $p_bug_id < 0 ? 0 : $p_bug_id;
		$this->timecard = $this->__timecard = $p_timecard;
		$this->estimate = $this->__estimate = $p_estimate;

		$this->updates = array();
	}

	/**
	 * Load an existing TimecardBug object from the database.
	 * @param int Bug ID
	 * @return object TimecardBug object
	 */
	static function load( $p_bug_id, $p_load_updates=true ) {
		$t_estimate_table = plugin_table( 'estimate', 'Timecard' );

		$t_query = "SELECT * FROM $t_estimate_table WHERE bug_id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $p_bug_id ) );

		if ( db_num_rows( $t_result ) < 1 ) {
			$t_project = TimecardProject::load( bug_get_field( $p_bug_id, 'project_id' ) );
			return new TimecardBug( $p_bug_id, $t_project->timecard, -1 );
		}

		$t_row = db_fetch_array( $t_result );
		$t_estimate = new TimecardBug( $t_row['bug_id'], $t_row['timecard'], $t_row['estimate'] );
		$t_estimate->timestamp = $t_row['timestamp'];
		$t_estimate->new = false;

		if ( $p_load_updates ) {
			$t_estimate->load_updates();
		}

		return $t_estimate;
	}

	/**
	 * Load existing TimecardBug objects from the database for a given project.
	 * @param int Project ID
	 * @param boolean Load TimecardUpdate objects
	 * @return array TimecardBug objects
	 */
	static function load_by_project( $p_project_id, $p_load_updates=true ) {
		if ( $p_load_updates ) {
			$t_updates = TimecardUpdate::load_by_project( $p_project_id );
		} else {
			$t_updates = array();
		}

		$t_bug_table = db_get_table( 'mantis_bug_table' );
		$t_estimate_table = plugin_table( 'estimate', 'Timecard' );

		$t_query = "SELECT * FROM $t_estimate_table WHERE bug_id IN ( SELECT id FROM $t_bug_table WHERE project_id=" . db_param() . ')';
		$t_result = db_query_bound( $t_query, array( $p_project_id ) );

		$t_estimates = array();
		while ( $t_row = db_fetch_array( $t_result ) ) {
			$t_estimate = new TimecardBug( $t_row['bug_id'], $t_row['timecard'], $t_row['estimate'] );
			$t_estimate->new = false;
			$t_estimate->timestamp = $t_row['timestamp'];

			if ( $p_load_updates && isset( $t_updates[ $t_row['bug_id'] ] ) ) {
				$t_estimate->updates = $t_updates[ $t_row['bug_id'] ];
			}
		}

		return $t_estimate;
	}

	/**
	 * Load child update objects.
	 */
	function load_updates() {
		if ( empty( $this->updates ) ) {
			$this->updates = TimecardUpdate::load_by_bug( $this->bug_id );
		}
	}

	/**
	 * Save a TimecardBug object to the database.
	 */
	function save() {
		$t_estimate_table = plugin_table( 'estimate', 'Timecard' );

		if ( $this->new ) {
			$t_query = "INSERT INTO $t_estimate_table ( bug_id, timecard, estimate, timestamp ) VALUES (
				" . db_param() . ',
				' . db_param() . ',
				' . db_param() . ',
				' . db_param() . ' )';
			db_query_bound( $t_query, array(
				$this->bug_id,
				$this->timecard,
				$this->estimate,
				$this->timestamp = db_now(),
			) );
		} else {
			$t_query = "UPDATE $t_estimate_table SET
				timecard=" . db_param() . ',
				estimate=' . db_param() . ',
				timestamp=' . db_param() . '
				WHERE bug_id=' . db_param();
			db_query_bound( $t_query, array(
				$this->timecard,
				$this->estimate,
				$this->timestamp = db_now(),
				$this->bug_id,
			) );
		}

		if ( $this->timecard != $this->__timecard ) {
			if ( $this->__timecard && $this->timecard ) {
				plugin_history_log( $this->bug_id, 'timecard_updated', $this->__timecard, $this->timecard, null, 'Timecard' );

			} else if ( $this->timecard ) {
				plugin_history_log( $this->bug_id, 'timecard_added', $this->timecard, '', null, 'Timecard' );

			} else if ( $this->__timecard ) {
				plugin_history_log( $this->bug_id, 'timecard_removed', $this->__timecard, ' ', null, 'Timecard' );
			}
			$this->__timecard = $this->timecard;
		}

		if ( $this->estimate != $this->__estimate ) {
			if ( $this->estimate >= 0 && $this->__estimate >= 0 ) {
				plugin_history_log( $this->bug_id, 'estimate_updated', $this->__estimate, $this->estimate, null, 'Timecard' );

			} else if ( $this->estimate >= 0 ) {
				plugin_history_log( $this->bug_id, 'estimate_added', $this->estimate, '', null, 'Timecard' );

			} else if ( $this->__estimate >= 0 ) {
				plugin_history_log( $this->bug_id, 'estimate_removed', $this->__estimate, ' ', null, 'Timecard' );
			}
			$this->__estimate = $this->estimate;
		}

		foreach ( $this->updates as $t_update ) {
			if ( $t_update->id == 0 ) {
				$t_update->save();
			}
		}
	}

	/**
	 * Generate the appropriate 'spent' hours from child updates.
	 */
	function calculate() {
		$this->load_updates();

		$this->spent = 0;
		foreach ( $this->updates as $t_update ) {
			$this->spent += $t_update->spent;
		}
	}

}

/**
 * Object representation of a bug update, including the hours spent by a user
 * and the associated bugnote.
 */
class TimecardUpdate {
	public $id;
	public $bug_id;
	public $bugnote_id;
	public $user_id;
	public $timestamp;
	public $spent;

	/**
	 * Create a new TimecardUpdate object.
	 * @param int Bug ID
	 * @param int Bugnote ID
	 * @param int User ID
	 * @param int Hours spent
	 */
	function __construct( $p_bug_id, $p_bugnote_id, $p_user_id, $p_spent=0 ) {
		$this->id = 0;
		$this->bug_id = $p_bug_id < 0 ? 0 : $p_bug_id;
		$this->bugnote_id = $p_bugnote_id < 0 ? 0 : $p_bugnote_id;
		$this->user_id = $p_user_id < 0 ? 0 : $p_user_id;
		$this->spent = $this->__spent = $p_spent < 0 ? 0 : $p_spent;
		$this->timestamp = db_now();
	}

	/**
	 * Load an existing TimecardUpdate object from the database.
	 * @param int Update ID
	 * @return object TimecardUpdate object
	 */
	static function load( $p_id ) {
		$t_update_table = plugin_table( 'update', 'Timecard' );

		$t_query = "SELECT * FROM $t_update_table WHERE id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $p_id ) );

		if ( db_num_rows( $t_result ) < 1 ) {
			trigger_error( ERROR_GENERIC, ERROR );
			return null;
		}

		$t_row = db_fetch_array( $t_result );
		$t_update = new TimecardUpdate( $t_row['bug_id'], $t_row['bugnote_id'], $t_row['user_id'], $t_row['spent'] );
		$t_update->id = $t_row['id'];
		$t_update->timestamp = $t_row['timestamp'];

		return $t_update;
	}

	/**
	 * Load all existing TimecardUpdates object from the database for a specific bug.
	 * @param int Bug ID
	 * @return array TimecardUpdate objects
	 */
	static function load_by_bug( $p_bug_id ) {
		$t_update_table = plugin_table( 'update', 'Timecard' );

		$t_query = "SELECT * FROM $t_update_table WHERE bug_id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $p_bug_id ) );

		$t_updates = array();
		while ( $t_row = db_fetch_array( $t_result ) ) {
			$t_update = new TimecardUpdate( $t_row['bug_id'], $t_row['bugnote_id'], $t_row['user_id'], $t_row['spent'] );
			$t_update->id = $t_row['id'];
			$t_update->timestamp = $t_row['timestamp'];

			$t_updates[] = $t_update;
		}

		return $t_updates;
	}

	/**
	 * Try to load an existing TimecardUpdate object for a given bugnote.
	 * @param int Bugnote ID
	 * @return object TimecardUpdate object, or null if not found
	 */
	static function load_by_bugnote( $p_bugnote_id ) {
		$t_update_table = plugin_table( 'update', 'Timecard' );

		$t_query = "SELECT * FROM $t_update_table WHERE bugnote_id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $p_bugnote_id ) );

		if ( db_num_rows( $t_result ) < 1 ) {
			return null;
		}

		$t_row = db_fetch_array( $t_result );
		$t_update = new TimecardUpdate( $t_row['bug_id'], $t_row['bugnote_id'], $t_row['user_id'], $t_row['spent'] );
		$t_update->id = $t_row['id'];
		$t_update->timestamp = $t_row['timestamp'];

		return $t_update;
	}

	/**
	 * Load all existing TimecardUpdate objects from the database for a specific project.
	 * @param int project_id
	 * @return array TimecardUpdate objects in 2D array by bug ID
	 */
	static function load_by_project( $p_project_id ) {
		$t_bug_table = db_get_table( 'mantis_bug_table' );
		$t_update_table = plugin_table( 'update', 'Timecard' );

		$t_query = "SELECT * FROM $t_update_table WHERE bug_id IN ( SELECT bug_id FROM $t_bug_table WHERE project_id=" . db_param() . ' )';
		$t_result = db_query_bound( $t_query, array( $p_project_id ) );

		$t_updates = array();
		while ( $t_row = db_fetch_array( $t_result ) ) {
			$t_update = new TimecardUpdate( $t_row['bug_id'], $t_row['bugnote_id'], $t_row['user_id'], $t_row['spent'] );
			$t_update->id = $t_row['id'];
			$t_update->timestamp = $t_row['timestamp'];

			if ( !isset( $t_updates[ $t_row['bug_id'] ] ) ) {
				$t_updates[ $t_row['bug_id'] ] = array();
			}

			$t_updates[ $t_row['bug_id'] ][] = $t_update;
		}

		return $t_updates;
	}

	/**
	 * Save a TimecardUpdate object to the database.
	 */
	function save() {
		$t_update_table = plugin_table( 'update', 'Timecard' );
		$t_hours = plugin_lang_get( 'hours' );

		if ( $this->id < 1 ) { #new
			$t_query = "INSERT INTO $t_update_table (
					bug_id,
					bugnote_id,
					user_id,
					timestamp,
					spent
				) VALUES (
					" . db_param() . ',
					' . db_param() . ',
					' . db_param() . ',
					' . db_param() . ',
					' . db_param() . ' )';

			db_query_bound( $t_query, array(
				$this->bug_id,
				$this->bugnote_id,
				$this->user_id,
				$this->timestamp,
				$this->spent,
				) );

			$this->id = db_insert_id( $t_update_table );

			plugin_history_log( $this->bug_id, 'time_spent_added', $this->spent . $t_hours, '', null, 'Timecard' );

		} else { #existing
			$t_query = "UPDATE $t_update_table SET
					bug_id=" . db_param() . ',
					bugnote_id=' . db_param() . ',
					user_id=' . db_param() . ',
					timestamp=' . db_param() . ',
					spent=' . db_param() . '
				WHERE id=' . db_param();

			db_query_bound( $t_query, array(
				$this->bug_id,
				$this->bugnote_id,
				$this->user_id,
				$this->timestamp,
				$this->spent,
				$this->id,
				) );

			if ( $this->spent != $this->__spent ) {
				plugin_history_log( $this->bug_id, 'time_spent_updated', $this->__spent . $t_hours, $this->spent . $t_hours, null, 'Timecard' );
				$this->__spent = $this->spent;
			}
		}
	}

	function delete() {
		$t_update_table = plugin_table( 'update', 'Timecard' );
		$t_hours = plugin_lang_get( 'hours' );

		$t_query = "DELETE FROM $t_update_table WHERE id=" . db_param();
		db_query_bound( $t_query, array( $this->id ) );

		plugin_history_log( $this->bug_id, 'time_spent_removed', $this->__spent . $t_hours, '', null, 'Timecard' );
		$this->id = 0;
	}
}

/**
 * Object representation of a project and it's 'Default Timecard' information.
 */
class TimecardProject {
	private $new = true;

	public $project_id;
	public $timecard;

	public $bugs;

	/**
	 * Create a new TimecardProject object.
	 * @param int Project ID
	 * @param string Default timecard string
	 */
	function __construct( $p_project_id, $p_timecard='' ) {
		$this->project_id = $p_project_id < 0 ? 0 : $p_project_id;
		$this->timecard = $p_timecard;

		$this->bugs = array();
	}

	/**
	 * Load an existing TimecardProject object from the database, or generate
	 * a new object when one doesn't exist.
	 * @param int Project ID
	 * @param boolean Load TimecardBug objects
	 * @return object TimecardProject object
	 */
	static function load( $p_project_id, $p_load_bugs=false ) {
		$t_project_table = plugin_table( 'project', 'Timecard' );

		$t_query = "SELECT * FROM $t_project_table WHERE project_id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $p_project_id ) );

		if ( db_num_rows( $t_result ) < 1 ) {
			return new TimecardProject( $p_project_id );
		}

		$t_row = db_fetch_array( $t_result );
		$t_project = new TimecardProject( $t_row['project_id'], $t_row['timecard'] );
		$t_project->new = false;

		if ( $p_load_bugs ) {
			$t_project->load_bugs( $p_load_bugs );
		}

		return $t_project;
	}

	/**
	 * Load child TimecardBug objects.
	 * @param boolean Load TimecardUpdate objects
	 */
	function load_bugs( $p_load_updates=false ) {
		if ( empty( $this->bugs ) ) {
			$this->bugs = TimecardBug::load_by_project( $this->project_id, $p_load_updates );
		}
	}

	/**
	 * Save a TimecardProject object to the database.
	 */
	function save() {
		$t_project_table = plugin_table( 'project', 'Timecard' );

		if ( $this->new ) {
			$t_query = "INSERT INTO $t_project_table ( project_id, timecard ) VALUES ( " . db_param() . ', ' . db_param() . ')';
			db_query_bound( $t_query, array( $this->project_id, $this->timecard ) );
		} else {
			$t_query = "UPDATE $t_project_table SET timecard=" . db_param() . ' WHERE project_id=' . db_param();
			db_query_bound( $t_query, array( $this->timecard, $this->project_id ) );
		}
	}
}

