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

form_security_validate( 'plugin_Timecard_log_time' );

$f_bug_id = gpc_get_int( 'bug_id' );
$f_spent = gpc_get_float( 'spent', 0 );

access_ensure_bug_level( plugin_config_get( 'update_threshold' ), $f_bug_id );

if ( $f_spent > 0 ) {
	$t_update = new TimecardUpdate( $f_bug_id, 0, auth_get_current_user_cookie(), $f_spent );
	$t_update->save();
} else {
	trigger_error( ERROR_GENERIC, ERROR );
}

form_security_purge( 'plugin_Timecard_log_time' );
print_successful_redirect_to_bug( $f_bug_id );

