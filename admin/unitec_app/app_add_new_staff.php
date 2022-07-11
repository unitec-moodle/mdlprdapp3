<?php

/***************************************************************
 * Customized function
 *
 * Add new peopleSoft staff into Moodle.
 *
 * Yong Liu 05/11/2018
 * 
 ***************************************************************
 */

ini_set('display_errors', 'On');
ini_set('error_reporting', E_ALL);

define('CLI_SCRIPT', true);
date_default_timezone_set('Pacific/Auckland');
// session_start();
require('config_unitec_app.php');
require($CFG_UNITEC_APP->moodle_root . '/config.php');

// defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir.'/authlib.php');


// Initiallized the database.
$dbhost = $CFG->dbhost;
$db_moodle = $CFG->dbname;
$db_peoplesoft = 'peoplesoft';
$dbuser = $CFG->dbuser;
$dbpass = $CFG->dbpass;

echo "\n-------- Synchronize PeopleSoft staff with Moodel ----------------\n";
echo "          Started at " . date("F j, Y, g:i a", time()) . "\n";   


// Create a connection to database peoplesoft.
$peoplesofte_connect = new mysqli($dbhost,$dbuser,$dbpass, $db_peoplesoft) or die ("Couldn't connect to server Peoplesoft");

if ($peoplesofte_connect->connect_errno) {
	print "***** Connect to PeopleSoft database failed: %s ***** \n" . $peoplesofte_connect->connect_error;
	exit();
}

// Create a connection to database moodle.
$moodle_connect = new mysqli($dbhost,$dbuser,$dbpass, $db_moodle) or die ("Couldn't connect to server Moodle");

if ($peoplesofte_connect->connect_errno) {
	print "***** Connect to Moodle database failed: %s ***** \n" . $moodle_connect->connect_error;
	exit();
}

// Get a list of users from Moodle manual accounts...
$field_list = ["email"];
$sql_moodle_users_query = "SELECT DISTINCT " . implode(',', $field_list) . "
				FROM mdl_user ur 
				WHERE ur.auth ='manual' AND ur.deleted = 0 AND ur.suspended = 0";
// echo "**** sql_moodle_users_query = " . $sql_moodle_users_query . "\n";

$moodle_users = array();

if (!get_user_list ($moodle_connect, $sql_moodle_users_query, $moodle_users, $field_list)){
	exit;
}
echo "\n--- " . count($moodle_users['email']) . " Moodle users in the list.\n"; // Debug only.
//echo print_r ($moodle_users['email']) . "\n"; // Debug only.

$today = date("Y-m-d", time());  // Get today's date & time like "2013-03-06".  

// Get a list of current users from peoplesoft accounts, i.e. $today is within ps.active_date and ps.deactive_date.

$field_list = ["email"];
$sql_peoplesoft_current_users_query = "SELECT DISTINCT " . implode(',', $field_list) . "
				FROM peoplesoft_staff ps 
				WHERE ps.user_id <> '' AND '" . $today . "' >= ps.active_date AND '" . $today . "' <= ps.deactive_date";
// echo "**** sql_peoplesoft_current_users_query = " . sql_peoplesoft_current_users_query . "\n";

$peoplesoft_current_users = array();

if (!get_user_list ($peoplesofte_connect, $sql_peoplesoft_current_users_query, $peoplesoft_current_users, $field_list)){
	exit;
}
echo "\n--- " . count($peoplesoft_current_users['email']) . " PeopleSoft staff in the list.\n"; // Debug only.
// echo print_r ($peoplesoft_current_users['email']) . "\n"; // Debug only.

//Get a list of current staff email from table peoplesoft_current_users, that are not in moodle user table. 
// Convert all values to lower case before the comparison.
$new_staff = array_diff(array_map("strtolower", $peoplesoft_current_users['email']), array_map("strtolower", $moodle_users['email']));



// Get a list of unavailable users from peoplesoft accounts, i.e. $today is outside their ps.active_date and ps.deactive_date.

$field_list = ["email"];
$sql_peoplesoft_unavailable_users_query = "SELECT DISTINCT " . implode(',', $field_list) . "
				FROM peoplesoft_staff ps 
				WHERE ps.user_id <> '' AND ('" . $today . "' < ps.active_date OR '" . $today . "' > ps.deactive_date)";
// echo "**** sql_peoplesoft_unavailable_users_query = " . sql_peoplesoft_unavailable_users_query . "\n";

$peoplesoft_unavailable_users = array();

if (!get_user_list ($peoplesofte_connect, $sql_peoplesoft_unavailable_users_query, $peoplesoft_unavailable_users, $field_list)){
	exit;
}

if(!empty($peoplesoft_unavailable_users['email'])){
	
	echo "\n--- " . count($peoplesoft_unavailable_users['email']) . " PeopleSoft unavailable staff (not start or left) in the list.\n"; // Debug only.
	// echo print_r ($peoplesoft_users['email']) . "\n"; // Debug only.

	//Get a list of email for peoplesoft_unavailable_users that are in Moodle user email list. 
	// Convert all values to lower case before the comparison.
	$staff_to_suspend = array_intersect(array_map("strtolower", $peoplesoft_unavailable_users['email']), array_map("strtolower", $moodle_users['email']));
}

// Clean up...
unset($peoplesoft_unavailable_users);
unset($peoplesoft_current_users);
unset($moodle_users);

$count['resumed'] = 0;   			// Number of "manual" users to be resumed from suspension.
$count['username_match'] = 0;   	// Number of new accounts that usernames are already in Moolde but not in "manual" auth type.
$count['username_conflict'] = 0;	// Number of new accounts that their usernames are used by other accounts outside "manual" auth type.
$count['new_user'] = 0;			// Number of pure new accounts to be added.
$count['user_in_db'] = 0;			// Number of accounts which already exist in DB type.
$count['suspended'] = 0;			// Number of accounts which already exist in DB type.
$count['not_suspended'] = 0;			// Staff ID not match in Moodle. Cannot be suspended.


// Add/resume/tranfer new staff to manual account...

if (empty($new_staff)){
	echo "\n----- No new staff added. ----- \n"; // Debug only.
}else {

	$new_user_list = "'" . implode("','", $new_staff) . "'";

	// Get the new staff info from peoplesoft_staff...
	$field_list = ['user_id', 'firstname','lastname','email','dob', 'department','empl_id'];
	$sql_new_ps_users_query = "SELECT DISTINCT " . implode(',', $field_list) . "
					FROM peoplesoft_staff ps 
					WHERE ps.email IN (" . $new_user_list . ")";

//	echo "\n*** sql_new_ps_users_query: " . $sql_new_ps_users_query . " \n"; // Debug only.

	$new_users_info = array();

	if (!get_user_list ($peoplesofte_connect, $sql_new_ps_users_query, $new_users_info, $field_list)){
		exit;
	}
	$num_of_new_users = count($new_users_info['user_id']);
echo "\n--- " . $num_of_new_users . " new user to add to Moodle.\n\n"; // Debug only.
//	echo print_r ($new_users_info) . "\n"; // Debug only.

	// Do not use transactions around this foreach, we want to skip problematic users, not revert everything.
		// Yong Liu 27/06/2012: Count how many accounts found outside DB auth range.

	// Add new staff to Moodle.
	for($i = 0; $i <  $num_of_new_users; $i++){

		add_user_to_moodle($new_users_info, $i, $count);

	}

}

// Suspend unavailable staff in Moodle...

if (!empty($staff_to_suspend)){
	
	$user_to_suspend_list = "'" . implode("','", $staff_to_suspend) . "'";

	// Get the expired staff info from peoplesoft_staff...
	$field_list = ['user_id', 'email','empl_id'];
	$sql_ps_users_suspend_query = "SELECT DISTINCT " . implode(',', $field_list) . "
					FROM peoplesoft_staff ps 
					WHERE ps.email IN (" . $user_to_suspend_list . ")";

//	echo "\n*** sql_new_ps_users_query: " . sql_new_ps_users_query . " \n"; // Debug only.

	$user_to_suspend_info = array();

	if (!get_user_list ($peoplesofte_connect, $sql_ps_users_suspend_query, $user_to_suspend_info, $field_list)){
		exit;
	}
	$num_user_to_suspend = count($user_to_suspend_info['user_id']);
	
echo "\n--- " . $num_user_to_suspend . " user to be suspended in Moodle.\n\n"; // Debug only.
//	echo print_r (user_to_suspend_info) . "\n"; // Debug only.

	// Suspend unavailable/expired staff in Moodle.
	for($i = 0; $i < $num_user_to_suspend; $i++){

		suspend_user_in_moodle($user_to_suspend_info, $i, $count);
	}
}


echo "\n    User already in Db type: " . $count['user_in_db'];
echo "\n    Username conflict but resolved: " . $count['username_conflict'];
echo "\n    User resumed from suspend: " . $count['resumed'];
echo "\n    User revived/transferred from other account type: " . $count['username_match'];
echo "\n    New Moodle user added: " . $count['new_user'];
echo "\n    User suspended: " . $count['suspended'];
echo "\n    User not suspended: " . $count['not_suspended'];

echo "\n--------  Synchronize PeopleSoft staff with Moodel -----------------\n";
echo "          Finisheded at " . date("F j, Y, g:i a", time()) . "\n";  

$peoplesofte_connect->close();
$moodle_connect->close();

exit;


///////////////////////////////////////////////////////////////////
// .
// This function queries the database to get the data list reference. 
// It returns "true" if success and 'false" otherwise. $user_list[index][field]
// is an array with key -> value.
//
////////////////////////////////////////////////////////////////////
	  
	function get_user_list ($connect, $sql_query, &$user_list, $field_list) {
		$sql_query_result = $connect->query($sql_query);
//			echo "---- sql_query_result = " . sql_query_result . " ---\n";		
		if (empty($sql_query_result)){ 
								// No result found.
			echo "\n $sql_query -- Ddatabase query problem!\n";
			return false;			
		} else {
			while ($record = $sql_query_result->fetch_assoc()) {
				foreach($field_list AS $field){
					$user_list[$field][] = $record[$field];
				}
			}
			unset($record);
			return true;
		}
	}
	  
///////////////////////////////////////////////////////////////////
// .
// This function adds new peoplesoft staffs to Moodle as a new user. 
// It returns "true" if success and 'false" otherwise. 
//
////////////////////////////////////////////////////////////////////
	  
	function add_user_to_moodle(&$user_detail, $i, &$count) {
		
		global $CFG, $DB;
        require_once($CFG->dirroot . '/user/lib.php');
		
		if (empty($options['verbose'])) {
			$trace = new null_progress_trace();
		} else {
			$trace = new text_progress_trace();
		}


		$new_user = new stdClass();	
		$new_user->username = $user_detail['user_id'][$i];
		$new_user->firstname = $user_detail['firstname'][$i];
		$new_user->lastname = $user_detail['lastname'][$i];
		$new_user->email = $user_detail['email'][$i];
		$new_user->idnumber = $user_detail['empl_id'][$i];
		$new_user->department = $user_detail['department'][$i];
		$new_user->password = md5($user_detail['dob'][$i]);
		$new_user->timecreated = time();
		$new_user->timemodified = $new_user->timecreated;


		$new_user->confirmed  = 1;
		$new_user->auth       = "manual";
		$new_user->mnethostid = $CFG->mnet_localhost_id;
		$new_user->lang = $CFG->lang;
		
		// If the user is suspended in Moodle, we just resume it.
		if ($olduser = $DB->get_record('user', array('username' => $new_user->username, 'email' => $new_user->email, 
													 'deleted' => 0, 'mnethostid' => $CFG->mnet_localhost_id))) {
			if($olduser->auth == "db"){
				echo "     --- Username '" . $new_user->username . "' exists in DB type and cannot be updated: name: " . $olduser->firstname . " " . 
					$olduser->lastname . ", email: " . $olduser->email . "\n";
				$count['user_in_db'] ++;
				return false;
			}else {
				$updateuser = new stdClass();
				$updateuser->id = $olduser->id;
				$updateuser->suspended = 0;
				$updateuser->auth = "manual";
				user_update_user($updateuser);
				$trace->output(get_string('auth_dbreviveduser', 'auth_db', array('name' => $new_user->username,
					'id' => $olduser->id)), 1);
				// Output message.
				echo "     --- User resumed: Username: " . $olduser->username . ", name: " . $olduser->firstname . " " . 
					$olduser->lastname . ", email: " . $olduser->email . ", previous auth: " . $olduser->auth . 
					", new auth: " . $updateuser->auth . "\n";

				$count['resumed']++;
				return true;
			}
		}

		// Maybe this user name is used by other Moodle account, we need to check if this Moodle account belongs 
		// to the correct PeopleSoft staff. If so we use this account. Otherwise we rename the user name for the 
		// existing account and assign this user name to the new account.
	    if(process_duplicated_user($new_user, $count)) return;

		try {
			$id = user_create_user($new_user, false); // It is truly a new user.
			$new_user->id = $id;
			
			echo "     --- User added: Username: " . $new_user->username . ", name: " . $new_user->firstname . " " . 
				$new_user->lastname . ", email: " . $new_user->email . ", auth: " . $new_user->auth . ", Moodle ID: " . 
				$new_user->id . "\n";
		} catch (moodle_exception $e) {
			echo "     --- User add failed: Username: " . $new_user->username . ", name: " . $new_user->firstname . " " . 
				$new_user->lastname . ", email: " . $new_user->email . ", auth: " . $new_user->auth . "\n";
			return null;
		}
		// Save custom profile fields here.
		require_once($CFG->dirroot . '/user/profile/lib.php');
		profile_save_data($new_user);

		// Make sure user context is present.
		context_user::instance($id);
		$count['new_user']++;
		unset($new_user);
		return $id;
	}

///////////////////////////////////////////////////////////////////
// .
// This function suspends unavailable/expired peoplesoft staffs in 
// Moodle. It returns "true" if success and 'false" otherwise. 
//
////////////////////////////////////////////////////////////////////
	  
	function suspend_user_in_moodle(&$user_detail, $i, &$count) {
		
		global $CFG, $DB;
        require_once($CFG->dirroot . '/user/lib.php');
		
		if (empty($options['verbose'])) {
			$trace = new null_progress_trace();
		} else {
			$trace = new text_progress_trace();
		}
		
		// Find this user in Moodle and suspend him/her. It must match both the email and staff ID. 
		if ($user_in_moodle = $DB->get_record('user', array('email' => $user_detail['email'][$i], 'idnumber' => $user_detail['empl_id'][$i], 
															'deleted' => 0, 'mnethostid' => $CFG->mnet_localhost_id))) {
			if($user_in_moodle->auth == "db"){ // We cannot suspend staff in "DB" as this is a student account which is controlled by another script.
				echo "*** Username '" . $user_in_moodle->username . "' exists in DB type and cannot be suspended: name: " . $user_in_moodle->firstname . " " . 
					$user_in_moodle->lastname . ", email: " . $user_in_moodle->email . "\n";
				$count['user_in_db'] ++;
				return false;
			}else {
				$updateuser = new stdClass();
				$updateuser->id = $user_in_moodle->id;
				$updateuser->suspended = 1;
				$updateuser->auth = "manual";
				user_update_user($updateuser);

				// Output message.
				echo "     --- User suspended: Username: " . $user_in_moodle->username . ", name: " . $user_in_moodle->firstname . " " . 
					$user_in_moodle->lastname . ", email: " . $user_in_moodle->email . ", Staff ID: " . $user_in_moodle->idnumber . ", auth: " . $user_in_moodle->auth . "\n";

				$count['suspended']++;
				return true;
			} 
		} else {
				echo "--- Email '" . $user_detail['email'][$i] . "' -- Staff ID not match in Moodle! Cannot be suspended: PS username: " . $user_detail['user_id'][$i] . " Staff ID: " . $user_detail['empl_id'][$i] . "\n";
				$count['not_suspended'] ++;
				return false;			
		}
	}


///////////////////////////////////////////////////////////////////
// Process duplicated accounts
//
// Yong Liu 07/11/2018
//
// Dealing the situation that this username already exists in Moolde but its auth is not "manual".
//  
///////////////////////////////////////////////////////////////////
	 
	 function process_duplicated_user($user, &$count, $verbose=false){
	 
	 	// Debug only.
//	 	$verbose= true;
		global $CFG, $DB;
				
								// Debug only
		if ($verbose) {						
			echo "username: " . $user->username . "\n";
			echo "first name: " . $user->firstname . "\n";				
			echo "last name: " . $user->lastname . "\n";
			echo "email: " . $user->email . "\n";
			echo "auth: " . $user->auth . "\n";								
			echo "ID number: " . $user->idnumber . "\n\n";
		}
		
		$old_user = $DB->get_record('user', array('username'=>$user->username, 'deleted'=>0, 
												  'mnethostid'=>$user->mnethostid));
																																				
	 	if(!empty($old_user) && $old_user->auth <> "db" && $old_user->auth <> "mnet") 
		{
						// Debug only.		
			if ($verbose) {							
				echo "Old username: " . $old_user->username . "\n";
				echo "Old first name: " . $old_user->firstname . "\n";				
				echo "Old last name: " . $old_user->lastname . "\n";
				echo "Old email: " . $old_user->email . "\n";				
				echo "Old auth: " . $old_user->auth . "\n";
				echo "Old ID number: " . $old_user->idnumber . "\n\n";
			}	
								// Check if their email match.
								// Just deliminate the posibility that the student has not got email.							
			if((strcasecmp($user->email, $old_user->email) == 0 && 
						   $user->email !== "no.email@psoft.student.admin" && 				  
						   $user->email !== "") 							
				|| (strcasecmp($user->idnumber, $old_user->idnumber) == 0) // If the ID match?
																				
				// If the first name and last name match?
				|| ((stripos($user->firstname, $old_user->firstname) === 0 || 
					stripos($old_user->firstname, $user->firstname) === 0 ) &&
				   (stripos($user->lastname, $old_user->lastname) === 0 ||
					stripos($old_user->lastname, $user->lastname) === 0)))
			   
				{				// User name and one or more of the email, student ID or full name match. Then we just merge the 
								// two accounts with the user name unchanged.
														
					$DB->set_field('user', 'auth', 'manual', array('id'=>$old_user->id));
					$DB->set_field('user', 'suspended', '0', array('id'=>$old_user->id));	
					$DB->set_field('user', 'idnumber', $user->idnumber, array('id'=>$old_user->id));
					$DB->set_field('user', 'firstname', $user->firstname, array('id'=>$old_user->id));
					$DB->set_field('user', 'lastname', $user->lastname, array('id'=>$old_user->id));
					$DB->set_field('user', 'email', $user->email, array('id'=>$old_user->id));	

					
				echo "     --- User transferred / merged: Username: " . $user->username . ", name: " . $user->firstname . " " . 
					$user->lastname . ", email: " . $user->email . ", previous auth: " . $user->auth . 
					", new auth: " . $old_user->auth . "\n";
															
					$count['username_match']++;
			
			} else{  	// Username match but nothing else match. Then we just change the username of the existing 
						// account to something else and give this username to this new user.
						
						// Add "0yq" to the existing username
				$DB->set_field('user', 'username', $user->username . "0yq", array('id'=>$old_user->id));			
									
				echo "               ----- Renamed username: " . $old_user->username . " => " . $old_user->username . 
					"0yq in '" . $old_user->auth . "'\n";
											
						// Then create a new account but still use this username.	
				$user->timecreated = time();
				$user->timemodified = $user->timecreated;			
				$user->password = $user->password;
				
//				echo "\n##### Username: " . $user->username . "  Password: " . $user->password . " #####\n";
	
				try {
					$id = user_create_user($user, false); // It is truly a new user.
				$user->id = $id;
				echo "     ------ User added: Username: " . $user->username . ", name: " . $user->firstname . " " . 
					$user->lastname . ", email: " . $user->email . ", auth: " . $user->auth . ", Moodle ID: " . 
					$user->id . "\n";
				} catch (moodle_exception $e) {
				echo "     ------ User add failed: Username: " . $user->username . ", name: " . $user->firstname . " " . 
					$user->lastname . ", email: " . $user->email . ", auth: " . $user->auth . "\n";
					return null;
				}
				// Save custom profile fields here.
				require_once($CFG->dirroot . '/user/profile/lib.php');
				profile_save_data($user);

				// Make sure user context is present.
				context_user::instance($id);		
				
				$count['username_conflict']++;
				
			}
			unset($user);
			unset($old_user);
			return true;
		} else {
			unset($user);
			unset($old_user);
			return false;
		}
	} 
?>