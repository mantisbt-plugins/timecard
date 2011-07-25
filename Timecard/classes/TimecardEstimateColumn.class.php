<?php

# Copyright (C) 2008    John Reese
# Copyright (C) 2011    Reinhard Holler
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

require_once( 'columns_api.php' );

class TimecardEstimateColumn extends MantisColumn {

	public $title = '';

	public $column = "estimate";

	public $sortable = false;

	public function __construct() {
		plugin_push_current( 'Timecard' );

		$this->title = plugin_lang_get( 'estimate' );

		plugin_pop_current();
	}

	public function display( $p_bug, $p_columns_target ) {
		plugin_push_current( 'Timecard' );

		$p_bug_id = $p_bug->id;
		$t_bug = TimecardBug::load( $p_bug_id, true );
		$t_bug->calculate();
		if ( $t_bug->estimate >= 0 ) {
			echo $t_bug->estimate, plugin_lang_get( 'hours' );
		}

		plugin_pop_current();
	}

}

