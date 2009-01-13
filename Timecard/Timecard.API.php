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

	public $updates;

	/**
	 * Create a new TimecardBug object.
	 * @param int Bug ID
	 * @param string Timecard string
	 * @param int Estimate of hours required
	 */
	function __construct( $p_bug_id, $p_timecard='', $p_estimate=0 ) {
		$this->bug_id = $p_bug_id < 0 ? 0 : $p_bug_id;
		$this->timecard = $p_timecard;
		$this->estimate = $p_estimate < 0 ? 0 : $p_estimate;
	}
}

/**
 * Object representation of a bug update, including the hours spent by a user
 * and the associated bugnote.
 */
class TimecardUpdate {
	private $new = true;

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
	function __construct( $p_bug_id, $p_bugnote_id, $p_user_id, $spent=0 ) {
		$this->bug_id = $p_bug_id < 0 ? 0 : $p_bug_id;
		$this->bugnote_id = $p_bugnote_id < 0 ? 0 : $p_bugnote_id;
		$this->user_id = $p_user_id < 0 ? 0 : $p_user_id;
		$this->spent = $p_spent < 0 ? 0 : $p_spent;
	}
}

/**
 * Object representation of a project and it's 'Default Timecard' information.
 */
class TimecardProject {
	private $new = true;

	public $project_id;
	public $timecard;

	/**
	 * Create a new TimecardProject object.
	 * @param int Project ID
	 * @param string Default timecard string
	 */
	function __construct( $p_project_id, $p_timecard='' ) {
		$this->project_id = $p_project_id < 0 ? 0 : $p_project_id;
		$this->timecard = $p_timecard;
	}

	/**
	 * Load an existing TimecardProject object from the database, or generate
	 * a new object when one doesn't exist.
	 * @param int Project ID
	 * @return object TimecardProject object
	 */
	static function load( $p_project_id ) {
		$t_project_table = plugin_table( 'project' );

		$t_query = "SELECT * FROM $t_project_table WHERE project_id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $p_project_id ) );

		if ( db_num_rows( $t_result ) < 1 ) {
			return new TimecardProject( $p_project_id );
		}

		$t_row = db_fetch_array( $t_result );
		$t_project = new TimecardProject( $t_row['project_id'], $t_row['timecard'] );
		$t_project->new = false;

		return $t_project;
	}

	/**
	 * Save a TimecardProject object to the database.
	 */
	function save() {
		$t_project_table = plugin_table( 'project' );

		if ( $this->new ) {
			$t_query = "INSERT INTO $t_project_table ( project_id, timecard ) VALUES ( " . db_param() . ', ' . db_param() . ')';
			db_query_bound( $t_query, array( $this->project_id, $this->timecard ) );
		} else {
			$t_query = "UPDATE $t_project_table SET timecard=" . db_param() . ' WHERE project_id=' . db_param();
			db_query_bound( $t_query, array( $this->timecard, $this->project_id ) );
		}
	}
}

