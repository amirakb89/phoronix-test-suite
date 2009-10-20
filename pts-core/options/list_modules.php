<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

class list_modules implements pts_option_interface
{
	public static function run($r)
	{
		echo pts_string_header("Phoronix Test Suite - Modules");
		foreach(pts_available_modules() as $module)
		{
			$module_details = new pts_user_module_details($module);
			echo sprintf("%-22ls - %-32ls [%s]\n", $module, $module_details->get_module_name() . " v" . $module_details->get_module_version(), $module_details->get_module_author());
		}
		echo "\n";
	}
}

?>
