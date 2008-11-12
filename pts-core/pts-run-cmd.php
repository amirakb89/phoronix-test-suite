<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-run-cmd.php: The main code for supporting non-run options aside from the test execution itself.

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


require("pts-core/functions/pts-functions.php");

$COMMAND = $argv[1];
pts_set_assignment("COMMAND", getenv("PTS_COMMAND"));

if(isset($argv[2]))
{
	$ARG_1 = $argv[2];
}
else
{
	$ARG_1 = "";
}

if(isset($argv[3]))
{
	$ARG_2 = $argv[3];
}
else
{
	$ARG_2 = "";
}

if(isset($argv[4]))
{
	$ARG_3 = $argv[4];
}
else
{
	$ARG_3 = "";
}

switch($COMMAND)
{
	case "INSTALL_TEST":
		include_once("pts-core/functions/pts-functions-install.php");

		if(empty($ARG_1))
		{
			echo "\nThe test or suite name to install must be supplied.\n";
		}
		else
		{
			if(IS_SCTP_MODE)
			{
				$ARG_1 = basename($ARG_1);
			}

			if(pts_read_assignment("COMMAND") == "force-install")
			{
				pts_set_assignment("PTS_FORCE_INSTALL", 1);
			}

			$ARG_1 = strtolower($ARG_1);

			if(strpos($ARG_1, "pcqs") !== false && !is_file(XML_SUITE_LOCAL_DIR . "pcqs-license.txt"))
			{
				// Install the Phoronix Certification & Qualification Suite
				$agreement = wordwrap(file_get_contents("http://www.phoronix-test-suite.com/pcqs/pcqs-license.txt"), 65);

				if(strpos($agreement, "PCQS") == false)
				{
					pts_exit("An error occurred while connecting to the Phoronix Test Suite Server. Please try again later.");
				}

				echo "\n\n" . $agreement;
				$agree = pts_bool_question("Do you agree to these terms in full and wish to proceed (y/n)?", false);

				if($agree)
				{
					pts_download("http://www.phoronix-test-suite.com/pcqs/download-pcqs.php", XML_SUITE_LOCAL_DIR . "pcqs-suite.tar");
					pts_extract_file(XML_SUITE_LOCAL_DIR . "pcqs-suite.tar", true);
					echo pts_string_header("The Phoronix Certification & Qualification Suite is now installed.");
				}
				else
				{
					pts_exit(pts_string_header("In order to run PCQS you must agree to the listed terms."));
				}
			}

			// Any external dependencies?
			echo "\n";
			pts_install_package_on_distribution($ARG_1);

			// Install tests
			pts_start_install($ARG_1);

			if(getenv("SILENT_INSTALL") !== false)
			{
				define("PTS_EXIT", 1);
			}
		}
		break;
	case "INSTALL_ALL":
		include_once("pts-core/functions/pts-functions-install.php");

		if(pts_read_assignment("COMMAND") == "force-install-all")
		{
			pts_set_assignment("PTS_FORCE_INSTALL", 1);
		}

		pts_module_process("__pre_install_process");
		foreach(pts_available_tests_array() as $test)
		{
			// Any external dependencies?
			pts_install_package_on_distribution($test);

			// Install tests
			pts_start_install($test);
		}
		pts_module_process("__post_install_process");
		break;
	case "INSTALL_EXTERNAL_DEPENDENCIES":
		include_once("pts-core/functions/pts-functions-install.php");

		if(empty($ARG_1))
		{
			echo "\nThe test or suite name to install external dependencies for must be supplied.\n";
		}
		else
		{
			if($ARG_1 == "phoronix-test-suite" || $ARG_1 == "pts" || $ARG_1 == "trondheim-pts")
			{
				$pts_dependencies = array("php-gd", "php-extras", "build-utilities");
				$packages_to_install = array();
				$continue_install = pts_package_generic_to_distro_name($packages_to_install, $pts_dependencies);

				if($continue_install)
				{
					pts_install_packages_on_distribution_process($packages_to_install);
				}
			}
			else
			{
				pts_install_package_on_distribution($ARG_1);
			}
		}
		break;
	case "MAKE_DOWNLOAD_CACHE":
		include_once("pts-core/functions/pts-functions-install.php");
		echo pts_string_header("Phoronix Test Suite - Generating Download Cache");
		pts_generate_download_cache();
		echo "\n";
		break;
	case "LIST_TESTS":
		echo pts_string_header("Phoronix Test Suite - Tests");
		foreach(pts_available_tests_array() as $identifier)
		{
			if(pts_test_supported($identifier) || IS_DEBUG_MODE)
			{
				echo new pts_test_profile_details($identifier);
			}
		}
		echo "\n";
		break;
	case "LIST_SUITES":
		echo pts_string_header("Phoronix Test Suite - Suites");
		$has_partially_supported_suite = false;
		foreach(pts_available_suites_array() as $identifier)
		{
			$suite_info = new pts_test_suite_details($identifier);

			if($has_partially_supported_suite == false && $suite_info->partially_supported())
			{
				$has_partially_supported_suite = true;
			}

			echo $suite_info;
		}
		echo "\n";
		if($has_partially_supported_suite)
		{
			echo "* Indicates a partially supported suite.\n\n";
		}
		break;
	case "LIST_MODULES":
		echo pts_string_header("Phoronix Test Suite - Modules");
		$available_modules = array_merge(glob(MODULE_DIR . "*.sh"), glob(MODULE_DIR . "*.php"));
		asort($available_modules);
		foreach($available_modules as $module_file)
		{
			echo new pts_user_module_details($module_file);
		}
		echo "\n";
		break;
	case "LIST_INSTALLED_TESTS":
		echo pts_string_header("Phoronix Test Suite - Installed Tests");
		foreach(pts_installed_tests_array() as $identifier)
		{
			if(is_test($identifier))
			{
			 	echo new pts_installed_test_details($identifier);
			}
		}
		echo "\n";
		break;
	case "LIST_TEST_USAGE":
		echo pts_string_header("Phoronix Test Suite - Test Usage");
		printf("%-18ls   %-8ls %-13ls %-11ls %-13ls %-10ls\n", "TEST", "VERSION", "INSTALL DATE", "LAST RUN", "AVG RUN-TIME", "TIMES RUN");
		foreach(pts_installed_tests_array() as $identifier)
		{
			echo new pts_test_usage_details($identifier);
		}
		echo "\n";
		break;
	case "LIST_SAVED_RESULTS":
		echo pts_string_header("Phoronix Test Suite - Saved Results");
		foreach(glob(SAVE_RESULTS_DIR . "*/composite.xml") as $saved_results_file)
		{
			echo new pts_test_results_details($saved_results_file);
		}
		echo "\n";
		break;
	case "RESULT_INFO":
		if(is_file(($saved_results_file = SAVE_RESULTS_DIR . $ARG_1 . "/composite.xml")))
		{
			echo new pts_test_result_info_details($saved_results_file);
		}
		else
		{
			echo "\n" . $ARG_1 . " isn't a valid results file.\n";
		}
		echo "\n";
		break;
	case "LIST_POSSIBLE_EXTERNAL_DEPENDENCIES":
		echo pts_string_header("Phoronix Test Suite - Possible External Dependencies");
		$xml_parser = new tandem_XmlReader(XML_DISTRO_DIR . "generic-packages.xml");
		$dependency_titles = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_TITLE);
		sort($dependency_titles);

		foreach($dependency_titles as $title)
		{
			echo "- " . $title . "\n";
		}
		echo "\n";

		break;
	case "INFO":
		if(is_suite($ARG_1))
		{
			$suite = new pts_test_suite_details($ARG_1);
			echo $suite->info_string();
		
			echo "\n";
		}
		else if(is_test($ARG_1))
		{
			$suite = new pts_test_profile_details($ARG_1);
			echo $suite->info_string();
		
			echo "\n";
		}
		else
		{
			echo "\n" . $ARG_1 . " is not recognized.\n";
		}
		break;
	case "MODULE_INFO":
		$ARG_1 = strtolower($ARG_1);
		if(is_file(($path = MODULE_DIR . $ARG_1 . ".php")) || is_file(($path = MODULE_DIR . $ARG_1 . ".sh")))
		{
			$module = new pts_user_module_details($path);
			echo $module->info_string();

			echo "\n";
		}
		else
		{
			echo "\n" . $ARG_1 . " is not recognized.\n";
		}
		break;
	case "MODULE_SETUP":
		$ARG_1 = strtolower($ARG_1);
		if(is_file(MODULE_DIR . $ARG_1 . ".php"))
		{
		 	$module = $ARG_1;
			$pre_message = "";

			if(!in_array($module, pts_attached_modules()) && !class_exists($module))
			{
				include(MODULE_DIR . $module . ".php");
			}

			$module_name = pts_php_module_call($module, "module_name");
			$module_description = pts_php_module_call($module, "module_description");
			$module_setup = pts_php_module_call($module, "module_setup");

			echo pts_string_header("Module: " . $module_name);
			echo $module_description . "\n";

			if(count($module_setup) == 0)
			{
				echo "\nThere are no options available for configuring with the " . $ARG_1 . " module.";
			}
			else
			{
				$set_options = array();
				foreach($module_setup as $module_option)
				{
					do
					{
						echo "\n" . $module_option->get_formatted_question();
						$input = trim(fgets(STDIN));
					}
					while(!$module_option->is_supported_value($input));

					if(empty($input))
					{
						$input = $module_option->get_default_value();
					}

					$this_input_identifier = $module_option->get_identifier();

					$set_options[$ARG_1 . "__" . $this_input_identifier] = $input;
				}
				pts_module_config_init($set_options);
			}

			echo "\n";
		}
		else
		{
			echo "\n" . $ARG_1 . " is not recognized.\n";
		}
		break;
	case "SHOW_RESULT":
		$URL =  pts_find_result_file($ARG_1);

		if($URL != false)
		{
			pts_run_shell_script("pts-core/scripts/launch-browser.sh", $URL);
		}
		else
		{
			echo "\n$ARG_1 was not found.\n";
		}
		break;
	case "REFRESH_GRAPHS":
		if(is_file(SAVE_RESULTS_DIR . $ARG_1 . "/composite.xml"))
		{
			$composite_xml = file_get_contents(SAVE_RESULTS_DIR . $ARG_1 . "/composite.xml");

			if(pts_save_result($ARG_1 . "/composite.xml", $composite_xml))
			{
				echo "\nThe Phoronix Test Suite Graphs Have Been Re-Rendered.\n";
				display_web_browser(SAVE_RESULTS_DIR . $ARG_1 . "/composite.xml");
			}
		}
		else
		{
			echo pts_string_header($ARG_1 . " was not found.");
		}
		break;
	case "UPLOAD_RESULT":
		include_once("pts-core/functions/pts-functions-run.php");

		$USE_FILE = pts_find_result_file($ARG_1, false);

		if($USE_FILE == false)
		{
			echo "\nThis result doesn't exist!\n";
			exit(0);
		}

		$tags_input = pts_promt_user_tags();
		echo "\n";

		$upload_url = pts_global_upload_result($USE_FILE, $tags_input);

		if(!empty($upload_url))
		{
			echo "Results Uploaded To: " . $upload_url . "\n\n";
			pts_module_process("__event_global_upload", $upload_url);
		}
		else
		{
			echo "\nResults Failed To Upload.\n";
		}
		break;
	case "REMOVE_ALL_RESULTS":
		$remove_all = pts_bool_question("Are you sure you wish to remove all saved results (Y/n)?", true);

		if($remove_all)
		{
			foreach(glob(SAVE_RESULTS_DIR . "*/composite.xml") as $saved_results_file)
			{
				$saved_identifier = basename($saved_results_file, ".xml");
				pts_remove_saved_result($saved_identifier);
			}
			echo "\n";
		}
		break;
	case "REMOVE_RESULT":
		if(is_file(SAVE_RESULTS_DIR . $ARG_1 . "/composite.xml"))
		{
			echo "\n";
			pts_remove_saved_result($ARG_1);
		}
		else
		{
			echo "\nThis result doesn't exist!\n";
		}
		break;
	case "REMOVE_INSTALLED_TEST":
		if(is_file(TEST_ENV_DIR . $ARG_1 . "/pts-install.xml"))
		{
			if(pts_bool_question("Are you sure you wish to remove the test " . $ARG_1 . " (y/N)?", false))
			{
				pts_remove(TEST_ENV_DIR . $ARG_1);
				echo "\nThe " . $ARG_1 . " test has been removed.\n\n";
			}
			else
			{
				echo "\n";
			}
		}
		else
		{
			echo "\n" . $ARG_1 . " is not installed.\n\n";
		}
		break;
	case "SYS_INFO":
		echo pts_string_header("Phoronix Test Suite v" . PTS_VERSION . " (" . PTS_CODENAME . ")\nSystem Information");
		echo "Hardware:\n" . pts_hw_string() . "\n\n";
		echo "Software:\n" . pts_sw_string() . "\n\n";
		break;
	case "MERGE_RESULTS":
		include_once("pts-core/functions/pts-functions-merge.php");

		$BASE_FILE = $ARG_1;
		$MERGE_FROM_FILE = $ARG_2;
		$MERGE_TO = $ARG_3;

		if(empty($BASE_FILE) || empty($MERGE_FROM_FILE))
		{
			echo "\nTwo saved result profile names must be supplied.\n";
		}
		else
		{
			$BASE_FILE = pts_find_result_file($BASE_FILE);
			$MERGE_FROM_FILE = pts_find_result_file($MERGE_FROM_FILE);

			if($BASE_FILE == false || $MERGE_FROM_FILE == false)
			{
				echo "\n" . $BASE_FILE . " or " . $MERGE_FROM_FILE . " couldn't be found.\n";
			}
			else
			{
				if(!empty($MERGE_TO) && !is_dir(SAVE_RESULTS_DIR . $MERGE_TO))
				{
					$MERGE_TO .= "/composite.xml";
				}
				else
				{
					$MERGE_TO = null;
				}

				if(empty($MERGE_TO))
				{
					do
					{
						$rand_file = rand(1000, 9999);
						$MERGE_TO = "merge-" . $rand_file . '/';
					}
					while(is_dir(SAVE_RESULTS_DIR . $MERGE_TO));

					$MERGE_TO .= "composite.xml";
				}

				// Merge Results
				$MERGED_RESULTS = pts_merge_test_results(file_get_contents($BASE_FILE), file_get_contents($MERGE_FROM_FILE));
				pts_save_result($MERGE_TO, $MERGED_RESULTS);
				echo "Merged Results Saved To: " . SAVE_RESULTS_DIR . $MERGE_TO . "\n\n";
				display_web_browser(SAVE_RESULTS_DIR . $MERGE_TO);
			}
		}
		break;
	case "ANALYZE_RESULTS":
		include_once("pts-core/functions/pts-functions-merge.php");

		$BASE_FILE = pts_find_result_file($ARG_1);
		$SAVE_TO = $ARG_2;

		if($BASE_FILE == false)
		{
			echo "\n" . $BASE_FILE . " couldn't be found.\n";
		}
		else
		{
			if(!empty($SAVE_TO) && !is_dir(SAVE_RESULTS_DIR . $SAVE_TO))
			{
				$SAVE_TO .= "/composite.xml";
			}
			else
			{
				$SAVE_TO = null;
			}

			if(empty($SAVE_TO))
			{
				do
				{
					$rand_file = rand(1000, 9999);
					$SAVE_TO = "analyze-" . $rand_file . '/';
				}
				while(is_dir(SAVE_RESULTS_DIR . $SAVE_TO));

				$SAVE_TO .= "composite.xml";
			}

			// Analyze Results
			$SAVED_RESULTS = pts_merge_batch_tests_to_line_comparison(@file_get_contents($BASE_FILE));
			pts_save_result($SAVE_TO, $SAVED_RESULTS);
			echo "Results Saved To: " . SAVE_RESULTS_DIR . $SAVE_TO . "\n\n";
			display_web_browser(SAVE_RESULTS_DIR . $SAVE_TO);
		}
		break;
	case "TEST_MODULE":
		$module = strtolower($ARG_1);
		if(is_file(MODULE_DIR . $module . ".php") || is_file(MODULE_DIR . $module . ".sh"))
		{
			pts_load_module($module);
			pts_attach_module($module);

			echo pts_string_header("Starting Module Test Process");

			$module_processes = pts_module_processes();

			foreach($module_processes as $process)
			{
				if(IS_DEBUG_MODE)
				{
					echo "Calling: " . $process . "()\n";
				}

				pts_module_process($process);
				sleep(1);
			}
			echo "\n";
		}
		else
		{
			echo "\n" . $module . " is not recognized.\n";
		}
		break;
	case "DIAGNOSTICS_DUMP":
		echo pts_string_header("Phoronix Test Suite v" . PTS_VERSION . " (" . PTS_CODENAME . ")\n" . "Diagnostics Dump");
		$pts_defined_constants = get_defined_constants(true);
			foreach($pts_defined_constants["user"] as $constant => $constant_value)
			{
				if(substr($constant, 0, 2) != "P_" && substr($constant, 0, 3) != "IS_")
				{
					echo $constant . " = " . $constant_value . "\n";
				}
			}

			echo "\nEnd-User Run-Time Variables:\n";
			foreach(pts_user_runtime_variables() as $var => $var_value)
			{
				echo $var . " = " . $var_value . "\n";
			}
			echo "\nEnvironmental Variables (accessible via test scripts):\n";
			foreach(pts_env_variables() as $var => $var_value)
			{
				echo $var . " = " . $var_value . "\n";
			}
			echo "\n";
		break;
	case "INITIAL_CONFIG":
		if(is_file(PTS_USER_DIR . "user-config.xml"))
		{
			copy(PTS_USER_DIR . "user-config.xml", PTS_USER_DIR . "user-config.xml.old");
			unlink(PTS_USER_DIR . "user-config.xml");
		}
		pts_user_config_init();
		break;
	case "LOGIN":
		echo "\nIf you haven't already registered for your free Phoronix Global account, you can do so at http://global.phoronix-test-suite.com/\n\nOnce you have registered your account and clicked the link within the verification email, enter your log-in information below.\n\n";
		echo "User-Name: ";
		$username = trim(fgets(STDIN));
		echo "Password: ";
		$password = md5(trim(fgets(STDIN)));
		$uploadkey = @file_get_contents("http://www.phoronix-test-suite.com/global/account-verify.php?user_name=" . $username . "&user_md5_pass=" . $password);

		if(!empty($uploadkey))
		{
			pts_user_config_init($username, $uploadkey);
			echo "\nAccount: " . $uploadkey . "\nAccount information written to user-config.xml.\n\n";
		}
		else
		{
			echo "\nPhoronix Global Account Not Found.\n";
		}
		break;
	case "BATCH_SETUP":
		echo "\nThese are the default configuration options for when running the Phoronix Test Suite in a batch mode (i.e. running phoronix-test-suite batch-benchmark universe). Running in a batch mode is designed to be as autonomous as possible, except for where you'd like any end-user interaction.\n\n";
		$batch_options = array();
		$batch_options[0] = pts_bool_question("Save test results when in batch mode (Y/n)?", true);

		if($batch_options[0] == true)
		{
			$batch_options[1] = pts_bool_question("Open the web browser automatically when in batch mode (y/N)?", false);
			$batch_options[2] = pts_bool_question("Auto upload the results to Phoronix Global (Y/n)?", true);
			$batch_options[3] = pts_bool_question("Prompt for test identifier (Y/n)?", true);
			$batch_options[4] = pts_bool_question("Prompt for test description (Y/n)?", true);
			$batch_options[5] = pts_bool_question("Prompt for saved results file-name (Y/n)?", true);
		}
		else
		{
			$batch_options[1] = false;
			$batch_options[2] = false;
			$batch_options[3] = false;
			$batch_options[4] = false;
			$batch_options[5] = false;
		}

		pts_user_config_init(null, null, $batch_options);
		echo "\nBatch settings saved.\n\n";
		break;
	case "CLONE":
		if(is_file(SAVE_RESULTS_DIR . $ARG_1 . "/composite.xml"))
		{
			echo "A saved result already exists with the same name.\n\n";
		}
		else
		{
			if(pts_is_global_id($ARG_1))
			{
				pts_save_result($ARG_1 . "/composite.xml", pts_global_download_xml($ARG_1));
				// TODO: re-render the XML file and generate the graphs through that save
				echo "Result Saved To: " . SAVE_RESULTS_DIR . $ARG_1 . "/composite.xml\n\n";
				//display_web_browser(SAVE_RESULTS_DIR . $ARG_1 . "/composite.xml");
			}
			else
			{
				echo $ARG_1 . " is an unrecognized Phoronix Global ID.\n\n";
			}
		}
		break;
	case "VERSION":
		echo "\nPhoronix Test Suite v" . PTS_VERSION . " (" . PTS_CODENAME . ")\n\n";
		break;
	default:
		echo "Phoronix Test Suite: Internal Error.\nCommand Not Recognized (" . pts_read_assignment("COMMAND") . ").\n";
}

?>
