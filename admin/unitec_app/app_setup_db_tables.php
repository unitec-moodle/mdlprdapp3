<?php
/////////////////////////////////
//
//	"app_setup_bb_tables.php"
//  By Yong Liu 30/03/2011
//
/////////////////////////////////

// This script does the folloing three jobs:
// 1. Import courses info from PeopleSoft snapshot file bbcourses.dat to the table bb_courses in the databse peoplesoft.
// 2. Import staff info from PeopleSoft snapshot file bbstaff.dat to the table bb_staff in the databse peoplesoft.
// 3. Import student exception list from the file app_enrolment_exception.txt to the table exception_list in the databse peoplesoft.
// 4. Import student enrolment info from PeopleSoft snapshot file bbstudents.dat to the table bb_students in the databse peoplesoft.
// 5. Import class date exception info from the file app_class_date_exception.txt to the table exception_list in the databse peoplesoft.
// 6. Import staff info from PeopleSoft snapshot file moodle_stf.dat to the table peoplesoft_staff in the databse peoplesoft.


ini_set('display_errors', 'On');
ini_set('error_reporting', E_ALL);

define('CLI_SCRIPT', true);
date_default_timezone_set('Pacific/Auckland');
// session_start();
require('config_unitec_app.php');
require($CFG_UNITEC_APP->moodle_root . '/config.php');

// Initiallized the database.
$dbhost = $CFG->dbhost;
$dbname = 'peoplesoft';
$dbuser = $CFG->dbuser;
$dbpass = $CFG->dbpass;

$connect = new mysqli($dbhost,$dbuser,$dbpass, $dbname) or die ("Couldn't connect to server");

if ($connect->connect_errno) {
	print "***** Connect failed: %s ***** \n" . $connect->connect_error;
	exit();
}

/////////////////////////////////
//
//	This is a script to pick the courses information from PeopleSoft file, "bbcourses.dat", and populate it into 
//	a table called "bb_courses" in an external database, "peoplesoft", on Moodle database server. 
//
/////////////////////////////////

echo "\n-------- 'bb_courses' data table population:";
echo "\n          Started at " . date("F j, Y, g:i a", time()) . "\n";   

	
	// Read PeopleSoft courses records ando pen PeopleSoft records file.
	
$file_name = $CFG->dirroot . "/admin/unitec_app/bbcourses.dat";	
echo "\nOpen file " . $file_name . "...";

$handle = fopen("$file_name", "r");

if (empty($handle)) { 
	echo "\nNo file exists.\n";
} else {
	if(!feof($handle)) {
		echo "\nFile opened.";	
		echo "\nClean data table...";
					// Empty the table bb_courses.
		$sql_empty_table = "TRUNCATE TABLE bb_courses";
		$result_empty_table = $connect->query($sql_empty_table); 
		
		if(!$result_empty_table) { 
			echo "\n" . $connect->error . "\n"; 
		} else {
			echo "\nData table cleaned.";	
			echo "\nNow populate data table bb_courses...";	
		
			$count = 0;	
			 while (($data = fgetcsv($handle, 200000, "|")) !== FALSE)
			 {   
				// Skip the rows that are not the right contents.
				if( strcasecmp(substr(trim($data[0]),0,3), "***") != 0 && strcasecmp(trim($data[0]), "Subject") != 0 ){ 
									// Skip the commented line (starts with "***") or invalid line such as the first column name.
				   $sql_import="INSERT into bb_courses 
						   (subject, category, class_number, name, description, faculty_code, department_code) 				    	
					values(	'" . trim($data[0]) . "','" . trim($data[1]) . "','" . trim($data[2]) . "',
							'" . str_replace("'", "\'", trim($data[3])) . "','" . str_replace("'", "\'", trim($data[4])) . "',
							'" . trim($data[5]) . "','" . trim($data[6]) . "')"; 
				
	//				   echo "*******  $import \n"; // Debug only.
				   
				   $connect->query($sql_import) or die($connect->error);
				   $count++;	
			   }
			 }
			 echo "\nTotal record: " . $count;	
			 
			 unset($data);		
		}  	// else empty_table
	}		// if !feof($handle)
	fclose($handle);
}			// else empty($handle)	 
		 
echo "\n--------  Moodle data table bb_courses population:";
echo "\n          Finisheded at " . date("F j, Y, g:i a", time()) . "\n";  

/////////////////////////////////
//
//	This is a script to pick the staff information from PeopleSoft file, "bbstaff.dat", and populate it into 
//	a table called "bb_staff" in an external database, "peoplesoft", on Moodle database server. 
//
/////////////////////////////////

echo "\n-------- 'bb_staff' data table population:";
echo "\n          Started at " . date("F j, Y, g:i a", time()) . "\n";   

	// Read PeopleSoft staff records and open PeopleSoft records file.
	
$file_name = $CFG->dirroot . "/admin/unitec_app/bbstaff.dat";	
echo "\nOpen file " . $file_name . "...";

$handle = fopen("$file_name", "r");

if (empty($handle)) { 
	echo"\nNo file exists.";
} else {
		 
	if(!feof($handle)) {
	
		echo "\nFile opened.";	
		
		echo "\nClean data table...";
	
		$sql_empty_table = "TRUNCATE TABLE bb_staff";
		
		$result_empty_table = $connect->query($sql_empty_table); // Empty the table ps_students.
		
		if(!$result_empty_table) { 
			echo "\n" . $connect->error . "\n"; 
		} else {
			echo "\nData table cleaned.";	
	
			echo "\nNow populate data table bb_staff...";	
		
			$count = 0;	
			 while (($data = fgetcsv($handle, 200000, "|")) !== FALSE)
			 {   
				// Skip the rows that are not the right contents.
				if( strcasecmp(substr(trim($data[0]),0,3), "***") != 0 && strcasecmp(trim($data[0]), "USER_ID") != 0 ){ 
												// Skip the blank line or invalid line such as the user name is space(s).
				   $sql_import="INSERT into bb_staff 
						   (user_id, firstname, middlename, lastname, email, dob, department, empl_id, profile) 				    	
					values(	'" . trim($data[0]) . "','" . str_replace("'", "\'", trim($data[1])) . "',
							'" . str_replace("'", "\'", trim($data[2])) . "','" . str_replace("'", "\'", trim($data[3])) . "',
							'" . str_replace("'", "\'", trim($data[4])) . "','" . trim($data[5]) . "','" . trim($data[6]) . "',
							'" . trim($data[7]) . "','" . trim($data[8])  . "')"; 
				
				   $connect->query($sql_import) or die($connect->error);
				   $count++;	
			   }	// if
			 } 	// while
			 echo "\nTotal record: " . $count;	
			 
			 unset($data);
		}		// else !$result_empty_table
	}			// if !feof($handle)
	fclose($handle);
}				// else empty($handle)	 
		 
echo "\n--------  Moodle data table bb_staff population:";
echo "\n          Finisheded at " . date("F j, Y, g:i a", time()) . "\n";  

/////////////////////////////////
//
//	This is a script to pick student exception list from the file, "yong_enrolment_exception.txt", and populate it into 
//	a table, "exception_list", in an external database, "peoplesoft", on Moodle database server. 
//
/////////////////////////////////

echo "\n-------- 'exception_list' data table population:";
echo "\n          Started at " . date("F j, Y, g:i a", time()) . "\n";   

	// Read PeopleSoft student enrollment records and open PeopleSoft records file.
	
$file_name = $CFG->dirroot . "/admin/unitec_app/app_enrolment_exception.txt";	
echo "\nOpen file " . $file_name . "...";

$handle = fopen("$file_name", "r");

if (empty($handle))  { echo "\nNo file exists.";
} else {
	 
	if(!feof($handle)) {
	
		echo "\nFile opened.";	
		
		echo "\nClean data table...";
		
		$sql_empty_table = "TRUNCATE TABLE exception_list";
		
		$result_empty_table = $connect->query($sql_empty_table); // Empty the table ps_students.
		
		if(!$result_empty_table) { 
			echo "\n" . $connect->error . "\n"; 
		} else {
			echo "\nData table cleaned.";	
		
		echo "\nNow populate data table exception_list...";	
		
			$count = 0;	
			while (($data = fgetcsv($handle, 200000, "|")) !== FALSE)
			{   
				// Skip the rows that are not the right contents.
				if( strlen(trim($data[0])) && strcasecmp(substr(trim($data[0]),0,3), "***") != 0 && strcasecmp(trim($data[0]), "USER_ID") != 0){ 
												// Skip the blank line or invalid line such as the user name is space(s).
				   $sql_import="INSERT into exception_list 
						   (user_id, lastname, firstname, email, dob, ps_subject, ps_cat, student_id, ps_class, visa_nsi,crse_grade,
							ps_prog, ps_prog_descr, ps_strm, ps_class_start_dt, ps_class_end_dt) 				    	
					values(	'" . trim($data[0]) . "','" . str_replace("'", "\'", trim($data[1])) . "',
							'" . str_replace("'", "\'", trim($data[2])) . "','" . str_replace("'", "\'", trim($data[3])) . "',
							'" . trim($data[4]) . "','" . trim($data[5]) . "','" . trim($data[6]) . "',
							'" . trim($data[7]) . "','" . trim($data[8]) . "','" . trim($data[9]) . "',
							'" . trim($data[10]) . "','" . trim($data[11]) . "','" . trim($data[12]) .  "',
							'" . trim($data[13]) . "','" . trim($data[14]) . "','" . trim($data[15]) . "')"; 
				
				   $connect->query($sql_import) or die($connect->error);
				   $count++;	
				}	// if
			}		// while
			echo "\nTotal record: " . $count;	
			unset($data);
		}			// else if(!$result_empty_table)
	}				// if(!feof($handle))
	fclose($handle);
}					// else if (empty($handle)) 
		 
echo "\n--------  Moodle data table exception_list population:";
echo "\n          Finisheded at " . date("F j, Y, g:i a", time()) . "\n";  

/////////////////////////////////
//
//	This is a script to pick the students and courses information from PeopleSoft file, "bbstudents.dat", and populate it into 
//	a table called "bb_students" in an external database, "peoplesoft", on Moodle database server. 
//
/////////////////////////////////

echo "\n-------- 'bb_students' data table population:";
echo "\n          Started at " . date("F j, Y, g:i a", time()) . "\n";

	// Read PeopleSoft student enrollment records and open PeopleSoft records file.
	
$file_name = $CFG->dirroot . "/admin/unitec_app/moodle_std.dat";	
echo "\nOpen file " . $file_name . "...";

$handle = fopen("$file_name", "r");

if (empty($handle))  { 
	echo "\nNo file exists.";
} else {
	if(!feof($handle)) {
	
		echo "\nFile opened.";	
		
		echo "\nClean data table...";
		
		$sql_empty_table = "TRUNCATE TABLE bb_students";
		
		$result_empty_table = $connect->query($sql_empty_table); // Empty the table ps_students.
		
		if(!$result_empty_table) { 
			echo "\n" . $connect->error . "\n"; 
		} else {
			echo "\nData table cleaned.";	
		
		echo "\nNow populate data table bb_students...";	
		
			$count = 0;	
			 while (($data = fgetcsv($handle, 200000, "|")) !== FALSE)
			 {   
				// Skip the rows that are not the right contents.
				if(strlen(trim($data[0])) && strcasecmp(substr(trim($data[0]),0,3), "***") != 0 && strcasecmp(trim($data[0]), "USER_ID") != 0 ){ 
												// Skip the blank line or invalid line such as the user name is space(s).
				   $sql_import="INSERT into bb_students 
						   (user_id, lastname, firstname, email, dob, ps_subject, ps_cat, student_id, ps_class, 
							visa_nsi, crse_grade, ps_prog, ps_prog_descr, ps_strm, ps_class_start_dt, ps_class_end_dt, ps_template, 
							gender, ethnic_cd, ethnicity, resid_cd, residency) 				    	
					values(	'" . trim($data[0]) . "','" . str_replace("'", "\'", trim($data[1])) . "',
							'" . str_replace("'", "\'", trim($data[2])) . "','" . str_replace("'", "\'", trim($data[3])) . "',
							'" . trim($data[4]) . "','" . trim($data[5]) . "','" . trim($data[6]) . "',
							'" . trim($data[7]) . "','" . trim($data[8]) . "','" . trim($data[9]) . "',
							'" . trim($data[10]) . "','" . trim($data[11]) . "','" . trim($data[12]) . "',
							'" . trim($data[13]) . "','" . trim($data[14]) . "','" . trim($data[15]) . "',
							'" . trim($data[16]) . "','" . trim($data[17]) . "','" . trim($data[18]) . "',
							'" . trim($data[19]) . "','" . trim($data[20]) ."','" . trim($data[21]) ."')"; 
	
	//				echo "\n --- " . $sql_import . " ---\n";   // Debug only.
				   $connect->query($sql_import) or die($connect->error);
				   $count++;	
			   }			// if
			}				// while
			echo "\nTotal record: " . $count;	
			unset($data);
		}					// else -- if(!$result_empty_table)
	}						// if(!feof($handle)) 
	fclose($handle);
}							// else -- if (empty($handle))  
		 
echo "\n--------  Moodle data table bb_students population:";
echo "\n          Finisheded at " . date("F j, Y, g:i a", time()) . "\n";  

/////////////////////////////////
//
//	This is a script to pick the class start and end date exception list from calss enrolment exception file, 
//  "app_class_date_exception.txt", and populate it into a table called "class_date_exception" in an external 
//  database, "peoplesoft", on Moodle database server. 
//
/////////////////////////////////

echo "\n-------- 'class_date_exception' data table population:";
echo "\n          Started at " . date("F j, Y, g:i a", time()) . "\n";
	
	// Read from calss enrolment exception list and open calss enrolment exception file.
	
$file_name = $CFG->dirroot . "/admin/unitec_app/app_class_date_exception.txt";	
echo "\nOpen file " . $file_name . "...";

$handle = fopen("$file_name", "r");

if (empty($handle))  {
	echo "\nNo file exists.";
} else {
	 
	if(!feof($handle)) {

		echo "\nFile opened.";	
		
		echo "\nClean data table...";
		
		$sql_empty_table = "TRUNCATE TABLE class_date_exception";
		
		$result_empty_table = $connect->query($sql_empty_table); // Empty the table ps_students.
		
		if(!$result_empty_table) { 
			echo "\n" . $connect->error . "\n"; 
		} else {
			echo "\nData table cleaned.";	
			echo "\nNow populate data table class_date_exception...";	
			$count = 0;	
			 while (($data = fgetcsv($handle, 200000, "|")) !== FALSE)
			 {   
								// Skip the blank line or invalid line such as the user name is space(s).
				if(strlen(trim($data[0])) && strcasecmp(substr(trim($data[0]),0,3), "***") != 0 && 
					strcasecmp(trim($data[0]), "PS_COURSE_ID") != 0 ){ 
				   $new_course_id_column = count($data) == 6 ? ", new_course_id" : "";
				   $new_course_id = count($data) == 6 ? ("','" . trim($data[5])) : "";							
				   $sql_import="INSERT into class_date_exception 
						   (ps_course_id, ps_class, ps_strm, ps_class_start_dt, ps_class_end_dt" . $new_course_id_column . ") 				    	
					values(	'" . trim($data[0]) . "','" . trim($data[1]) . "','" . trim($data[2]) . "',
							'" . trim($data[3]) . "','" . trim($data[4]) . $new_course_id . "')"; 
				
				   $connect->query($sql_import) or die($connect->error);
				  
	//				   echo "*******  $import \n"; // Debug only.
				   $count++;	
			   }			// if
			 }				// while
			 echo "\nTotal record: " . $count;	
			 unset($data);		
		}					// else -- if(!$result_empty_table) { 
	}						// if(!feof($handle)) 
		 fclose($handle);
}							// else -- if (empty($handle)) 
		 
echo "\n--------  Moodle data table class_date_exception population:";
echo "\n          Finisheded at " . date("F j, Y, g:i a", time()) . "\n";  


/////////////////////////////////
//
//	This is a script to pick the staff information from PeopleSoft file, "moodle_std.dat", and populate it into 
//	a table called "peoplesoft_staff" in an external database, "peoplesoft", on Moodle database server. 
//
/////////////////////////////////

echo "\n-------- 'peoplesoft_staff' data table population:";
echo "\n          Started at " . date("F j, Y, g:i a", time()) . "\n";   

	// Read PeopleSoft staff records and open PeopleSoft records file.
	
$file_name = $CFG->dirroot . "/admin/unitec_app/moodle_stf.dat";	
echo "\nOpen file " . $file_name . "...";

$handle = fopen("$file_name", "r");

if (empty($handle)) { 
	echo"\nNo file exists.";
} else {
		 
	if(!feof($handle)) {
	
		echo "\nFile opened.";	
		
		echo "\nClean data table...";
	
		$sql_empty_table = "TRUNCATE TABLE peoplesoft_staff";
		
		$result_empty_table = $connect->query($sql_empty_table); // Empty the table ps_students.
		
		if(!$result_empty_table) { 
			echo "\n" . $connect->error . "\n"; 
		} else {
			echo "\nData table cleaned.";	
	
			echo "\nNow populate data table peoplesoft_staff...";	
		
			$count = 0;	
			 while (($data = fgetcsv($handle, 200000, "|")) !== FALSE)
			 {   
				// Skip the rows that are not the right contents.
				if( strcasecmp(substr(trim($data[0]),0,3), "***") != 0 && strcasecmp(trim($data[0]), "USER_ID") != 0 ){ 
												// Skip the blank line or invalid line such as the user name is space(s).
				   $sql_import="INSERT into peoplesoft_staff 
						   (user_id, firstname, middlename, lastname, email, dob, job_title, department, empl_id, upn, active_date, deactive_date) 				    	
					values(	'" . trim($data[0]) . "','" . (empty(trim($data[1])) ? '' : str_replace("'", "\'", trim($data[1]))) . "',
							'" . (empty(trim($data[2])) ? '' : str_replace("'", "\'", trim($data[2]))) . "',
							'" . (empty(trim($data[3])) ? '' : str_replace("'", "\'", trim($data[3]))) . "','" . str_replace("'", "\'", 
							 trim($data[4])) . "','" . trim($data[5]) . "','" . trim($data[6]) ."','" . trim($data[7]) . "',
							'E" . trim($data[8]) . "','" . trim($data[9])  .  "', '" . trim($data[10])  .  "',
							'" . (empty(trim($data[11])) ? date('Y-m-d', strtotime('+180 days')) : trim($data[11])) ."')"; 

//					echo "\n" . trim($data[9]) . "---" . (empty(trim($data[10])) ? date('Y-m-d', strtotime('+365 days')) : trim($data[9])) . "\n";
				
				   $connect->query($sql_import) or die($connect->error);
				   $count++;	
			   }	// if
			 } 	// while
			 echo "\nTotal record: " . $count;	
			 
			 unset($data);
		}		// else !$result_empty_table
	}			// if !feof($handle)
	fclose($handle);
}				// else empty($handle)	 
		 
echo "\n--------  Moodle data table peoplesoft_staff population:";
echo "\n          Finisheded at " . date("F j, Y, g:i a", time()) . "\n";  

$connect->close();

?>

