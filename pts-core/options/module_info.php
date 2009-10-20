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

class module_info implements pts_option_interface
{
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, "pts_is_module", null, "No module found.")
		);
	}
	public static function run($args)
	{
		$module = new pts_user_module_details($args[0]);
		echo pts_string_header("Module: " . $module->get_module_name());

		if(in_array($args[0], pts_module_manager::attached_modules()))
		{
			echo "** This module is currently loaded. **\n";
		}

		echo "Version: " . $module->get_module_version() . "\n";
		echo "Author: " . $module->get_module_author() . "\n";
		echo "Description: " . $module->get_module_description() . "\n";
		echo "\n" . $module->get_module_info() . "\n";
		echo "\n";
	}
}

?>
