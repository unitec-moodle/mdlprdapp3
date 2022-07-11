<?php

//	"app_remove_force_pass.php"
//	By Maloclm Hay 2/03/2022
//	This is a program to disable the auth_forcepasswordchange status in the mdl_user_preferences. 
//

ini_set('display_errors', 'On');
ini_set('error_reporting', E_ALL);

define('CLI_SCRIPT', true);

// session_start();

require('config_unitec_app.php');
require($CFG_UNITEC_APP->moodle_root . '/config.php');


// Show start time and date
echo "\n--------  Moodle - Disable auth_forcepasswordchange status:";
echo "\n          Started at " . date("F j, Y, g:i a") . "\n";


$dbhost = $CFG->dbhost;
$dbuser = $CFG->dbuser;
$dbpass = $CFG->dbpass;
$dbname = $CFG->dbname;


// Create connection
$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Update table
$sql = "update `mdl_user_preferences` set `value` = 0 WHERE `name` like '%auth_forcepasswordchange%'";


// Success report
if ($conn->query($sql) === TRUE) {
  echo "Records updated successfully";
} else {
  echo "Error updating records: " . $conn->error;
}

$conn->close();

// Show end time and date
echo "\n\n--------  Moodle - Disable auth_forcepasswordchange status:";
echo "\n          Finished at " . date("F j, Y, g:i a") . "\n"; 
?>