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

access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

html_page_top1( plugin_lang_get( 'title' ) );
html_page_top2();

print_manage_menu();

$t_current_project = helper_get_current_project();

if( 0 == $t_current_project ){
	#@todo replace below to handle ZERO (ie All Projects)
	echo 'Must select something other than All Projects';
	die();
}

$t_bug_table = db_get_table( 'mantis_bug_table' );

echo '<table class="width100" cellspacing="1">';

echo '<tr class="row-category">
		<td>Bug Id</td>
		<td>Bug Summary</td>
		<td>Assigned To</td>
		<td>Timecard</td>
		<td>Time Remaining</td>
	</tr>';

$i = 1; #row class selector
$t_time_sum = 0;

$t_all_projects = project_hierarchy_get_all_subprojects( $t_current_project );
array_unshift( $t_all_projects,$t_current_project );

foreach( $t_all_projects as $t_all_project ){

	$t_query = "SELECT * FROM $t_bug_table
			WHERE project_id=" . $t_all_project;
	$t_result = db_query_bound( $t_query );

	while ( $t_row = db_fetch_array( $t_result ) ) {

		$t_timecard = TimecardBug::load( $t_row['id'] );
		$t_timecard->summary = substr( $t_row['summary'], 0, 40 );
		$t_timecard->assigned = user_get_name( $t_row['handler_id'] );

		if( $t_timecard->estimate < 0 ){
			$t_timecard->estimate = 'no estimate';
			$row_class = 'negative';
		} else {
			$t_time_sum += $t_timecard->estimate;
			$row_class = "row-$i";
		}

	#@todo implement line below doesn't work properly
	//print_bug_link( $t_timecard->bug_id )
		echo "<tr class='$row_class'>
				<td>" . $t_timecard->bug_id . '</td>' .
				'<td>' . $t_timecard->summary . '</td>
				<td>' . $t_timecard->assigned . '</td>
				<td class="center">' . $t_timecard->timecard . '</td>
				<td class="center">' . $t_timecard->estimate . '</td>
			</tr>';

		$i = ($i == 1) ? 2 : 1; #toggle row class selector
	}
}
echo '<tr><td colspan="4" class="right">Total Time Remaining</td><td class="center positive">' . $t_time_sum . '</td></tr>';
echo '</table>';

?>